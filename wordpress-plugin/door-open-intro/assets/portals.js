/* DRUMI portals: a ring of doors that turns. The active door stands in the middle; its name and button sit under
   the ring and change with it. Turn it with the arrows, the dots, a swipe or drag, the keyboard arrows, or by
   clicking a side door. Clicking the active door (or its button) swings it open and the golden iris carries the
   visitor through; a future portal stays closed and its button opens the waitlist with that portal chosen. The ring
   has no ends: after the last journey comes the first again. */
(function (d, w) {
	var reduced = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var OPEN_MS = 950; // the leaves swing all the way open (matches .is-entering in portals.css)…
	var IRIS_MS = 650; // …and only then does the golden light grow from the doorway to fill the screen
	var SCALE = 0.8; // each step away from the middle, a door is this much smaller

	function ready(fn) {
		if (d.readyState !== 'loading') fn();
		else d.addEventListener('DOMContentLoaded', fn);
	}
	function mod(a, n) {
		return ((a % n) + n) % n;
	}
	function plainClick(e) {
		return e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0;
	}

	// swing the portal fully open first, then grow the golden iris from the doorway, and only then go
	function go(portal, href, from) {
		if (reduced) {
			w.location.href = href;
			return;
		}
		var r = from.getBoundingClientRect();
		d.documentElement.style.setProperty('--jt-x', ((r.left + r.width / 2) / w.innerWidth) * 100 + '%');
		d.documentElement.style.setProperty('--jt-y', ((r.top + r.height / 2) / w.innerHeight) * 100 + '%');
		portal.classList.add('is-entering');
		w.setTimeout(function () {
			d.documentElement.classList.add('doi-jt-on');
			try { w.sessionStorage.setItem('doiJT', '1'); } catch (err) {}
		}, OPEN_MS);
		w.setTimeout(function () {
			w.location.href = href;
		}, OPEN_MS + IRIS_MS);
	}

	// The waitlist is one shared form (the homepage's #waitlist section) with a "Portal" field. A future portal's
	// button, or the finder's result, sends this event: the form gets that portal chosen, every .drumi-wl-portal names
	// it, and the page scrolls to the form. After signing up, .drumi-wl-confirm appears with {portal} filled in.
	function joinWaitlist(name, href) {
		w.dispatchEvent(new CustomEvent('drumi:waitlist', { detail: { portal: name, href: href } }));
	}

	function waitlist() {
		var field = d.querySelector('select[name="form_fields[portal]"]');
		var names = d.querySelectorAll('.drumi-wl-portal');
		var confirm = d.querySelector('.drumi-wl-confirm');
		var chosen = field ? field.value : '';
		// the confirmation's own words, kept so {portal} can be filled in again for a second sign-up
		var texts = [];
		if (confirm) {
			var walker = d.createTreeWalker(confirm, NodeFilter.SHOW_TEXT);
			var node;
			while ((node = walker.nextNode())) texts.push([node, node.nodeValue]);
		}
		function choose(name) {
			if (field && name) {
				var known = Array.prototype.some.call(field.options, function (o) { return o.value === name; });
				if (known) field.value = name;
			}
			chosen = field ? field.value : name || chosen;
			Array.prototype.forEach.call(names, function (n) { n.textContent = chosen; });
			if (confirm) confirm.classList.remove('is-shown');
		}
		if (field) {
			d.documentElement.classList.add('drumi-wl-on');
			if (confirm) d.documentElement.classList.add('drumi-wl-confirm-on');
			field.addEventListener('change', function () { choose(field.value); });
			choose(field.value);
			// Elementor empties the form after a sign-up: remember which portal it was for
			field.form.addEventListener('submit', function () { chosen = field.value; }, true);
			if (w.jQuery) {
				w.jQuery(field.form).on('submit_success', function () {
					var name = chosen;
					w.setTimeout(function () { choose(name); if (confirm) confirm.classList.add('is-shown'); }, 0);
					texts.forEach(function (t) { t[0].nodeValue = t[1].split('{portal}').join(name); });
				});
			}
		}
		w.addEventListener('drumi:waitlist', function (e) {
			var detail = e.detail || {};
			choose(detail.portal);
			var href = detail.href || '#waitlist';
			var id = href.indexOf('#') >= 0 ? href.slice(href.indexOf('#') + 1) : '';
			var target = id && d.getElementById(id);
			if (!target) {
				w.location.href = href;
				return;
			}
			target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
			if (w.history.replaceState) w.history.replaceState(null, '', '#' + id);
		});
		// any other link to the waitlist can choose its entry too: give it the attribute data-portal="…" in Elementor
		// (Link > Custom attributes), e.g. "Explore DRUMI for professionals"
		d.addEventListener('click', function (e) {
			var a = e.target.closest && e.target.closest('a[data-portal]');
			if (!a || plainClick(e)) return;
			e.preventDefault();
			joinWaitlist(a.getAttribute('data-portal'), a.getAttribute('href'));
		});
	}

	function setup(root) {
		var track = root.querySelector('.doi-portals-track');
		var portals = Array.prototype.slice.call(root.querySelectorAll('.doi-portal'));
		var N = portals.length;
		if (!track || !N) return;
		var captionEl = root.querySelector('.doi-portals-caption');
		var labelEl = root.querySelector('.doi-portals-label');
		var nameEl = root.querySelector('.doi-portals-name');
		var descEl = root.querySelector('.doi-portals-desc');
		var waitUrl = root.getAttribute('data-waitlist-url') || '';
		var cta = root.querySelector('.doi-portals-cta');
		var ctaText = root.querySelector('.doi-portals-cta-text');
		var dots = Array.prototype.slice.call(root.querySelectorAll('.doi-portals-dot'));
		var prev = root.querySelector('.doi-portals-prev');
		var next = root.querySelector('.doi-portals-next');
		var active = mod(parseInt(root.getAttribute('data-start'), 10) || 0, N);
		var shift = 0; // part of a turn, while a finger or mouse is dragging the ring
		var W = 200;
		var G = 20;
		// on wide screens the doors stop shrinking two steps out and carry on at that size to the edges; on phones and
		// tablets they keep shrinking and only the nearest are shown
		var wide = w.matchMedia ? w.matchMedia('(min-width: 1025px)') : { matches: false };
		var minScale = 0;
		var reach = 2.75;

		function measure() {
			W = portals[0].offsetWidth || W;
			G = parseFloat(w.getComputedStyle(root).getPropertyValue('--gap')) || G;
			minScale = wide.matches ? SCALE * SCALE : 0;
			reach = wide.matches ? 5.5 : 2.75;
		}
		function scaleAt(a) {
			return Math.max(Math.pow(SCALE, a), minScale);
		}
		// how far along the floor a door stands when it is k steps from the middle (k is fractional mid-drag):
		// each step is half of each neighbour's width plus the gap
		function along(k) {
			var a = Math.abs(k);
			var x = 0;
			var s = 1;
			for (var i = 0; a > 0 && i < 8; i++) {
				var step = Math.min(1, a);
				var next = Math.max(s * SCALE, minScale);
				x += step * (W / 2 * (s + next) + G);
				s = next;
				a -= step;
			}
			return k < 0 ? -x : x;
		}
		// steps from the middle, the short way round the ring
		function rel(i) {
			return mod(i - active - shift + N / 2, N) - N / 2;
		}

		function place() {
			portals.forEach(function (el, i) {
				var k = rel(i);
				var a = Math.abs(k);
				el.style.transform = 'translateX(' + along(k).toFixed(1) + 'px) scale(' + scaleAt(a).toFixed(4) + ')';
				// the door going round the back from one end to the other is fully faded while it travels
				el.style.opacity = String(Math.max(0, Math.min(1, reach - a)));
				el.style.zIndex = String(10 - Math.round(a));
				el.classList.toggle('is-active', a < 0.5);
				el.classList.toggle('is-side', a >= 0.5);
				el.setAttribute('aria-hidden', a > reach - 0.25 ? 'true' : 'false');
				el.tabIndex = a < 0.5 ? 0 : -1;
			});
		}

		// the halo behind the middle of the ring takes the active door's colour (portals.css turns the ivory one gold)
		function tint(el) {
			root.style.setProperty('--ring-pc', el.style.getPropertyValue('--pc').trim() || '#e8c98f');
			root.classList.toggle('is-ring-light', el.classList.contains('is-light'));
		}

		var swapTimer = 0;
		function caption(animate) {
			var el = portals[active];
			tint(el);
			var name = el.getAttribute('data-name');
			var text = el.getAttribute('data-cta');
			var wait = isWaitlist(el);
			// a future portal's button leads to the waitlist instead of through the door
			var href = wait ? waitUrl : el.getAttribute('data-href');
			function apply() {
				if (labelEl) labelEl.textContent = el.getAttribute('data-label') || '';
				nameEl.textContent = name;
				if (descEl) descEl.textContent = el.getAttribute('data-desc') || '';
				ctaText.textContent = text;
				cta.classList.toggle('is-waitlist', wait);
				if (href) {
					cta.setAttribute('href', href);
					cta.removeAttribute('aria-disabled');
				} else {
					cta.removeAttribute('href');
					cta.setAttribute('aria-disabled', 'true');
				}
			}
			dots.forEach(function (b, i) {
				if (i === active) b.setAttribute('aria-current', 'true');
				else b.removeAttribute('aria-current');
			});
			w.clearTimeout(swapTimer);
			if (!animate || reduced) {
				apply();
				captionEl.classList.remove('is-swapping');
				return;
			}
			captionEl.classList.add('is-swapping');
			swapTimer = w.setTimeout(function () {
				apply();
				captionEl.classList.remove('is-swapping');
			}, 220);
		}

		function turnTo(i) {
			var n = mod(i, N);
			var changed = n !== active;
			shift = 0;
			active = n;
			place();
			if (changed) caption(true);
		}

		function isWaitlist(el) {
			return el.getAttribute('data-waitlist') === '1' && !!waitUrl;
		}

		function nudge(el) {
			el.classList.remove('is-nudged');
			void el.offsetWidth;
			el.classList.add('is-nudged');
			w.setTimeout(function () { el.classList.remove('is-nudged'); }, 520);
		}

		// clicking: a side door comes to the middle; the middle door opens (or, while closed, shakes its head)
		portals.forEach(function (el, i) {
			el.addEventListener('click', function (e) {
				if (dragged) {
					e.preventDefault();
					return;
				}
				if (Math.abs(rel(i)) >= 0.5) {
					e.preventDefault();
					turnTo(i);
					return;
				}
				// a future portal stays closed: clicking it opens its waitlist
				if (isWaitlist(el)) {
					e.preventDefault();
					joinWaitlist(el.getAttribute('data-name'), waitUrl);
					return;
				}
				var href = el.getAttribute('data-href');
				if (!href) {
					e.preventDefault();
					nudge(el);
					return;
				}
				if (plainClick(e)) return;
				e.preventDefault();
				// a second click (a double-click) while it swings must not follow the link straight away
				if (el.classList.contains('is-entering')) return;
				go(el, href, el.querySelector('.doi-portal-door') || el);
			});
			el.addEventListener('dragstart', function (e) { e.preventDefault(); });
			if (el.tagName !== 'A') {
				el.addEventListener('keydown', function (e) {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						el.click();
					}
				});
			}
		});

		cta.addEventListener('click', function (e) {
			var el = portals[active];
			var href = cta.getAttribute('href');
			if (!href) {
				e.preventDefault();
				nudge(el);
				return;
			}
			if (isWaitlist(el)) {
				e.preventDefault();
				joinWaitlist(el.getAttribute('data-name'), href);
				return;
			}
			if (plainClick(e)) return;
			e.preventDefault();
			if (el.classList.contains('is-entering')) return;
			go(el, href, el.querySelector('.doi-portal-door') || el);
		});

		if (prev) prev.addEventListener('click', function () { turnTo(active - 1); });
		if (next) next.addEventListener('click', function () { turnTo(active + 1); });
		dots.forEach(function (b, i) {
			b.addEventListener('click', function () { turnTo(i); });
		});
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.preventDefault();
				turnTo(active + (e.key === 'ArrowRight' ? 1 : -1));
			}
		});

		// drag with a mouse, swipe with a finger (up and down still scrolls the page)
		var down = false;
		var dragged = false;
		var startX = 0;
		var pointer = null;
		track.addEventListener('pointerdown', function (e) {
			if (e.pointerType === 'mouse' && e.button !== 0) return;
			down = true;
			dragged = false;
			startX = e.clientX;
			pointer = e.pointerId;
		});
		track.addEventListener('pointermove', function (e) {
			if (!down || e.pointerId !== pointer) return;
			var dx = e.clientX - startX;
			if (!dragged && Math.abs(dx) > 8) {
				dragged = true;
				track.classList.add('is-dragging');
				try { track.setPointerCapture(pointer); } catch (err) {}
			}
			if (dragged) {
				shift = -dx / along(1);
				place();
			}
		});
		function release() {
			if (!down) return;
			down = false;
			if (!dragged) return;
			track.classList.remove('is-dragging');
			var target = Math.round(active + shift);
			// a short flick still turns one door
			if (target === active && Math.abs(shift) > 0.15) target = active + (shift > 0 ? 1 : -1);
			turnTo(target);
			// the click that ends a drag arrives after pointerup: let it see that this was a drag
			w.setTimeout(function () { dragged = false; }, 0);
		}
		track.addEventListener('pointerup', release);
		track.addEventListener('pointercancel', release);
		track.addEventListener('lostpointercapture', release);

		function settle() {
			measure();
			track.classList.add('is-dragging');
			place();
			w.requestAnimationFrame(function () {
				w.requestAnimationFrame(function () { track.classList.remove('is-dragging'); });
			});
		}
		w.addEventListener('resize', settle);
		// coming back with the back button: the page may be restored as it was left, mid-swing
		w.addEventListener('pageshow', function (e) {
			if (!e.persisted) return;
			portals.forEach(function (el) { el.classList.remove('is-entering'); });
			d.documentElement.classList.remove('doi-jt-on');
		});

		settle();
		caption(false);
	}

	ready(function () {
		waitlist();
		Array.prototype.forEach.call(d.querySelectorAll('.doi-portals'), setup);
	});
})(document, window);
