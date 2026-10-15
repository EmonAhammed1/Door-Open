/* DRUMI portal finder: a few questions in a dialog that suggest the theme (portal) that speaks to the visitor right
   now, say why, and offer the next step: the journal for Awakening, or the waitlist (with that portal chosen) for a
   future one. Any link to #find-your-portal opens it. Text and questions come from includes/home.php
   (window.DOI_FINDER). */
(function (d, w) {
	var cfg = w.DOI_FINDER;
	if (!cfg) return;

	function ready(fn) {
		if (d.readyState !== 'loading') fn();
		else d.addEventListener('DOMContentLoaded', fn);
	}

	function el(tag, cls, text) {
		var n = d.createElement(tag);
		if (cls) n.className = cls;
		if (text != null) n.textContent = text;
		return n;
	}
	function fill(tpl) {
		var args = Array.prototype.slice.call(arguments, 1);
		var k = 0;
		return tpl.replace(/%(\d+\$)?s/g, function (m, pos) {
			return String(pos ? args[parseInt(pos, 10) - 1] : args[k++]);
		});
	}
	var arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

	ready(function () {
		var dialog = d.getElementById('find-your-portal');
		if (!dialog || typeof dialog.showModal !== 'function') return;
		// the dialog lives in the page builder's section; at the end of <body> nothing around it can clip or tint it
		d.body.appendChild(dialog);
		var body = dialog.querySelector('.doi-finder-body');
		var T = cfg.text;
		var Q = cfg.questions;
		var answers = [];

		function score() {
			var pts = cfg.portals.map(function () { return 0; });
			answers.forEach(function (a, qi) {
				var add = Q[qi].a[a][1];
				Object.keys(add).forEach(function (p) {
					// the first answer is what is on their mind: it settles a tie
					if (pts[p] != null) pts[p] += add[p] + (qi === 0 ? 0.1 : 0);
				});
			});
			var best = 0;
			pts.forEach(function (v, i) {
				if (v > pts[best]) best = i;
			});
			return best;
		}

		function swap(build) {
			body.classList.add('is-swapping');
			w.setTimeout(function () {
				body.textContent = '';
				build();
				body.classList.remove('is-swapping');
				var focus = body.querySelector('.doi-finder-option, .doi-finder-result-name');
				if (focus) focus.focus({ preventScroll: true });
			}, body.childNodes.length ? 220 : 0);
		}

		function question(i) {
			swap(function () {
				var head = el('div', 'doi-finder-head');
				head.appendChild(el('p', 'doi-finder-kicker', fill(T.step, i + 1, Q.length)));
				var dots = el('div', 'doi-finder-progress');
				Q.forEach(function (_, k) {
					dots.appendChild(el('span', k <= i ? 'is-done' : ''));
				});
				head.appendChild(dots);
				body.appendChild(head);
				var h = el('h2', 'doi-finder-title', Q[i].q);
				h.id = 'doi-finder-title';
				body.appendChild(h);
				var list = el('div', 'doi-finder-options');
				Q[i].a.forEach(function (a, k) {
					var b = el('button', 'doi-finder-option', a[0]);
					b.type = 'button';
					if (answers[i] === k) b.classList.add('is-chosen');
					b.addEventListener('click', function () {
						answers[i] = k;
						answers.length = i + 1;
						if (i + 1 < Q.length) question(i + 1);
						else result();
					});
					list.appendChild(b);
				});
				body.appendChild(list);
				if (i > 0) {
					var back = el('button', 'doi-finder-back', T.back);
					back.type = 'button';
					back.addEventListener('click', function () { question(i - 1); });
					body.appendChild(back);
				}
			});
		}

		function link(cls, text, href) {
			var a = el('a', cls);
			a.href = href;
			a.innerHTML = '<span></span>' + arrow;
			a.firstChild.textContent = text;
			// in-page steps (the journal, the waitlist) close the dialog first so the page can scroll to them
			if (href.charAt(0) === '#') a.addEventListener('click', function () { dialog.close(); });
			return a;
		}

		// the shared waitlist form, with this portal chosen (portals.js listens for the event and scrolls there)
		function join(portal) {
			var a = link('doi-finder-cta', T.join, cfg.waitlist);
			a.addEventListener('click', function (e) {
				e.preventDefault();
				dialog.close();
				w.dispatchEvent(new CustomEvent('drumi:waitlist', { detail: { portal: portal, href: cfg.waitlist } }));
			});
			return a;
		}

		function result() {
			var p = cfg.portals[score()];
			swap(function () {
				var res = el('div', 'doi-finder-result');
				res.style.setProperty('--pc', p.color || '#e8c98f');
				res.appendChild(el('span', 'doi-finder-arch'));
				res.appendChild(el('p', 'doi-finder-kicker', T.kicker));
				var name = el('h2', 'doi-finder-result-name', p.name);
				name.id = 'doi-finder-title';
				name.tabIndex = -1;
				res.appendChild(name);
				if (p.label) res.appendChild(el('p', 'doi-finder-status', p.label));
				if (p.desc) res.appendChild(el('p', 'doi-finder-desc', p.desc));

				var why = el('div', 'doi-finder-why');
				why.appendChild(el('p', 'doi-finder-label', T.shared));
				var ul = el('ul', 'doi-finder-shared');
				answers.forEach(function (a, qi) {
					ul.appendChild(el('li', '', Q[qi].a[a][0]));
				});
				why.appendChild(ul);
				if (p.reason) {
					why.appendChild(el('p', 'doi-finder-label', T.why));
					why.appendChild(el('p', 'doi-finder-reason', p.reason));
				}
				res.appendChild(why);

				var next = el('div', 'doi-finder-next');
				if (p.later) {
					// a future journal: its waitlist with this portal chosen, nothing to buy; Awakening stays the way to begin
					next.appendChild(join(p.name));
					if (cfg.first) next.appendChild(link('doi-finder-cta is-quiet', fill(T.begin, cfg.first.name), cfg.first.url));
				} else {
					next.appendChild(link('doi-finder-cta', T.journal, p.url || cfg.journal));
				}
				res.appendChild(next);

				var again = el('button', 'doi-finder-back', T.again);
				again.type = 'button';
				again.addEventListener('click', function () {
					answers = [];
					question(0);
				});
				res.appendChild(again);
				body.appendChild(res);
			});
		}

		function open(e) {
			if (e) e.preventDefault();
			answers = [];
			body.textContent = '';
			question(0);
			dialog.showModal();
			d.documentElement.classList.add('doi-finder-open');
		}

		dialog.addEventListener('close', function () {
			d.documentElement.classList.remove('doi-finder-open');
		});
		dialog.querySelector('.doi-finder-close').addEventListener('click', function () { dialog.close(); });
		// a click on the dimmed backdrop (outside the card) closes it
		dialog.addEventListener('click', function (e) {
			if (e.target === dialog) dialog.close();
		});
		d.addEventListener('click', function (e) {
			var a = e.target.closest && e.target.closest('a[href$="#find-your-portal"]');
			if (a) open(e);
		});
		if (w.location.hash === '#find-your-portal') open();
	});
})(document, window);
