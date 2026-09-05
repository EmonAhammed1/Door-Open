<?php
/**
 * Plugin Name: Door Open Intro
 * Plugin URI:  https://github.com/EmonAhammed1/Door-Open
 * Description: Plays the DRUMI door-opening animation as a full-screen overlay when visitors first open your WordPress site. The stone arch frame has a transparent doorway hole — the ACTUAL WordPress site shows through while the doors swing open. When the camera zooms through, the overlay fades to reveal the full site.
 * Version:     1.1.0
 * Author:      Door Open
 * License:     GPL-2.0-or-later
 * Text Domain: door-open-intro
 */

defined('ABSPATH') || exit;

// ─── Constants ───────────────────────────────────────────────────────────────
define('DOI_VERSION',    '1.1.0');
define('DOI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOI_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─── Inject the overlay snippet into every front-end page ────────────────────
add_action('wp_footer', 'doi_inject_overlay', 1);
function doi_inject_overlay() {
    if (is_admin()) return;

    $overlay_url = DOI_PLUGIN_URL . 'door-overlay/overlay.html';
    ?>
    <!-- Door Open Intro Overlay v1.1 -->
    <style id="doi-styles">
      /*
       * The overlay wrapper itself is transparent — the WP page renders normally
       * behind it. Only the arch + door panels are visible from the iframe.
       */
      #doi-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        z-index: 999999;
        /* Transparent so WP page shows through the arch hole */
        background: transparent;
        pointer-events: auto;
        transition: opacity 1s ease;
        opacity: 1;
      }
      #doi-overlay.doi-fading {
        opacity: 0;
        pointer-events: none;
      }
      #doi-overlay.doi-hidden {
        display: none;
      }
      #doi-iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: none;
        background: transparent;
        /* allowtransparency is set as attribute; this ensures the CSS matches */
      }

      /*
       * While the overlay is active, PREVENT the WordPress page from scrolling.
       * (We allow the WP page to be visible, but not interactive until overlay is done.)
       */
      body.doi-active {
        overflow: hidden !important;
      }
    </style>

    <div id="doi-overlay" role="dialog" aria-label="Welcome Animation" aria-live="polite">
      <iframe
        id="doi-iframe"
        src="<?php echo esc_url($overlay_url); ?>"
        title="Welcome"
        allow="autoplay"
        allowtransparency="true"
        scrolling="no"
        frameborder="0"
      ></iframe>
    </div>

    <script id="doi-script">
    (function() {
      'use strict';

      var overlay = document.getElementById('doi-overlay');
      var iframe  = document.getElementById('doi-iframe');
      if (!overlay || !iframe) return;

      // ── Session guard: only show once per browser session ──────────────────
      // (Remove the next 3 lines if you want it to play on EVERY page load)
      var SESSION_KEY = 'doi_shown_v1';
      if (sessionStorage.getItem(SESSION_KEY)) {
        overlay.classList.add('doi-hidden');
        return;
      }
      sessionStorage.setItem(SESSION_KEY, '1');

      // Lock body scroll while overlay is active
      document.body.classList.add('doi-active');

      // ── Dismiss the overlay ────────────────────────────────────────────────
      var dismissed = false;
      function dismiss() {
        if (dismissed) return;
        dismissed = true;
        document.body.classList.remove('doi-active');
        overlay.classList.add('doi-fading');
        setTimeout(function() {
          overlay.classList.add('doi-hidden');
        }, 1100); // slightly longer than the CSS transition
      }

      // ── Listen for the "animation done" postMessage from the iframe ────────
      window.addEventListener('message', function(e) {
        // Accept from any origin since the plugin hosts the iframe on same domain
        if (e.data && e.data.type === 'door-animation-done') {
          dismiss();
        }
      });

      // ── Safety timeout — 14s max (in case iframe fails to load / send msg) ─
      setTimeout(dismiss, 14000);

      // ── Click the overlay BACKGROUND to skip (not the arch area) ──────────
      // Users who want to skip can click outside the iframe (e.g., keyboard ESC)
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') dismiss();
      });

    })();
    </script>
    <?php
}

// ─── Admin settings page ─────────────────────────────────────────────────────
add_action('admin_menu', 'doi_admin_menu');
function doi_admin_menu() {
    add_options_page(
        __('Door Open Intro Settings', 'door-open-intro'),
        __('Door Open Intro', 'door-open-intro'),
        'manage_options',
        'door-open-intro',
        'doi_settings_page'
    );
}

function doi_settings_page() {
    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    $file_exists  = file_exists($overlay_file);
    $file_size    = $file_exists ? round(filesize($overlay_file) / 1024) . ' KB' : '—';
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Door Open Intro Settings', 'door-open-intro'); ?></h1>

      <div style="background:#fff; border:1px solid #ddd; padding:24px; max-width:660px; border-radius:8px; margin-top:16px;">

        <h2 style="margin-top:0; font-size:1.1rem; color:#1d2327;">🎬 How It Works</h2>
        <ul style="line-height:2; color:#444; margin-left:18px;">
          <li>Visitor opens your site → WordPress page loads normally</li>
          <li>A <strong>transparent overlay</strong> sits on top</li>
          <li>The stone arch shows with its doorway <strong>hole revealing your WP site</strong></li>
          <li>Doors swing open → camera zooms through → overlay fades away</li>
          <li>Session-based: shown <strong>once per browser session</strong></li>
        </ul>

        <hr style="margin:20px 0; border-color:#eee;">

        <h2 style="font-size:1.1rem; color:#1d2327;">📂 Installation</h2>
        <ol style="line-height:2; color:#444; margin-left:18px;">
          <li>Build overlay: <code style="background:#f6f7f7; padding:2px 6px; border-radius:3px;">npm run build:overlay</code></li>
          <li>Copy <code>dist-overlay/overlay.html</code> → <code>door-overlay/overlay.html</code></li>
          <li>Re-zip and reinstall, or copy file directly via FTP</li>
        </ol>

        <hr style="margin:20px 0; border-color:#eee;">

        <h2 style="font-size:1.1rem; color:#1d2327;">⚙️ Status</h2>
        <?php if ($file_exists): ?>
          <p style="color:#00a32a; font-weight:600;">✅ overlay.html installed (<?php echo esc_html($file_size); ?>)</p>
        <?php else: ?>
          <p style="color:#d63638; font-weight:600;">❌ overlay.html NOT found</p>
          <p style="color:#666;">Expected path:<br><code><?php echo esc_html($overlay_file); ?></code></p>
        <?php endif; ?>

        <hr style="margin:20px 0; border-color:#eee;">

        <h2 style="font-size:1.1rem; color:#1d2327;">🔗 Tools</h2>
        <p>
          <a href="<?php echo esc_url(DOI_PLUGIN_URL . 'door-overlay/overlay.html'); ?>" target="_blank" class="button button-secondary">
            Preview Overlay Alone ↗
          </a>
          &nbsp;
          <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button button-primary">
            View Homepage with Overlay ↗
          </a>
        </p>
        <p style="color:#666; font-size:0.85rem; margin-top:8px;">
          <em>Tip: Clear <code>sessionStorage</code> in DevTools → Application → Session Storage to replay the animation.</em>
        </p>
      </div>
    </div>
    <?php
}
