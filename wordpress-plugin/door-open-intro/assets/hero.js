(function (d, w) {
	var cfg = w.DOI_HERO;
	if (!cfg) return;

	function ready(fn) {
		if (d.readyState !== 'loading') fn();
		else d.addEventListener('DOMContentLoaded', fn);
	}

	ready(function () {
		var track = d.querySelector('.doi-hero-track');
		var stage = track && track.querySelector('.doi-hero-stage');
		if (!track || !stage) return;

		var canvas = d.createElement('canvas');
		canvas.className = 'doi-hero-canvas';
		canvas.setAttribute('aria-hidden', 'true');
		var fade = d.createElement('div');
		fade.className = 'doi-hero-fade';
		var cue = d.createElement('div');
		cue.className = 'doi-hero-cue';
		cue.setAttribute('aria-hidden', 'true');
		cue.textContent = cfg.cue || 'Scroll';
		stage.appendChild(canvas);
		stage.appendChild(fade);
		stage.appendChild(cue);
		track.classList.add('doi-hero-on');

		var ctx = canvas.getContext('2d');
		var reduced = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var N = 0;
		var set = null;
		var frames = [];
		var ok = [];
		var gen = 0;
		var started = false;
		var cur = 0;
		var tgt = 0;
		var progress = 0;
		var shown = -1;
		var raf = 0;

		function pickSet() {
			return w.innerWidth / w.innerHeight <= 0.75 ? 'mobile' : 'desktop';
		}
		function useSet(s) {
			set = s;
			N = cfg.sets[s].count;
		}
		function src(i) {
			var S = cfg.sets[set];
			var n = String(i + 1);
			while (n.length < 3) n = '0' + n;
			return S.base + n + '.' + S.ext;
		}
		function smooth(t) {
			t = t < 0 ? 0 : t > 1 ? 1 : t;
			return t * t * (3 - 2 * t);
		}

		// Coarse to fine: the first and last frames, then every 16th, 8th, ... so a rough version of the whole
		// walk can be scrubbed almost at once and fills in while the visitor is still at the door.
		function loadOrder() {
			var order = [0, N - 1];
			var seen = {};
			seen[0] = seen[N - 1] = true;
			for (var step = 16; step >= 1; step /= 2) {
				for (var i = 0; i < N; i += step) {
					if (!seen[i]) {
						seen[i] = true;
						order.push(i);
					}
				}
			}
			return order;
		}

		function load() {
			var mine = ++gen;
			var queue = loadOrder();
			var active = 0;
			frames = new Array(N);
			ok = new Array(N);
			function next() {
				while (active < 6 && queue.length && mine === gen) {
					fetchFrame(queue.shift());
				}
			}
			function fetchFrame(i) {
				var img = new Image();
				img.decoding = 'async';
				// the ends of the walk first; the in-between frames never compete with the door's photographs
				img.fetchPriority = i === 0 || i === N - 1 ? 'high' : 'low';
				active++;
				img.onload = function () {
					active--;
					if (mine !== gen) return;
					ok[i] = true;
					schedule();
					next();
				};
				img.onerror = function () {
					active--;
					next();
				};
				img.src = src(i);
				frames[i] = img;
			}
			next();
		}

		function nearest(i) {
			if (ok[i]) return i;
			for (var k = 1; k < N; k++) {
				if (i - k >= 0 && ok[i - k]) return i - k;
				if (i + k < N && ok[i + k]) return i + k;
			}
			return -1;
		}

		// Backing store no finer than the frames themselves: past that, extra pixels add memory, not detail.
		function size() {
			var sw = stage.clientWidth;
			var sh = stage.clientHeight;
			var S = cfg.sets[set];
			var cover = Math.max(sw / S.w, sh / S.h);
			var k = Math.max(1, Math.min(w.devicePixelRatio || 1, 2, 1 / cover));
			var cw = Math.round(sw * k);
			var ch = Math.round(sh * k);
			if (canvas.width !== cw || canvas.height !== ch) {
				canvas.width = cw;
				canvas.height = ch;
				shown = -1;
			}
		}

		function draw() {
			var i = nearest(Math.round(cur));
			if (i < 0 || i === shown) return;
			var img = frames[i];
			var cw = canvas.width;
			var ch = canvas.height;
			var s = Math.max(cw / img.naturalWidth, ch / img.naturalHeight);
			var dw = img.naturalWidth * s;
			var dh = img.naturalHeight * s;
			ctx.drawImage(img, (cw - dw) / 2, (ch - dh) / 2, dw, dh);
			shown = i;
		}

		function measure() {
			var total = track.offsetHeight - stage.offsetHeight;
			var top = track.getBoundingClientRect().top;
			progress = total > 0 ? Math.min(1, Math.max(0, -top / total)) : 0;
			tgt = progress * (N - 1);
		}

		function tick() {
			raf = 0;
			var delta = tgt - cur;
			cur = reduced || Math.abs(delta) < 0.05 ? tgt : cur + delta * 0.18;
			draw();
			fade.style.opacity = String(smooth((progress - 0.8) / 0.2));
			cue.classList.toggle('is-hidden', progress > 0.02);
			if (cur !== tgt) schedule();
		}
		function schedule() {
			if (!raf) raf = w.requestAnimationFrame(tick);
		}

		function onScroll() {
			measure();
			schedule();
		}
		function onResize() {
			var s = pickSet();
			var switched = s !== set;
			if (switched) {
				useSet(s);
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				shown = -1;
				if (started) load();
			}
			size();
			measure();
			if (switched) cur = tgt;
			schedule();
		}

		useSet(pickSet());
		size();
		measure();
		w.addEventListener('scroll', onScroll, { passive: true });
		w.addEventListener('resize', onResize);

		// The door's photographs are fetched first; the walk's frames follow as soon as the door is showing (or is
		// opened, or skipped), while the visitor is still looking at it. Waiting for the whole page to load left the
		// corridor half-fetched, and seemingly frozen, right after the door opened on a slow connection.
		function start() {
			if (started) return;
			started = true;
			load();
		}
		var html = d.documentElement;
		if (!html.classList.contains('doi-on')) {
			start();
		} else {
			w.addEventListener('doi:open', start, { once: true });
			var wait = w.setInterval(function () {
				if (html.classList.contains('doi-ready') || !html.classList.contains('doi-on')) {
					w.clearInterval(wait);
					start();
				}
			}, 150);
		}
		schedule();
	});
})(document, window);
