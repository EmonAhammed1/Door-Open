<?php
/**
 * Plugin Name: Door Open Intro
 * Plugin URI:  https://github.com/EmonAhammed1/Door-Open
 * Description: Plays the DRUMI door-opening animation as a full-screen overlay when visitors first open your WordPress site. The overlay fades away to reveal the WordPress page behind it.
 * Version:     1.0.0
 * Author:      Door Open
 * License:     GPL-2.0-or-later
 * Text Domain: door-open-intro
 */

defined('ABSPATH') || exit;

// ─── Constants ───────────────────────────────────────────────────────────────
define('DOI_VERSION',    '1.0.0');
define('DOI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOI_PLUGIN_URL', plugin_dir_url(__FILE__));

// ─── Inject the overlay snippet into every front-end page ────────────────────
add_action('wp_footer', 'doi_inject_overlay', 1);
function doi_inject_overlay() {
    // Don't show in wp-admin
    if (is_admin()) return;

    $overlay_url = DOI_PLUGIN_URL . 'door-overlay/overlay.html';
    ?>
    <!-- Door Open Intro Overlay -->
    <style id="doi-styles">
      #doi-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        z-index: 999999;
        background: #e8ded5;
        transition: opacity 0.8s ease, visibility 0.8s ease;
        opacity: 1;
        visibility: visible;
        overflow: hidden;
      }
      #doi-overlay.doi-fade-out {
        opacity: 0;
        visibility: hidden;
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
      }
    </style>

    <div id="doi-overlay" role="dialog" aria-label="Welcome Animation">
      <iframe
        id="doi-iframe"
        src="<?php echo esc_url($overlay_url); ?>"
        title="Welcome"
        allow="autoplay"
        scrolling="no"
      ></iframe>
    </div>

    <script id="doi-script">
    (function() {
      'use strict';

      var overlay = document.getElementById('doi-overlay');
      if (!overlay) return;

      // ── Session guard: only show once per browser session ──────────────
      var SESSION_KEY = 'doi_shown';
      // Comment out the line below to show on every page load:
      if (sessionStorage.getItem(SESSION_KEY)) {
        overlay.classList.add('doi-hidden');
        return;
      }
      sessionStorage.setItem(SESSION_KEY, '1');

      // ── Listen for the "door animation done" message from the iframe ───
      function onDone() {
        overlay.classList.add('doi-fade-out');
        // Remove from DOM after transition ends so it doesn't block interactions
        setTimeout(function() {
          overlay.classList.add('doi-hidden');
        }, 900);
      }

      window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'door-animation-done') {
          onDone();
        }
      });

      // ── Safety timeout: 12s max ────────────────────────────────────────
      // In case the iframe never sends the done message (e.g. load failure)
      setTimeout(onDone, 12000);

      // ── Skip on click/tap (for impatient visitors) ─────────────────────
      overlay.addEventListener('click', function(e) {
        // Only skip if clicking the overlay itself, not the iframe content
        if (e.target === overlay) onDone();
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

add_action('admin_init', 'doi_register_settings');
function doi_register_settings() {
    register_setting('doi_settings_group', 'doi_session_only', [
        'type'    => 'boolean',
        'default' => true,
    ]);
}

function doi_settings_page() {
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Door Open Intro Settings', 'door-open-intro'); ?></h1>
      <p style="color:#555; margin-bottom:16px;">
        The door animation overlay plays automatically when visitors arrive at your site.
        The <code>overlay.html</code> file must exist inside <code>wp-content/plugins/door-open-intro/door-overlay/</code>.
      </p>

      <div style="background:#fff; border:1px solid #ddd; padding:20px; max-width:600px; border-radius:6px;">
        <h2 style="margin-top:0; font-size:1.1rem;">📂 Installation Steps</h2>
        <ol style="line-height:1.8;">
          <li>Build the overlay: <code>npm run build:overlay</code></li>
          <li>Copy <code>dist-overlay/overlay.html</code> into this plugin's <code>door-overlay/</code> folder</li>
          <li>The overlay will appear on every page for first-time visitors (session-based)</li>
        </ol>

        <hr style="margin:16px 0;">

        <h2 style="font-size:1.1rem;">⚙️ Status</h2>
        <?php
        $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
        if (file_exists($overlay_file)) {
            $size = round(filesize($overlay_file) / 1024) . 'KB';
            echo '<p style="color:green;">✅ <strong>overlay.html</strong> found (' . esc_html($size) . ')</p>';
        } else {
            echo '<p style="color:red;">❌ <strong>overlay.html not found.</strong> Please copy it to: <code>' . esc_html($overlay_file) . '</code></p>';
        }
        ?>

        <hr style="margin:16px 0;">
        <h2 style="font-size:1.1rem;">🔗 Preview</h2>
        <p><a href="<?php echo esc_url(DOI_PLUGIN_URL . 'door-overlay/overlay.html'); ?>" target="_blank" class="button">
          Open Overlay Directly ↗
        </a></p>
      </div>
    </div>
    <?php
}
