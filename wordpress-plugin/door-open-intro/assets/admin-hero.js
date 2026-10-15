/* Settings → Door Open Intro: turns a chosen video into the corridor's still frames, in the browser (the server
   can't convert video), and uploads them. The frames are only used once the settings are saved. */
(function () {
	'use strict';
	var C = window.DOI_HERO_ADMIN;
	if (!C || !window.wp || !wp.media) return;

	var busy = 0;

	function fmt(s, a, b) {
		return s.replace('%1$d', a).replace('%2$d', b);
	}
	function pad(n) {
		n = String(n);
		while (n.length < 3) n = '0' + n;
		return n;
	}
	function setStatus(el, text, cls) {
		el.textContent = text;
		el.className = 'doi-video-status' + (cls ? ' ' + cls : '');
	}

	function post(fields, files) {
		var fd = new FormData();
		fd.append('nonce', C.nonce);
		Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
		(files || []).forEach(function (f) {
			fd.append('f[]', f.blob, f.name);
			fd.append('n[]', f.n);
		});
		return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (!j || !j.success) throw new Error('request failed');
				return j.data;
			});
	}

	function once(el, ok, bad) {
		return new Promise(function (resolve, reject) {
			function done() { off(); resolve(); }
			function fail() { off(); reject(new Error(bad)); }
			function off() {
				el.removeEventListener(ok, done);
				el.removeEventListener(bad, fail);
			}
			el.addEventListener(ok, done);
			el.addEventListener(bad, fail);
		});
	}
	function seek(video, t) {
		var p = once(video, 'seeked', 'error');
		video.currentTime = t;
		return p;
	}
	function toBlob(canvas, type, quality) {
		return new Promise(function (resolve) { canvas.toBlob(resolve, type, quality); });
	}
	function wait(ms) {
		return new Promise(function (resolve) { setTimeout(resolve, ms); });
	}

	// a thumbnail-sized fingerprint of what was just drawn, to tell whether the video really moved on
	var probe = document.createElement('canvas');
	probe.width = 32;
	probe.height = 18;
	var pctx = probe.getContext('2d', { willReadFrequently: true });
	function fingerprint(canvas) {
		pctx.drawImage(canvas, 0, 0, 32, 18);
		var d = pctx.getImageData(0, 0, 32, 18).data;
		var h = 0;
		for (var k = 0; k < d.length; k += 4) h = (h * 31 + d[k] + d[k + 1] * 7 + d[k + 2] * 13) >>> 0;
		return h;
	}

	async function prepare(box, att) {
		var cap = +box.dataset.cap;
		var status = box.querySelector('.doi-video-status');
		var bar = box.querySelector('.doi-video-progress');
		var input = box.querySelector('.doi-video-value');
		var thumb = box.querySelector('.doi-video-thumb');
		var name = box.querySelector('.doi-video-name');
		var pick = box.querySelector('.doi-video-pick');
		var reset = box.querySelector('.doi-video-reset');
		var before = { value: input.value, thumb: thumb.src, name: name.textContent, reset: reset.hidden };

		busy++;
		pick.disabled = true;
		bar.value = 0;
		bar.hidden = false;
		setStatus(status, C.text.loading);

		var video = document.createElement('video');
		video.muted = true;
		video.playsInline = true;
		video.preload = 'auto';
		try {
			video.src = att.url;
			await once(video, 'loadeddata', 'error');
			var dur = video.duration;
			var vw = video.videoWidth;
			var vh = video.videoHeight;
			if (!vw || !vh || !isFinite(dur) || dur <= 0) throw new Error('no video');

			// no larger than the corridor can use; ~12 stills per second of video, 60 to 180 in all
			var scale = Math.min(1, cap / Math.max(vw, vh));
			var w = Math.round(vw * scale / 2) * 2;
			var h = Math.round(vh * scale / 2) * 2;
			var count = Math.max(60, Math.min(180, Math.round(dur * 12)));
			var canvas = document.createElement('canvas');
			canvas.width = w;
			canvas.height = h;
			var ctx = canvas.getContext('2d');

			var begin = await post({ action: 'doi_hero_begin', set: box.dataset.set });
			var last = Math.max(0.001, dur - 0.05);
			var ext = null;
			var batch = [];
			var prev = null;
			var seen = {};
			var distinct = 0;
			for (var i = 0; i < count; i++) {
				var t = Math.max(0.001, last * i / (count - 1));
				await seek(video, t);
				ctx.drawImage(video, 0, 0, w, h);
				// a paused video whose tab isn't in front can stop decoding and keep handing back the same
				// picture: when a still matches the one before, give it a moment, seek again and look again
				var print = fingerprint(canvas);
				for (var tries = 0; i && print === prev && tries < 3; tries++) {
					await wait(150);
					await seek(video, t);
					ctx.drawImage(video, 0, 0, w, h);
					print = fingerprint(canvas);
				}
				if (!seen[print]) {
					seen[print] = true;
					distinct++;
				}
				prev = print;
				// a corridor that never moves is worse than the one already there: stop rather than save it
				if ((i === 29 && distinct === 1) || (i === count - 1 && distinct < count / 4)) throw new Error('stuck');
				var blob = ext === 'jpg' ? null : await toBlob(canvas, 'image/webp', 0.82);
				// browsers that can't write WebP hand back a PNG: use JPEG for the whole video then
				if (!ext) ext = blob && blob.type === 'image/webp' ? 'webp' : 'jpg';
				if (ext === 'jpg') blob = await toBlob(canvas, 'image/jpeg', 0.86);
				batch.push({ blob: blob, n: i + 1, name: pad(i + 1) + '.' + ext });
				if (batch.length === 10 || i === count - 1) {
					var res = await post({ action: 'doi_hero_frames', folder: begin.folder, ext: ext }, batch);
					if (res.saved !== batch.length) throw new Error('frames not saved');
					batch = [];
				}
				bar.value = Math.round((i + 1) / count * 100);
				setStatus(status, fmt(C.text.preparing, i + 1, count));
			}

			input.value = JSON.stringify({ folder: begin.folder, count: count, w: w, h: h, ext: ext, video: att.id });
			thumb.src = begin.base + '001.' + ext;
			name.textContent = att.filename || att.title || '';
			reset.hidden = false;
			setStatus(status, C.text.ready, 'is-ready');
		} catch (e) {
			input.value = before.value;
			thumb.src = before.thumb;
			name.textContent = before.name;
			reset.hidden = before.reset;
			setStatus(status, e.message === 'stuck' && C.text.stuck ? C.text.stuck : C.text.failed, 'is-error');
		} finally {
			video.removeAttribute('src');
			video.load();
			bar.hidden = true;
			pick.disabled = false;
			busy--;
		}
	}

	Array.prototype.forEach.call(document.querySelectorAll('.doi-video'), function (box) {
		var frame = null;
		box.querySelector('.doi-video-pick').addEventListener('click', function () {
			if (!frame) {
				frame = wp.media({ title: C.text.choose, button: { text: C.text.use }, library: { type: 'video' }, multiple: false });
				frame.on('select', function () {
					prepare(box, frame.state().get('selection').first().toJSON());
				});
			}
			frame.open();
		});
		box.querySelector('.doi-video-reset').addEventListener('click', function () {
			box.querySelector('.doi-video-value').value = '';
			box.querySelector('.doi-video-thumb').src = box.dataset.originalThumb;
			box.querySelector('.doi-video-name').textContent = C.text.original;
			this.hidden = true;
			setStatus(box.querySelector('.doi-video-status'), C.text.reset);
		});
	});

	window.addEventListener('beforeunload', function (e) {
		if (!busy) return;
		e.preventDefault();
		e.returnValue = C.text.busy;
	});
})();
