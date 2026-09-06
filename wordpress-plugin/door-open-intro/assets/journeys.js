/* Site header: transparent over the top of the homepage, off-white once the page scrolls. (The homepage doors that
   used to live here are now the portals: assets/portals.js.) */
(function (d, w) {
	function ready(fn) {
		if (d.readyState !== 'loading') fn();
		else d.addEventListener('DOMContentLoaded', fn);
	}

	ready(function () {
		var header = d.querySelector('header.elementor-location-header') || d.querySelector('.elementor-148');
		if (!header) return;
		function onScroll() {
			if (w.scrollY > 40) {
				header.classList.add('header-scrolled');
			} else {
				header.classList.remove('header-scrolled');
			}
		}
		w.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	});
})(document, window);
