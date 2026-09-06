/*! Door Open Intro 4.1.0 — https://github.com/EmonAhammed1/Door-Open */
(function () {
	'use strict';

	var html = document.documentElement;
	var SETTINGS = window.DOI_CONFIG;
	// The <head> gate decides whether this visit gets the door (see doi_print_head in the plugin).
	if (!SETTINGS || !html.classList.contains('doi-on') || document.getElementById('doi-root')) return;

	// ---------------------------------------------------------------------------------------------
	// Each door photograph is measured in its own pixels and mapped to the screen with the same math as
	// `background-size: cover`, so it lines up with the still shown while loading. Leaves follow the inner edge of
	// the dark doorway outline and overlap by 0.75px at the seam; the wall's opening (`hole`) is 2px smaller than
	// the leaves, so the closed door has no seam around it.
	// ---------------------------------------------------------------------------------------------
	const SETS = {
		desktop: {
			img: { w: 1672, h: 941 }, idle: SETTINGS.idle, lit: SETTINGS.lit,
			seam: 836,
			leafL: { x0: 549.5, x1: 836.75, y0: 66, y1: 822.5, hinge: 551, path: 'M0 756.5L3.5 259A283 259 0 0 1 286.5 0L287.25 0L287.25 756.5Z' },
			leafR: { x0: 835.25, x1: 1122.5, y0: 66, y1: 822.5, hinge: 1121, path: 'M0 756.5L0 0L0.75 0A283 259 0 0 1 283.75 259L287.25 756.5Z' },
			hole: 'M551.5 820.5L555 325A281 257 0 0 1 836 68A281 257 0 0 1 1117 325L1120.5 820.5Z',
			opening: { springY: 325, bottom: 820.5, rx: 281, ry: 257, cx: 836, left: [555, 551.5], right: [1117, 1120.5] },
			emblem: [836, 344], // moved up 55px, then 120px more, in the photos (Oct 2026)
			focus: [836, 460],
			word: { y: 232, size: 44 },
			tag: { y: 272, size: 17.5 },
			cta: { y: 520, size: 13 }, // in the middle of the door, under the icon
			thickness: 18,
			// on screens narrower than the photo's door area, this much of its width is fitted instead of `cover`
			// cropping the door; the space above and below is filled with a blurred copy
			fitWidth: 760,
		},
		// portrait screens (phones, upright tablets): the 941 x 1671 phone photographs
		phone: {
			img: { w: 941, h: 1671 }, idle: SETTINGS.phoneIdle, lit: SETTINGS.phoneLit,
			seam: 470,
			leafL: { x0: 135, x1: 470.75, y0: 244, y1: 1397, hinge: 137, path: 'M0 1153L5 286A330 286 0 0 1 335 0L335.75 0L335.75 1153Z' },
			leafR: { x0: 469.25, x1: 804, y0: 244, y1: 1397, hinge: 802, path: 'M0 1153L0 0L0.75 0A330 286 0 0 1 330.75 286L334.75 1153Z' },
			hole: 'M137 1395L142 530A328 284 0 0 1 470 246A328 284 0 0 1 798 530L802 1395Z',
			opening: { springY: 530, bottom: 1395, rx: 328, ry: 284, cx: 470, left: [142, 137], right: [798, 802] },
			emblem: [469, 650], // moved up 68px, then 200px more, in the photos (Oct 2026)
			focus: [470, 843],
			word: { y: 500, size: 46 },
			tag: { y: 545, size: 21 },
			cta: { y: 905, size: 13 }, // in the middle of the door, under the icon
			thickness: 21,
			fitWidth: 760,
		},
	};
	// must match the (max-aspect-ratio: 3/4) rules for the loading still in the plugin's <head> output
	const pickSet = (vw, vh) => (SETTINGS.phoneIdle && vw / vh <= 0.75 ? 'phone' : 'desktop');

	const CFG = {
		openDeg: 84,
		direction: 'out',   // leaves swing toward the viewer; 'in' swings them away
		overshoot: 1.1,     // the camera travels this much past the point where the doorway fills the screen
		persp: 1.1,         // camera perspective, in multiples of the photo's on-screen width
		depth: 3,           // how far behind the door the page sits, in multiples of the perspective
		// the door starts opening `overlap` ms before its engraving is fully lit (see computePlan)
		T: { ignite: 3400, overlap: 120, openDur: 2100, camDelay: 300, camDur: 2800 },
		particles: 20,
	};
	let setName = null; // the active door set; its measurements are copied into CFG by applySet()

	const clamp01 = v => (v < 0 ? 0 : v > 1 ? 1 : v);
	const ease = t => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
	const easeInv = y => (y < 0.5 ? Math.cbrt(y / 4) : 1 - Math.cbrt(2 * (1 - y)) / 2);
	const smooth = t => { t = clamp01(t); return t * t * (3 - 2 * t); };
	const svgMask = (vb, d, evenodd) =>
		'url("data:image/svg+xml,' + encodeURIComponent(
			"<svg xmlns='http://www.w3.org/2000/svg' viewBox='" + vb + "' preserveAspectRatio='none'><path " + (evenodd ? "fill-rule='evenodd' " : '') + "d='" + d + "'/></svg>"
		) + '")';
	function mulberry32(a) { return function () { a |= 0; a = a + 0x6D2B79F5 | 0; let t = Math.imul(a ^ a >>> 15, 1 | a); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; }; }
	const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// ------------------------------------------------ DOM ------------------------------------------------
	const el = (tag, cls, parent) => { const n = document.createElement(tag); if (cls) n.className = cls; if (parent) parent.appendChild(n); return n; };

	const root = el('div');
	root.id = 'doi-root';
	root.setAttribute('role', 'dialog');
	root.setAttribute('aria-modal', 'true');
	root.setAttribute('aria-label', SETTINGS.wordmark || 'Welcome');
	root.style.visibility = 'hidden';

	const back = el('div', 'doi-layer', root);
	const room = el('div', 'doi-room', back);
	const bcam = el('div', 'doi-cam', back);
	const bworld = el('div', 'doi-world', bcam);

	const front = el('div', 'doi-cam', root);
	const world = el('div', 'doi-world', front);

	function makeCanvas(parent, withMark) {
		const cv = el('div', 'doi-canvas', parent);
		const idle = el('img', 'doi-idle', cv); idle.alt = ''; idle.decoding = 'async';
		const lit = el('img', 'doi-lit', cv); lit.alt = ''; lit.decoding = 'async';
		let mark = null, word = null, tag = null;
		if (withMark && (SETTINGS.wordmark || (SETTINGS.tagline && SETTINGS.tagline.length))) {
			mark = el('div', 'doi-mark', cv);
			mark.setAttribute('aria-hidden', 'true');
			if (SETTINGS.wordmark) { word = el('p', 'doi-word', mark); word.textContent = SETTINGS.wordmark; }
			if (SETTINGS.tagline && SETTINGS.tagline.length) {
				tag = el('p', 'doi-tag', mark);
				SETTINGS.tagline.forEach((line, i) => { if (i) tag.appendChild(document.createElement('br')); tag.appendChild(document.createTextNode(line)); });
			}
		}
		return { cv, idle, lit, mark, word, tag };
	}

	// tall screens: a blurred copy of the wall above and below the photo (see CFG.fitWidth)
	const backdrop = el('div', 'doi-backdrop', world);
	const backdropImg = el('img', '', backdrop); backdropImg.alt = ''; backdropImg.decoding = 'async';

	const surround = el('div', 'doi-surround', world);
	const surCanvas = makeCanvas(surround, false);

	function makeLeaf(side) {
		const leaf = el('div', 'doi-leaf doi-leaf--' + side, world);
		const face = el('div', 'doi-face', leaf);
		const canvas = makeCanvas(face, true);
		const shade = el('div', 'doi-shade', face);
		const edge = el('div', 'doi-edge', leaf);
		return { spec: null, leaf, face, canvas, shade, edge };
	}
	const leafL = makeLeaf('l');
	const leafR = makeLeaf('r');

	// Point the scene at one door set: its photographs, cut-outs and measurements. Only the active set's
	// pictures are ever requested, so a phone never downloads the desktop photographs and vice versa.
	function applySet(name) {
		if (name === setName) return;
		setName = name;
		const S = SETS[name];
		Object.assign(CFG, S);
		for (const img of [surCanvas.idle, leafL.canvas.idle, leafR.canvas.idle]) img.src = S.idle;
		for (const img of [surCanvas.lit, leafL.canvas.lit, leafR.canvas.lit, backdropImg]) img.src = S.lit;
		surround.style.webkitMaskImage = surround.style.maskImage =
			svgMask('0 0 ' + S.img.w + ' ' + S.img.h, 'M0 0H' + S.img.w + 'V' + S.img.h + 'H0Z' + S.hole, true);
		leafL.spec = S.leafL;
		leafR.spec = S.leafR;
		for (const L of [leafL, leafR]) {
			L.face.style.webkitMaskImage = L.face.style.maskImage =
				svgMask('0 0 ' + (L.spec.x1 - L.spec.x0) + ' ' + (L.spec.y1 - L.spec.y0), L.spec.path, false);
		}
	}

	const seam = el('div', 'doi-seam', root);
	const bloom = el('div', 'doi-bloom', root);

	// A visible, labelled button inside role=dialog: it names the dialog's single action.
	const ui = el('div', 'doi-layer', root);
	const cta = el('div', 'doi-cta', ui);
	const btn = el('button', 'doi-btn', cta);
	btn.type = 'button';
	const btnLabel = el('span', '', btn);
	btnLabel.textContent = SETTINGS.button || 'Enter';
	if (SETTINGS.buttonBg) btn.style.setProperty('--doi-btn-bg', SETTINGS.buttonBg);
	let caption = null;
	if (SETTINGS.caption) { caption = el('p', 'doi-caption', cta); caption.textContent = SETTINGS.caption; }

	let skipBtn = null;
	if (SETTINGS.skip) {
		skipBtn = el('button', 'doi-skip', root);
		skipBtn.type = 'button';
		skipBtn.textContent = SETTINGS.skipLabel || 'Skip intro';
	}

	const litImgs = [surCanvas.lit, leafL.canvas.lit, leafR.canvas.lit];
	const idleImgs = [surCanvas.idle, leafL.canvas.idle, leafR.canvas.idle];

	// --------------------------------------------- the page underneath ---------------------------------------------
	// The theme's own top-level elements, loaded normally. They're made inert while the door covers them, and are
	// scaled together about one point as the camera walks in, which reads as a single plane further back.
	const NON_VISUAL = /^(SCRIPT|STYLE|LINK|META|NOSCRIPT|TEMPLATE|TITLE|BASE)$/;
	let pageEls = [];
	let pageScaled = false;

	function collectPage() {
		pageEls = Array.prototype.filter.call(document.body.children, n =>
			n !== root && !n.classList.contains('doi-cover') && !NON_VISUAL.test(n.tagName)
		).map(n => ({
			el: n,
			o: '',
			prev: { transform: n.style.transform, transformOrigin: n.style.transformOrigin, willChange: n.style.willChange, transition: n.style.transition },
			inert: n.inert === true,
		}));
		// the engine drives these every frame; a theme's own transitions (e.g. `* { transition: all }`) would make
		// them lag behind the camera, so they're off until the page is handed back
		for (const p of pageEls) p.el.style.transition = 'none';
	}

	// apply a style change instantly even if the theme animates that property, then hand transitions back
	function withoutTransition(node, change) {
		const prev = node.style.transition;
		node.style.transition = 'none';
		change();
		void node.offsetWidth;
		node.style.transition = prev;
	}

	function resetPageScale() {
		for (const p of pageEls) {
			p.el.style.transform = p.prev.transform;
			p.el.style.transformOrigin = p.prev.transformOrigin;
			p.el.style.willChange = p.prev.willChange;
		}
		pageScaled = false;
	}

	function measurePage() {
		if (pageScaled) resetPageScale();
		// grown from the top-center of the screen rather than the vanishing point: the page's top edge then never
		// drops into view, and its side edges stay outside the doorway until it lands at 1:1
		for (const p of pageEls) {
			const r = p.el.getBoundingClientRect();
			p.o = (G.fx - r.left) + 'px ' + (-r.top) + 'px';
		}
	}

	function setPageScale(s) {
		if (s >= 1) { if (pageScaled) resetPageScale(); return; }
		for (const p of pageEls) {
			p.el.style.transformOrigin = p.o;
			p.el.style.transform = 'scale(' + s + ')';
			if (!pageScaled) p.el.style.willChange = 'transform';
		}
		pageScaled = true;
	}

	function setPageInert(on) {
		for (const p of pageEls) {
			if ('inert' in p.el) p.el.inert = on ? true : p.inert;
		}
	}

	// Scrolling is locked during the intro, which hides the scrollbar. On systems with classic scrollbars the page
	// is given that width back as padding while it's hidden, so it's already at its final width when the scrollbar
	// returns: nothing shifts at the moment of the reveal.
	let prevHtmlPadding = null;
	function holdScrollbarSpace() {
		if (document.documentElement.scrollHeight <= window.innerHeight) return;
		const probe = el('div', '', document.body);
		probe.style.cssText = 'position:absolute;top:-9999px;left:0;width:100px;height:100px;overflow:scroll;visibility:hidden';
		const w = probe.offsetWidth - probe.clientWidth;
		probe.remove();
		if (w > 0) {
			prevHtmlPadding = html.style.paddingRight;
			withoutTransition(html, () => { html.style.paddingRight = w + 'px'; });
		}
	}
	function releaseScrollbarSpace() {
		if (prevHtmlPadding !== null) {
			const prev = prevHtmlPadding;
			withoutTransition(html, () => { html.style.paddingRight = prev; });
		}
		prevHtmlPadding = null;
	}

	// ------------------------------------------------ layout ------------------------------------------------
	let G = null;
	let particles = [];

	// Is a point (source-photo px) inside the wall's opening? The opening is convex, so a rectangle
	// is inside it exactly when its four corners are.
	function inOpening(x, y) {
		const o = CFG.opening;
		if (y > o.bottom) return false;
		if (y >= o.springY) {
			const f = (y - o.springY) / (o.bottom - o.springY);
			return x >= o.left[0] + (o.left[1] - o.left[0]) * f && x <= o.right[0] + (o.right[1] - o.right[0]) * f;
		}
		const u = (x - o.cx) / o.rx, v = (o.springY - y) / o.ry;
		return u * u + v * v <= 1;
	}

	// The camera zoom (about the focus) at which the doorway first frames the whole screen.
	// Past it, the wall and the leaves are off-screen and only the page is visible.
	function clearScale() {
		const corners = [[0, 0], [G.vw, 0], [0, G.vh], [G.vw, G.vh]];
		const clear = S => corners.every(c =>
			inOpening(CFG.focus[0] + (c[0] - G.fx) / (G.c * S), CFG.focus[1] + (c[1] - G.fy) / (G.c * S)));
		let lo = 1, hi = 12;
		if (!clear(hi)) return hi;
		for (let i = 0; i < 40; i++) { const m = (lo + hi) / 2; if (clear(m)) hi = m; else lo = m; }
		return hi;
	}

	// When things happen, in ms after the click. The glow only visibly changes the door's engraving; once that is
	// fully lit (the top of the arch is the farthest point from the emblem), the rest of its spread is over wall and
	// curtains that look the same lit or unlit, so waiting for it would read as a pause. The door opens right then.
	let plan = null;
	function computePlan() {
		const far = Math.hypot(CFG.emblem[0] - CFG.seam, CFG.emblem[1] - CFG.leafL.y0) * G.c;
		const lit = CFG.T.ignite * easeInv(Math.min(1, (far + G.feather) / G.maxR));
		const openStart = Math.max(0, lit - CFG.T.overlap);
		const camStart = openStart + CFG.T.camDelay;
		return { openStart, camStart, end: camStart + CFG.T.camDur };
	}

	function layout() {
		const vw = window.innerWidth, vh = window.innerHeight;
		applySet(pickSet(vw, vh));
		const W = CFG.img.w, H = CFG.img.h;
		const cover = Math.max(vw / W, vh / H);
		// wide screens: `cover`, as before. Tall screens: fit CFG.fitWidth of the photo across the screen.
		const c = Math.min(cover, vw / CFG.fitWidth);
		const ox = (vw - W * c) / 2, oy = (vh - H * c) / 2;
		const P = CFG.persp * W * c;
		const fx = ox + CFG.focus[0] * c, fy = oy + CFG.focus[1] * c;
		const ex = CFG.emblem[0] * c, ey = CFG.emblem[1] * c; // canvas-local
		const exs = ox + ex, eys = oy + ey;                    // on screen
		const maxR = Math.max.apply(null, [[0, 0], [vw, 0], [0, vh], [vw, vh]].map(p => Math.hypot(p[0] - exs, p[1] - eys))) * 1.02;
		// the name and tagline light up as the glow's edge reaches the name
		G = { vw, vh, c, ox, oy, P, fx, fy, ex, ey, maxR, feather: maxR * 0.12, textDist: (CFG.emblem[1] - CFG.word.y) * c };
		G.sClear = clearScale();
		G.sEnd = Math.min(8, G.sClear * CFG.overshoot);
		if (state === 'idle') plan = computePlan();

		// blurred wall above and below the photo when it doesn't cover the screen; it never shows through the
		// photo itself (its mask cuts the photo's box out, 2px inside so there's no hairline between them)
		const gap = c < cover - 1e-6;
		backdrop.style.display = gap ? '' : 'none';
		if (gap) {
			const bw = W * cover + 120, bh = H * cover + 120;
			const bl = (vw - bw) / 2, bt = (vh - bh) / 2;
			Object.assign(backdrop.style, { left: bl + 'px', top: bt + 'px', width: bw + 'px', height: bh + 'px' });
			const hx = ox - bl + 2, hy = oy - bt + 2, hw = W * c - 4, hh = H * c - 4;
			backdrop.style.webkitMaskImage = backdrop.style.maskImage = svgMask('0 0 ' + bw + ' ' + bh,
				'M0 0H' + bw + 'V' + bh + 'H0Z' + 'M' + hx + ' ' + hy + 'H' + (hx + hw) + 'V' + (hy + hh) + 'H' + hx + 'Z', true);
		}

		root.style.setProperty('--doi-fx', fx + 'px');
		root.style.setProperty('--doi-fy', fy + 'px');
		for (const cam of [front, bcam]) {
			cam.style.perspective = P + 'px';
			cam.style.perspectiveOrigin = fx + 'px ' + fy + 'px';
		}

		const placeCanvas = (cv, dx, dy) => {
			cv.style.left = -dx + 'px'; cv.style.top = -dy + 'px';
			cv.style.width = W * c + 'px'; cv.style.height = H * c + 'px';
		};
		surround.style.left = ox + 'px'; surround.style.top = oy + 'px';
		surround.style.width = W * c + 'px'; surround.style.height = H * c + 'px';
		placeCanvas(surCanvas.cv, 0, 0);

		for (const L of [leafL, leafR]) {
			const s = L.spec;
			const st = L.leaf.style;
			st.left = ox + s.x0 * c + 'px'; st.top = oy + s.y0 * c + 'px';
			st.width = (s.x1 - s.x0) * c + 'px'; st.height = (s.y1 - s.y0) * c + 'px';
			st.transformOrigin = (s.hinge - s.x0) * c + 'px 50%';
			placeCanvas(L.canvas.cv, s.x0 * c, s.y0 * c);
			const t = CFG.thickness * c;
			const at = (CFG.seam - s.x0) * c;
			L.edge.style.width = t + 'px';
			L.edge.style.left = (L === leafL ? at : at - t) + 'px';
			const m = L.canvas;
			if (m.mark) {
				m.mark.style.top = (CFG.word.y - CFG.word.size / 2) * c + 'px';
				if (m.word) m.word.style.fontSize = CFG.word.size * c + 'px';
				if (m.tag) {
					m.tag.style.fontSize = CFG.tag.size * c + 'px';
					m.tag.style.marginTop = (m.word ? CFG.tag.y - CFG.word.y - CFG.word.size / 2 : CFG.tag.y - CFG.word.y + CFG.word.size / 2) * c + 'px';
				}
			}
		}

		seam.style.left = fx - 2 + 'px';
		seam.style.top = oy + CFG.leafL.y0 * c + 'px';
		seam.style.height = (CFG.leafL.y1 - CFG.leafL.y0) * c + 'px';
		cta.style.left = fx + 'px';
		cta.style.top = oy + CFG.cta.y * c + 'px';
		cta.style.fontSize = Math.max(11, Math.min(16, CFG.cta.size * c)) + 'px';
		if (caption) caption.style.fontSize = Math.max(19, 19 * c) + 'px';

		measurePage();
		buildParticles();
	}

	function buildParticles() {
		for (const p of particles) p.node.remove();
		particles = [];
		const rnd = mulberry32(7);
		const { c, ox, oy, P, fx, fy } = G;
		for (let i = 0; i < CFG.particles; i++) {
			// a point inside the doorway (source-photo px), biased toward the lower, brighter half
			const o = CFG.opening;
			const yTop = o.springY - o.ry * 0.7, yBot = o.bottom - 30;
			let x, y;
			for (;;) {
				x = o.cx - o.rx * 0.84 + rnd() * o.rx * 1.68;
				y = yTop + Math.pow(rnd(), 0.8) * (yBot - yTop);
				if (y >= o.springY) break;
				const half = o.rx * Math.sqrt(1 - Math.pow((o.springY - y) / o.ry, 2)) - o.rx * 0.09;
				if (Math.abs(x - o.cx) < half) break;
			}
			const z = -(0.05 + rnd() * 1.15) * P;    // depth behind the door plane
			const k = (P - z) / P;                    // un-project so it starts exactly where we aimed
			const sx = ox + x * c, sy = oy + y * c;
			const node = el('div', 'doi-p', bworld);
			const size = (13 + rnd() * 26) * c * k;
			node.style.width = node.style.height = size + 'px';
			particles.push({
				node, size, z,
				wx: fx + (sx - fx) * k, wy: fy + (sy - fy) * k,
				rise: (14 + rnd() * 38) * c * k,
				amp: (4 + rnd() * 10) * c * k,
				w: 0.6 + rnd() * 1.1, ph: rnd() * 6.28,
				alpha: 0.55 + rnd() * 0.45,
				delay: rnd() * 700,
			});
		}
	}

	// ------------------------------------------ one frame of the timeline ------------------------------------------
	// A pure function of t (ms since the click); t < 0 is the resting door.
	let maskOff = null;
	function setIgnition(radius) {
		const off = radius === Infinity;
		if (off) {
			if (maskOff !== true) {
				for (const img of litImgs) img.style.webkitMaskImage = img.style.maskImage = 'none';
				for (const img of idleImgs) img.style.visibility = 'hidden';
			}
			maskOff = true;
			return;
		}
		maskOff = false;
		for (const img of idleImgs) img.style.visibility = '';
		// never a zero-size gradient: some browsers treat 0/0/0 stops as "no mask" and show everything
		const r = Math.max(radius, 0.5);
		const inner = Math.max(0, r - G.feather);
		const outer = Math.max(inner + 0.5, r);
		const g = 'radial-gradient(circle at ' + G.ex + 'px ' + G.ey + 'px, #000 0, #000 ' + inner + 'px, transparent ' + outer + 'px)';
		for (const img of litImgs) img.style.webkitMaskImage = img.style.maskImage = g;
	}

	let revealedSent = false;
	function render(t) {
		const T = Object.assign({}, CFG.T, plan);

		// 1 — ignition: the glow spreads out from the emblem
		const ig = t < 0 ? 0 : ease(clamp01(t / T.ignite));
		setIgnition(t >= T.ignite ? Infinity : ig * G.maxR);
		root.classList.toggle('is-lit', t >= 0 && ig * G.maxR >= G.textDist);

		// 2 — the button steps aside
		const b = t < 0 ? 0 : smooth(t / 380);
		ui.style.opacity = String(1 - b);
		cta.style.transform = 'translate(-50%,-50%) scale(' + (1 - 0.05 * b) + ')';
		ui.style.visibility = b >= 1 ? 'hidden' : '';

		// 3 — the leaves swing open on their hinges
		const po = clamp01((t - T.openStart) / T.openDur);
		// ease-out: the leaves move from the very first frame (no slow wind-up) and settle as they reach full swing
		const th = Math.sin(po * Math.PI / 2) * CFG.openDeg;
		const dir = CFG.direction === 'out' ? 1 : -1;
		const sn = Math.sin(th * Math.PI / 180);
		leafL.leaf.style.transform = 'translateZ(0.5px) rotateY(' + (-dir * th) + 'deg)';
		leafR.leaf.style.transform = 'translateZ(0.6px) rotateY(' + (dir * th) + 'deg)';
		for (const L of [leafL, leafR]) {
			L.shade.style.opacity = String(0.85 * sn);
			L.edge.style.filter = 'brightness(' + (0.8 + 0.55 * sn) + ')';
		}

		// 4 — the camera walks through the doorway while it opens
		const pc = clamp01((t - T.camStart) / T.camDur);
		const S = Math.exp(Math.log(G.sEnd) * ease(pc));
		const d = G.P * (1 - 1 / S);
		world.style.transform = 'translateZ(' + d + 'px)';
		bworld.style.transform = 'translateZ(' + d + 'px)';
		// once the doorway frames the whole screen, the wall and the swung-out leaves are all off-screen
		const through = S >= G.sClear * 1.01;
		leafL.leaf.style.visibility = leafR.leaf.style.visibility = through ? 'hidden' : '';
		surround.style.visibility = through ? 'hidden' : '';

		// 5 — the page is a plane further back, so it grows more slowly; it lands at exactly 1:1 the moment the
		//     doorway clears the screen. Before the door opens it isn't visible, so it's left untouched.
		const sPage = Math.min(1, (CFG.depth + 1 / G.sClear) / (CFG.depth + 1 / S));
		setPageScale(po > 0 && t < T.end ? sPage : 1);
		if (pc >= 0.82 && !revealedSent) {
			revealedSent = true;
			window.dispatchEvent(new CustomEvent('doi:revealing'));
		}

		// 6 — light: a crack of light at the seam, a flood through the gap, then it clears into the room
		seam.style.opacity = String(smooth((t - (T.openStart - 380)) / 380) * (1 - smooth(th / 22)));
		const fade = clamp01((pc - 0.25) / 0.75);
		room.style.opacity = String(po > 0 ? 0.93 * (1 - Math.pow(fade, 1.6)) : 0);
		const bump = Math.exp(-Math.pow((pc - 0.36) / 0.17, 2));
		bloom.style.opacity = String((0.46 * smooth(th / 55) + 0.3 * bump * (pc > 0 ? 1 : 0)) * (1 - smooth((pc - 0.5) / 0.42)));
		if (skipBtn) skipBtn.style.visibility = pc >= 0.8 ? 'hidden' : '';

		// 7 — bubbles drift up in the light and stream past as we enter
		const pa = smooth((t - (T.openStart + 250)) / 600) * (1 - smooth((pc - 0.66) / 0.3));
		const secs = Math.max(0, t - T.openStart) / 1000;
		for (const p of particles) {
			const a = pa * p.alpha * smooth((t - T.openStart - 250 - p.delay) / 500);
			p.node.style.opacity = String(a * (0.78 + 0.22 * Math.sin(secs * 2.1 + p.ph)));
			if (a <= 0) continue;
			const x = p.wx + p.amp * Math.sin(secs * p.w + p.ph) - p.size / 2;
			const y = p.wy - p.rise * secs - p.size / 2;
			p.node.style.transform = 'translate3d(' + x + 'px,' + y + 'px,' + p.z + 'px)';
		}
	}

	// ------------------------------------------------ playback ------------------------------------------------
	let state = 'idle'; // idle → playing → done  (or → leaving → done when skipped)
	let raf = 0, t0 = 0, now = -1;
	let usedKeyboard = false;

	function whenLoaded(img, ms) {
		if (img.complete && img.naturalWidth) return Promise.resolve();
		const loaded = new Promise(resolve => {
			img.addEventListener('load', resolve, { once: true });
			img.addEventListener('error', resolve, { once: true });
		});
		return Promise.race([loaded, new Promise(resolve => setTimeout(resolve, ms))]);
	}

	function onKey(e) {
		usedKeyboard = true;
		if (e.key === 'Escape') skip();
	}
	function onResize() {
		if (state === 'leaving') return;
		layout();
		render(state === 'playing' ? now : -1);
	}

	function mount() {
		if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
		window.scrollTo(0, 0);
		holdScrollbarSpace();
		document.body.appendChild(root);
		collectPage();
		setPageInert(true);
		layout();
		render(-1);
		window.addEventListener('keydown', onKey);
		window.addEventListener('resize', onResize);
		btn.addEventListener('click', play);
		front.style.cursor = 'pointer';
		front.addEventListener('click', play);
		if (skipBtn) skipBtn.addEventListener('click', skip);

		// swap the loading still for the live door once its pictures are ready (same picture, same framing)
		whenLoaded(surCanvas.idle, 3000).then(() => {
			root.style.visibility = '';
			html.classList.add('doi-ready');
			const cover = document.querySelector('.doi-cover');
			if (cover) cover.remove();
		});
	}

	function play() {
		if (state !== 'idle') return;
		state = 'playing';
		window.dispatchEvent(new CustomEvent('doi:open'));
		if (reduceMotion) {
			render(plan.openStart);
			setTimeout(skip, 350);
			return;
		}
		whenLoaded(surCanvas.lit, 2500).then(() => {
			if (state !== 'playing') return;
			t0 = performance.now();
			const tick = time => {
				now = time - t0;
				render(now);
				if (now >= plan.end + 60) finish();
				else raf = requestAnimationFrame(tick);
			};
			raf = requestAnimationFrame(tick);
		});
	}

	function skip() {
		if (state === 'done' || state === 'leaving') return;
		state = 'leaving';
		cancelAnimationFrame(raf);
		root.style.transition = 'opacity .45s ease';
		root.style.opacity = '0';
		if (pageScaled) {
			for (const p of pageEls) p.el.style.transition = 'transform .45s ease';
			resetPageScale();
		}
		setTimeout(finish, 470);
	}

	function finish() {
		if (state === 'done') return;
		state = 'done';
		cancelAnimationFrame(raf);
		resetPageScale();
		void document.body.offsetWidth; // commit the page at 1:1 before its own transitions come back
		for (const p of pageEls) p.el.style.transition = p.prev.transition;
		setPageInert(false);
		window.removeEventListener('keydown', onKey);
		window.removeEventListener('resize', onResize);
		const refocus = usedKeyboard && root.contains(document.activeElement);
		root.remove();
		const cover = document.querySelector('.doi-cover');
		if (cover) cover.remove();
		html.classList.remove('doi-on', 'doi-ready');
		releaseScrollbarSpace();
		if ('scrollRestoration' in history) history.scrollRestoration = 'auto';
		if (SETTINGS.once) { try { window.sessionStorage.setItem('doi_seen', '1'); } catch (e) { /* storage blocked */ } }
		// a session cookie (no expiry) is shared by every tab and cleared when the browser is closed
		if (SETTINGS.onceBrowser) {
			document.cookie = 'doi_seen=1; path=' + (SETTINGS.cookiePath || '/') + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		}

		// keyboard users land at the start of the page's content instead of nowhere
		if (refocus) {
			const main = document.querySelector('main, [role="main"], #main, #content, #primary');
			if (main) {
				if (!main.hasAttribute('tabindex')) {
					main.setAttribute('tabindex', '-1');
					main.addEventListener('blur', () => main.removeAttribute('tabindex'), { once: true });
				}
				main.focus({ preventScroll: true });
			}
		}
		// the page now scrolls; let sliders, sticky headers and the like re-measure
		window.dispatchEvent(new Event('resize'));
		window.dispatchEvent(new CustomEvent('doi:revealed'));
	}

	mount();
})();
