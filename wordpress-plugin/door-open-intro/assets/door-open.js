/* =====================================================================
   DOOR OPEN INTRO — CINEMATIC 3D DOOR ANIMATION JAVASCRIPT
   Pure vanilla JavaScript · Web Audio API · Zero dependencies
   Compatible with Elementor (Canvas & Full Width), Gutenberg & Classic
   ===================================================================== */

(function () {
  'use strict';

  var $  = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Global config object passed from PHP via wp_localize_script
  var cfg = window.DOI_CONFIG || {};

  /* ------------------------------------------------------------------
     1. PROCEDURAL WEB AUDIO API (Drone, Crackle & Door Swell)
     100% synthesized in-browser — zero audio files needed!
     ------------------------------------------------------------------ */
  var Sound = (function () {
    var ctx = null, master = null, noiseBuf = null, crackleTimer = null, running = false;

    function ensure() {
      if (ctx) return ctx;
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      try {
        ctx = new AC();
        master = ctx.createGain();
        master.gain.value = 0;
        master.connect(ctx.destination);
      } catch (e) {
        return null;
      }
      return ctx;
    }

    function noise(c) {
      if (noiseBuf) return noiseBuf;
      var len = c.sampleRate * 3, buf = c.createBuffer(1, len, c.sampleRate), d = buf.getChannelData(0), last = 0;
      for (var i = 0; i < len; i++) {
        var w = Math.random() * 2 - 1;
        last = (last + 0.02 * w) / 1.02;
        d[i] = last * 3.5;
      }
      noiseBuf = buf;
      return buf;
    }

    function crackle() {
      if (!ctx || !running) return;
      var src = ctx.createBufferSource();
      src.buffer = noise(ctx);
      src.playbackRate.value = 2 + Math.random() * 3;
      var bp = ctx.createBiquadFilter();
      bp.type = 'bandpass';
      bp.frequency.value = 1500 + Math.random() * 2500;
      bp.Q.value = 1.2;
      var g = ctx.createGain(), t = ctx.currentTime, peak = 0.015 + Math.random() * 0.05;
      g.gain.setValueAtTime(0, t);
      g.gain.linearRampToValueAtTime(peak, t + 0.004);
      g.gain.exponentialRampToValueAtTime(0.0005, t + 0.03 + Math.random() * 0.05);
      src.connect(bp).connect(g).connect(master);
      src.start(t);
      src.stop(t + 0.15);
      crackleTimer = setTimeout(crackle, 60 + Math.random() * 520);
    }

    function start() {
      if (cfg.soundEnabled === false || cfg.soundEnabled === 0 || cfg.soundEnabled === '0') return;
      var c = ensure();
      if (!c) return;
      if (c.state === 'suspended') c.resume();
      if (running) return;
      running = true;
      var now = c.currentTime;

      // Drone
      var drone = c.createGain();
      drone.gain.value = 0.05;
      var lfo = c.createOscillator();
      lfo.frequency.value = 0.08;
      var lfoG = c.createGain();
      lfoG.gain.value = 0.02;
      lfo.connect(lfoG).connect(drone.gain);
      lfo.start(now);

      [55, 82.41, 110.3].forEach(function (f, i) {
        var o = c.createOscillator();
        o.type = 'sine';
        o.frequency.value = f;
        o.detune.value = i * 4;
        var g = c.createGain();
        g.gain.value = i === 0 ? 1 : 0.35;
        o.connect(g).connect(drone);
        o.start(now);
      });
      drone.connect(master);

      // Soft air ambient
      var n = c.createBufferSource();
      n.buffer = noise(c);
      n.loop = true;
      var lp = c.createBiquadFilter();
      lp.type = 'lowpass';
      lp.frequency.value = 420;
      var air = c.createGain();
      air.gain.value = 0.16;
      n.connect(lp).connect(air).connect(master);
      n.start(now);

      crackle();
      master.gain.cancelScheduledValues(now);
      master.gain.setValueAtTime(0, now);
      master.gain.linearRampToValueAtTime(0.85, now + 2.0);

      document.body.classList.add('tar-sound-on');
      $$('[data-tar-sound]').forEach(function (b) { b.setAttribute('aria-pressed', 'true'); });
    }

    function stop() {
      if (!ctx) return;
      var now = ctx.currentTime;
      master.gain.cancelScheduledValues(now);
      master.gain.setValueAtTime(master.gain.value, now);
      master.gain.linearRampToValueAtTime(0, now + 0.8);
      running = false;
      clearTimeout(crackleTimer);
      document.body.classList.remove('tar-sound-on');
      $$('[data-tar-sound]').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
    }

    function swell() {
      var c = ensure();
      if (!c) return;
      if (c.state === 'suspended') c.resume();
      var t = c.currentTime;
      master.gain.cancelScheduledValues(t);
      master.gain.setValueAtTime(0.5, t);
      master.gain.linearRampToValueAtTime(0.95, t + 0.6);

      // Low resonant air rush
      var src = c.createBufferSource();
      src.buffer = noise(c);
      var lp = c.createBiquadFilter();
      lp.type = 'lowpass';
      lp.frequency.setValueAtTime(120, t);
      lp.frequency.exponentialRampToValueAtTime(950, t + 1.6);
      lp.frequency.exponentialRampToValueAtTime(140, t + 3.2);
      var g = c.createGain();
      g.gain.setValueAtTime(0, t);
      g.gain.linearRampToValueAtTime(0.55, t + 1.4);
      g.gain.linearRampToValueAtTime(0, t + 3.4);
      src.connect(lp).connect(g).connect(master);
      src.start(t);
      src.stop(t + 3.6);

      // Chime tone
      var o = c.createOscillator();
      o.type = 'sine';
      o.frequency.value = 164.8;
      var og = c.createGain();
      og.gain.setValueAtTime(0, t + 0.5);
      og.gain.linearRampToValueAtTime(0.1, t + 1.1);
      og.gain.exponentialRampToValueAtTime(0.0005, t + 4.8);
      o.connect(og).connect(master);
      o.start(t + 0.5);
      o.stop(t + 5.0);
    }

    function bind() {
      $$('[data-tar-sound]').forEach(function (b) {
        b.addEventListener('click', function () { running ? stop() : start(); });
      });
    }

    return { start: start, stop: stop, swell: swell, bind: bind };
  })();

  /* ------------------------------------------------------------------
     2. COVER GEOMETRY & EASING HELPERS
     ------------------------------------------------------------------ */
  function cover(cw, ch, iw, ih) {
    var s = Math.max(cw / iw, ch / ih);
    var dw = iw * s, dh = ih * s;
    return { cw: cw, ch: ch, dispW: dw, dispH: dh, offX: (cw - dw) / 2, offY: (ch - dh) / 2 };
  }

  function toPx(g, fx, fy) {
    return { x: g.offX + fx * g.dispW, y: g.offY + fy * g.dispH };
  }

  function loadSize(src, cb) {
    var img = new Image();
    img.onload = function () { cb({ w: img.naturalWidth, h: img.naturalHeight }); };
    img.onerror = function () { cb({ w: 1672, h: 941 }); };
    img.src = src;
  }

  var easeInOut = function (t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; };
  var easeOut   = function (t) { return 1 - Math.pow(1 - t, 3); };

  /* ------------------------------------------------------------------
     3. THRESHOLD CONTROLLER (3D Doors & Zoom Animation)
     ------------------------------------------------------------------ */
  function initThreshold(root) {
    if (!root) return null;

    var base   = $('.tar-scene__base', root),
        clip   = $('.tar-scene__clip', root),
        room   = $('.tar-scene__room', root),
        scene  = $('.tar-scene', root),
        panels = $('.tar-scene__panels', root),
        left   = $('.tar-panel--left', root),
        right  = $('.tar-panel--right', root),
        seam   = $('.tar-seam', root),
        lintel = $('.tar-lintel', root),
        glow   = $('.tar-glow', root);

    var skipKey = root.getAttribute('data-skip-key') || '';
    if (skipKey && sessionStorage.getItem(skipKey) === '1') {
      root.classList.add('is-done');
      document.body.classList.remove('tar-locked');
      return null;
    }

    document.body.classList.add('tar-locked');

    var baseSrc = (base && base.getAttribute('data-src')) || cfg.doorImageUrl || '';
    var roomSrc = (room && room.getAttribute('data-src')) || cfg.roomImageUrl || '';

    if (base) base.style.backgroundImage = 'url("' + baseSrc + '")';
    if (room) room.style.backgroundImage = 'url("' + roomSrc + '")';

    var pct = {
      l: parseFloat(root.getAttribute('data-door-left') || '35.0') / 100,
      r: parseFloat(root.getAttribute('data-door-right') || '65.0') / 100,
      t: parseFloat(root.getAttribute('data-door-top') || '16.4') / 100,
      b: parseFloat(root.getAttribute('data-door-bottom') || '86.9') / 100,
      lintelY: parseFloat(root.getAttribute('data-lintel-y') || '9.0') / 100
    };

    var natural = null, geo = null, rect = null, center = null, busy = false;

    function layout() {
      if (!natural) return;
      var cw = root.clientWidth || window.innerWidth, ch = root.clientHeight || window.innerHeight;
      geo = cover(cw, ch, natural.w, natural.h);
      var tl = toPx(geo, pct.l, pct.t), br = toPx(geo, pct.r, pct.b);
      rect = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
      center = { x: (tl.x + br.x) / 2, y: (tl.y + br.y) / 2 };
      var size = geo.dispW + 'px ' + geo.dispH + 'px';

      if (base) {
        base.style.backgroundSize = size;
        base.style.backgroundPosition = geo.offX + 'px ' + geo.offY + 'px';
      }
      if (scene) scene.style.transformOrigin = center.x + 'px ' + center.y + 'px';
      if (room)  room.style.transformOrigin  = center.x + 'px ' + center.y + 'px';

      if (!busy && clip) {
        clip.style.clipPath = 'inset(' + rect.top + 'px ' + (cw - rect.left - rect.width) + 'px ' + (ch - rect.top - rect.height) + 'px ' + rect.left + 'px)';
      }

      var half = rect.width / 2 + 0.5;
      [left, right].forEach(function (p, i) {
        if (!p) return;
        var x = rect.left + (i ? rect.width / 2 - 0.5 : 0);
        p.style.left = x + 'px';
        p.style.top = rect.top + 'px';
        p.style.width = half + 'px';
        p.style.height = rect.height + 'px';
        p.style.backgroundImage = 'url("' + baseSrc + '")';
        p.style.backgroundSize = size;
        p.style.backgroundPosition = (geo.offX - x) + 'px ' + (geo.offY - rect.top) + 'px';
      });

      if (seam) {
        seam.style.left = (center.x - rect.width * 0.22) + 'px';
        seam.style.top = (rect.top - rect.height * 0.05) + 'px';
        seam.style.width = (rect.width * 0.44) + 'px';
        seam.style.height = (rect.height * 1.18) + 'px';
      }
      if (lintel) {
        var lp = toPx(geo, 0.5, pct.lintelY);
        lintel.style.left = lp.x + 'px';
        lintel.style.top = lp.y + 'px';
        lintel.style.fontSize = (geo.dispW * 0.0165) + 'px';
      }
      if (glow) {
        glow.style.setProperty('--gx', center.x + 'px');
        glow.style.setProperty('--gy', center.y + 'px');
      }
    }

    loadSize(baseSrc, function (s) {
      natural = s;
      layout();
    });

    if (roomSrc) new Image().src = roomSrc; // preload
    window.addEventListener('resize', layout);

    function finish() {
      if (skipKey) {
        try { sessionStorage.setItem(skipKey, '1'); } catch (e) {}
      }

      // Unlock scrolling & signal arrival so page animations under the overlay can start
      document.body.classList.remove('tar-locked');
      document.body.classList.add('tar-arrived');

      // Begin slow, velvety dissolve of the room image into the store underneath
      root.classList.add('is-dissolving');

      // After 2.0s dissolve transition completes, finish navigation or cleanup
      setTimeout(function () {
        root.classList.add('is-done');
        window.removeEventListener('resize', layout);

        var dest = root.getAttribute('data-home-url');
        if (dest && dest.trim() !== '' && dest !== '#' && dest !== window.location.pathname && dest !== window.location.href) {
          window.location.href = dest;
        } else {
          setTimeout(function () {
            if (root.parentNode) root.parentNode.removeChild(root);
          }, 150);
        }
      }, 2000);
    }

    function walkThrough() {
      if (!rect) { finish(); return; }
      var cw = root.clientWidth || window.innerWidth, ch = root.clientHeight || window.innerHeight;
      var P = center, r = rect;
      var need = Math.max(
        P.x / Math.max(1, P.x - r.left),
        (cw - P.x) / Math.max(1, r.left + r.width - P.x),
        P.y / Math.max(1, P.y - r.top),
        (ch - P.y) / Math.max(1, r.top + r.height - P.y)
      );
      var sEnd = Math.min(3.4, need * 1.08);
      var D = reduceMotion ? 700 : 1800;
      var start = performance.now();
      var i0 = { t: r.top, r: cw - r.left - r.width, b: ch - r.top - r.height, l: r.left };

      root.classList.add('is-entering');

      (function frame(now) {
        var t = Math.min(1, (now - start) / D);
        var e = easeInOut(t);
        var s = 1 + (sEnd - 1) * e;
        var k = 1.3 - 0.3 * easeOut(t);
        var shrink = 1 - easeOut(Math.min(1, t * 1.15));

        if (scene) scene.style.transform = 'scale(' + s + ')';
        if (room)  room.style.transform  = 'scale(' + (k / s) + ')';
        if (clip)  clip.style.clipPath = 'inset(' + (i0.t * shrink) + 'px ' + (i0.r * shrink) + 'px ' + (i0.b * shrink) + 'px ' + (i0.l * shrink) + 'px)';

        var op = 1 - easeInOut(Math.min(1, t / 0.7));
        if (base) base.style.opacity = op;
        if (panels) panels.style.opacity = op;
        if (lintel) lintel.style.opacity = op;

        if (glow) {
          glow.style.opacity = t < 0.4 ? 0.6 + 0.4 * (t / 0.4) : Math.max(0, 1 - (t - 0.4) / 0.6);
        }
        if (room) {
          room.style.filter = 'brightness(' + (1.15 - 0.15 * easeOut(t)) + ') saturate(1.05)';
        }

        if (t < 1) {
          requestAnimationFrame(frame);
        } else {
          // Camera has arrived fully into the room!
          if (clip) clip.style.clipPath = 'none';
          if (scene) scene.style.transform = 'scale(' + sEnd + ')';

          // Linger briefly (600ms) so the visitor sees and enjoys the room interior,
          // then slowly and smoothly fade out (dissolve) into the store!
          setTimeout(function () {
            finish();
          }, 600);
        }
      })(start);
    }

    function enter() {
      if (busy) return;
      busy = true;
      Sound.swell();
      root.classList.add('is-leaving');
      setTimeout(function () { root.classList.add('is-open'); }, 250);
      setTimeout(walkThrough, 250 + (reduceMotion ? 500 : 1500));
    }

    $$('[data-tar-enter]', root).forEach(function (b) {
      b.addEventListener('click', enter);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !busy && root && !root.classList.contains('is-done')) {
        enter();
      }
    });

    return { enter: enter, layout: layout };
  }

  /* ------------------------------------------------------------------
     4. DYNAMIC OVERLAY GENERATOR (For shortcode click triggers)
     ------------------------------------------------------------------ */
  function createDoorOverlayDOM(opts) {
    opts = opts || {};
    var doorImg = opts.doorImg || cfg.doorImageUrl || '';
    var roomImg = opts.roomImg || cfg.roomImageUrl || '';
    var destUrl = opts.url     || cfg.preloadUrl    || '';
    var btnText = opts.btnText || cfg.buttonText    || 'JOIN THE JOURNEY';

    var sec = document.createElement('section');
    sec.id = 'tar-threshold';
    sec.className = 'tar-threshold';
    sec.setAttribute('data-door-left', '35.0');
    sec.setAttribute('data-door-right', '65.0');
    sec.setAttribute('data-door-top', '16.4');
    sec.setAttribute('data-door-bottom', '86.9');
    sec.setAttribute('data-lintel-y', '9.0');
    sec.setAttribute('data-home-url', destUrl);
    sec.setAttribute('data-skip-key', ''); // do not skip when clicked explicitly

    sec.innerHTML =
      '<div class="tar-scene">' +
        '<div class="tar-scene__base" data-src="' + doorImg + '"></div>' +
        '<div class="tar-scene__clip">' +
          '<div class="tar-scene__room" data-src="' + roomImg + '"></div>' +
        '</div>' +
        '<div class="tar-scene__panels">' +
          '<div class="tar-panel tar-panel--left"><span class="tar-panel__shade"></span></div>' +
          '<div class="tar-panel tar-panel--right"><span class="tar-panel__shade"></span></div>' +
          '<div class="tar-seam"></div>' +
        '</div>' +
      '</div>' +
      '<div class="tar-glow"></div>' +
      '<div class="tar-flicker"></div>' +
      '<div class="tar-vignette"></div>' +
      '<div class="tar-grain"></div>' +
      '<div class="tar-threshold__ui">' +
        '<header class="tar-topbar">' +
          '<div class="tar-logo">' +
            '<svg class="tar-logo__droplet" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' +
              '<path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />' +
              '<circle cx="20" cy="32" r="7" />' +
              '<circle cx="20" cy="22" r="1.2" fill="currentColor" />' +
              '<circle cx="20" cy="26" r="1.2" fill="currentColor" />' +
              '<circle cx="20" cy="30" r="1.2" fill="currentColor" />' +
            '</svg>' +
            '<span class="tar-logo__name">D R U M I</span>' +
            '<span class="tar-logo__tag">A sanctuary for your dreams</span>' +
          '</div>' +
          '<nav class="tar-topbar__nav">' +
            '<button class="tar-nav-link" type="button" data-tar-sound aria-pressed="false">Sound <span class="tar-eq"><i></i><i></i><i></i><i></i></span></button>' +
          '</nav>' +
        '</header>' +
        '<div class="tar-threshold__content">' +
          '<div class="tar-threshold__scrim">' +
            '<div class="tar-emblem-wrap">' +
              '<svg class="tar-emblem" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' +
                '<path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />' +
                '<circle cx="20" cy="32" r="7" />' +
                '<circle cx="20" cy="22" r="1.2" fill="currentColor" />' +
                '<circle cx="20" cy="26" r="1.2" fill="currentColor" />' +
                '<circle cx="20" cy="30" r="1.2" fill="currentColor" />' +
              '</svg>' +
            '</div>' +
            '<h1 class="tar-display tar-drumi-title">D R U M I</h1>' +
            '<div class="tar-drumi-sub">' +
              '<p>A sanctuary for your dreams.</p>' +
              '<p>A journey back to yourself.</p>' +
            '</div>' +
            '<div class="tar-btn-container">' +
              '<button class="tar-btn" type="button" data-tar-enter>' +
                '<span>' + btnText + '</span>' +
              '</button>' +
              '<span class="tar-btn-sub">STEP INTO YOUR INNER WORLD</span>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<footer class="tar-threshold__foot">' +
          '<p class="tar-smallcaps">Slow Down.<br>Listen Within.</p>' +
          '<p class="tar-smallcaps tar-threshold__foot-right">Trust The Message.<br>Return To You.</p>' +
        '</footer>' +
      '</div>';

    document.body.appendChild(sec);
    return sec;
  }

  /* ------------------------------------------------------------------
     5. GLOBAL TRIGGER: window.doiOpenDoor(btn)
     Can be invoked anywhere: by [door_open] button, onclick, or script
     ------------------------------------------------------------------ */
  window.doiOpenDoor = function (btn) {
    var url     = (btn && btn.getAttribute('data-doi-url'))  || cfg.preloadUrl || '';
    var doorImg = (btn && btn.getAttribute('data-door-img')) || cfg.doorImageUrl || '';
    var roomImg = (btn && btn.getAttribute('data-room-img')) || cfg.roomImageUrl || '';
    var btnText = (btn && btn.getAttribute('data-btn-text')) || cfg.buttonText || 'JOIN THE JOURNEY';

    var existing = $('#tar-threshold');
    if (existing && !existing.classList.contains('is-done')) {
      var ctrl = initThreshold(existing);
      if (ctrl) ctrl.enter();
      return;
    }

    if (existing && existing.classList.contains('is-done')) {
      existing.parentNode && existing.parentNode.removeChild(existing);
    }

    var newSec = createDoorOverlayDOM({
      doorImg: doorImg,
      roomImg: roomImg,
      url: url,
      btnText: btnText
    });

    Sound.bind();
    var ctrl2 = initThreshold(newSec);
    // When triggered via button click, automatically begin the door opening sequence!
    setTimeout(function () {
      if (ctrl2) ctrl2.enter();
    }, 150);
  };

  /* ------------------------------------------------------------------
     6. INITIALIZATION ON DOM READY
     ------------------------------------------------------------------ */
  function domReady(fn) {
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  domReady(function () {
    Sound.bind();

    // If threshold already exists in markup (e.g. Site Intro Mode or [door_open mode="intro"])
    var root = $('#tar-threshold');
    if (root) {
      initThreshold(root);
    }

    // Attach click listeners to any .doi-btn or [data-doi-trigger]
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.doi-btn, [data-doi-trigger]');
      if (!btn) return;
      e.preventDefault();
      window.doiOpenDoor(btn);
    });
  });

})();
