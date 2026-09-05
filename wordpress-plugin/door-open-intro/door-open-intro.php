<?php
/**
 * Plugin Name:  Door Open Intro
 * Plugin URI:   https://github.com/EmonAhammed1/Door-Open
 * Description:  Cinematic door-opening animation overlay for WordPress. Site intro + Widget/Shortcode mode.
 * Version:      2.1.0
 * Author:       Door Open
 * License:      GPL-2.0-or-later
 * Text Domain:  door-open-intro
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'DOI_VERSION',    '2.1.0' );
define( 'DOI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DOI_OPTIONS',    'doi_settings' );

// ─── Default settings ─────────────────────────────────────────────────────────
function doi_defaults() {
    return [
        'site_intro_enabled' => 1,
        'widget_enabled'     => 0,
        'button_text'        => 'JOIN THE JOURNEY',
        'preload_url'        => '',
        'session_once'       => 1,
    ];
}

function doi_get( $key ) {
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    return $opts[ $key ] ?? null;
}

// ─── Helper: overlay iframe src URL ──────────────────────────────────────────
function doi_overlay_src( $trigger = 'auto', $redirect = '' ) {
    $btn  = doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY';
    $base = DOI_PLUGIN_URL . 'door-overlay/overlay.html';
    $args = [
        'trigger' => $trigger,
        'btn'     => rawurlencode( $btn ),
    ];
    if ( $redirect ) {
        $args['redirect'] = rawurlencode( $redirect );
    }
    return add_query_arg( $args, $base );
}

// ─── Enqueue frontend assets (CSS + JS) ──────────────────────────────────────
// This is the ONLY place JS is output — works with ALL page builders including Elementor.
// wp_enqueue_scripts fires before the page renders, so scripts are always present.
add_action( 'wp_enqueue_scripts', 'doi_enqueue_frontend_assets' );
function doi_enqueue_frontend_assets() {
    if ( is_admin() ) return;

    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    if ( ! file_exists( $overlay_file ) ) return;

    // ── Shared CSS ────────────────────────────────────────────────────────────
    $css = '
/* Door Open Intro — shared styles */
.doi-widget-btn {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 14px 32px; border-radius: 999px;
  background: #9a7470; color: #fbf7f4;
  font-family: "Cinzel", serif; font-size: 0.8rem;
  letter-spacing: 0.28em; text-transform: uppercase; font-weight: 500;
  border: 1.5px solid rgba(196,162,159,0.5); cursor: pointer;
  box-shadow: 0 8px 32px rgba(100,55,50,0.4), 0 2px 8px rgba(0,0,0,0.15);
  transition: background 0.25s ease, transform 0.15s ease;
  position: relative; overflow: hidden;
}
.doi-widget-btn:hover { background: #7f5e5b; }
.doi-widget-btn:active { transform: scale(0.97); }
.doi-widget-btn svg { width: 16px; height: 16px; flex-shrink: 0; }

/* Site intro overlay */
#doi-site-overlay {
  position: fixed; inset: 0; width: 100vw; height: 100vh;
  z-index: 999999; background: transparent;
  transition: opacity 1.1s cubic-bezier(0.4,0,0.2,1), visibility 1.1s;
  opacity: 1; visibility: visible; pointer-events: auto;
}
#doi-site-overlay.doi--fading  { opacity: 0; visibility: hidden; pointer-events: none; }
#doi-site-overlay.doi--gone    { display: none; }

/* Widget/shortcode overlay */
#doi-widget-overlay {
  display: none; position: fixed; inset: 0; width: 100vw; height: 100vh;
  z-index: 999999; background: transparent; opacity: 1;
  transition: opacity 1.1s cubic-bezier(0.4,0,0.2,1);
}
#doi-widget-overlay.doi--visible { display: block; }
#doi-widget-overlay.doi--fading  { opacity: 0; pointer-events: none; }

/* Shared iframe rule */
#doi-site-overlay iframe,
#doi-widget-overlay iframe {
  position: absolute; inset: 0; width: 100%; height: 100%;
  border: none; background: transparent;
}

body.doi-locked { overflow: hidden !important; }
';
    wp_register_style( 'doi-styles', false );
    wp_enqueue_style( 'doi-styles' );
    wp_add_inline_style( 'doi-styles', $css );

    // ── Shared JS ─────────────────────────────────────────────────────────────
    // Uses a dummy handle so we can attach inline JS the WordPress-standard way.
    // This JS is output in <head> via wp_enqueue_scripts — BEFORE Elementor or
    // any page builder renders content, so it is always available on click.
    wp_register_script( 'doi-script', false, [], DOI_VERSION, false );
    wp_enqueue_script( 'doi-script' );

    $session_once   = doi_get( 'session_once' ) ? 'true' : 'false';
    $intro_enabled  = doi_get( 'site_intro_enabled' ) ? 'true' : 'false';
    $widget_enabled = doi_get( 'widget_enabled' ) ? 'true' : 'false';

    $js = '
window.DOI = {
  introEnabled:  ' . $intro_enabled . ',
  widgetEnabled: ' . $widget_enabled . ',
  sessionOnce:   ' . $session_once . ',
  SESSION_KEY:   "doi_v21_shown"
};

/* ── Widget overlay: open on button click ─────────────────────────────────── */
window.doiOpenOverlay = function(btn) {
  if (!window.DOI.widgetEnabled) return;
  var src     = btn.getAttribute("data-doi-src");
  var bg      = btn.getAttribute("data-doi-bg") || "transparent";
  var overlay = document.getElementById("doi-widget-overlay");
  var iframe  = document.getElementById("doi-widget-iframe");
  if (!overlay || !iframe || !src) {
    console.warn("[door_open] Overlay elements not found. src=" + src);
    return;
  }
  iframe.src = src;
  overlay.style.background = bg;
  overlay.classList.add("doi--visible");
  overlay.classList.remove("doi--fading");
  document.body.classList.add("doi-locked");

  var done = false;
  function dismiss() {
    if (done) return; done = true;
    document.body.classList.remove("doi-locked");
    overlay.classList.add("doi--fading");
    setTimeout(function() {
      overlay.classList.remove("doi--visible", "doi--fading");
      iframe.src = "";
    }, 1200);
  }

  function msgH(e) {
    if (e.data && e.data.type === "door-animation-done") {
      window.removeEventListener("message", msgH);
      dismiss();
    }
  }
  window.addEventListener("message", msgH);
  var t = setTimeout(dismiss, 16000);

  function onKey(e) {
    if (e.key === "Escape") {
      clearTimeout(t);
      window.removeEventListener("message", msgH);
      document.removeEventListener("keydown", onKey);
      dismiss();
    }
  }
  document.addEventListener("keydown", onKey);
};

/* ── Site intro overlay: auto-dismiss after animation ─────────────────────── */
document.addEventListener("DOMContentLoaded", function() {
  var overlay = document.getElementById("doi-site-overlay");
  if (!overlay) return;

  if (window.DOI.sessionOnce && sessionStorage.getItem(window.DOI.SESSION_KEY)) {
    overlay.classList.add("doi--gone");
    return;
  }
  if (window.DOI.sessionOnce) sessionStorage.setItem(window.DOI.SESSION_KEY, "1");

  document.body.classList.add("doi-locked");

  var done = false;
  function dismiss() {
    if (done) return; done = true;
    document.body.classList.remove("doi-locked");
    overlay.classList.add("doi--fading");
    setTimeout(function() { overlay.classList.add("doi--gone"); }, 1200);
  }

  window.addEventListener("message", function(e) {
    if (e.data && e.data.type === "door-animation-done") dismiss();
  });
  setTimeout(dismiss, 15000);
  document.addEventListener("keydown", function(e) { if (e.key === "Escape") dismiss(); });
});
';
    wp_add_inline_script( 'doi-script', $js );
}

// ─── TOGGLE 1: Site Intro — inject overlay into every front-end page ──────────
add_action( 'wp_footer', 'doi_site_intro_overlay', 1 );
function doi_site_intro_overlay() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'site_intro_enabled' ) ) return;

    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    if ( ! file_exists( $overlay_file ) ) return;

    $src = doi_overlay_src( 'auto' );
    ?>
    <div id="doi-site-overlay" role="presentation" aria-hidden="true">
      <iframe
        src="<?php echo esc_url( $src ); ?>"
        title="Welcome animation"
        allow="autoplay"
        allowtransparency="true"
        scrolling="no"
        frameborder="0"
      ></iframe>
    </div>
    <?php
}

// ─── TOGGLE 2: Widget overlay DOM — inject once if widget mode is on ──────────
// The div is always injected in footer; doiOpenOverlay() (from wp_enqueue_scripts) controls it.
add_action( 'wp_footer', 'doi_widget_overlay_dom', 2 );
function doi_widget_overlay_dom() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'widget_enabled' ) ) return;
    ?>
    <div id="doi-widget-overlay" role="dialog" aria-label="Door animation" aria-hidden="true">
      <iframe
        id="doi-widget-iframe"
        title="Welcome animation"
        allow="autoplay"
        allowtransparency="true"
        scrolling="no"
        frameborder="0"
      ></iframe>
    </div>
    <?php
}

// ─── Shortcode — ONLY outputs HTML, zero <script> tags ───────────────────────
// JS is already on the page via wp_enqueue_scripts above.
add_action( 'init', 'doi_register_shortcodes' );
function doi_register_shortcodes() {
    add_shortcode( 'door_open',       'doi_shortcode_render' );
    add_shortcode( 'door_open_intro', 'doi_shortcode_render' ); // alias
}

function doi_shortcode_render( $atts ) {
    if ( ! doi_get( 'widget_enabled' ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="background:#fff3cd;color:#856404;padding:8px 12px;border-radius:4px;font-size:0.82em;display:inline-block;">'
                . '⚠️ <strong>[door_open]</strong>: Enable <em>Widget / Shortcode Mode</em> in '
                . '<a href="' . admin_url( 'options-general.php?page=door-open-intro' ) . '">Door Open Intro settings</a>.'
                . '</p>';
        }
        return '';
    }

    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    if ( ! file_exists( $overlay_file ) ) {
        return '<p style="color:#c62828;font-size:0.82em;">[door_open] overlay.html not found. '
            . '<a href="' . admin_url( 'options-general.php?page=door-open-intro' ) . '">See settings</a>.</p>';
    }

    $atts = shortcode_atts( [
        'text' => doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY',
        'url'  => doi_get( 'preload_url' ) ?: '',
        'bg'   => 'transparent',
    ], $atts, 'door_open' );

    $src = doi_overlay_src( 'click', esc_url( $atts['url'] ) );

    // Pure HTML — no <script> tags — safe for Elementor, Gutenberg, WPBakery, etc.
    return sprintf(
        '<button
           type="button"
           class="doi-widget-btn"
           data-doi-src="%s"
           data-doi-bg="%s"
           onclick="doiOpenOverlay(this)"
           aria-label="%s"
         >
           <span>%s</span>
           <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
             <path d="M4 10H16M16 10L11 5M16 10L11 15"/>
           </svg>
         </button>',
        esc_attr( $src ),
        esc_attr( $atts['bg'] ),
        esc_attr( $atts['text'] ),
        esc_html( $atts['text'] )
    );
}

// ─── Classic WP Widget ────────────────────────────────────────────────────────
add_action( 'widgets_init', function() {
    if ( doi_get( 'widget_enabled' ) ) register_widget( 'DOI_Widget' );
} );

class DOI_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'doi_door_widget',
            __( 'Door Open Animation', 'door-open-intro' ),
            [ 'description' => __( 'Cinematic door-opening button for any sidebar/section.', 'door-open-intro' ) ]
        );
    }
    public function widget( $args, $instance ) {
        $text = ! empty( $instance['text'] ) ? $instance['text'] : doi_get( 'button_text' );
        $url  = ! empty( $instance['url'] )  ? $instance['url']  : '';
        echo $args['before_widget'];
        echo do_shortcode( '[door_open text="' . esc_attr( $text ) . '" url="' . esc_url( $url ) . '"]' );
        echo $args['after_widget'];
    }
    public function form( $instance ) {
        $text = $instance['text'] ?? '';
        $url  = $instance['url']  ?? '';
        ?>
        <p>
          <label for="<?php echo $this->get_field_id('text'); ?>">Button Text:</label>
          <input class="widefat" id="<?php echo $this->get_field_id('text'); ?>"
            name="<?php echo $this->get_field_name('text'); ?>" type="text"
            value="<?php echo esc_attr($text); ?>" placeholder="<?php echo esc_attr(doi_get('button_text')); ?>">
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('url'); ?>">Redirect URL:</label>
          <input class="widefat" id="<?php echo $this->get_field_id('url'); ?>"
            name="<?php echo $this->get_field_name('url'); ?>" type="url"
            value="<?php echo esc_attr($url); ?>" placeholder="https://">
        </p>
        <?php
    }
    public function update( $new_instance, $old_instance ) {
        return [
            'text' => sanitize_text_field( $new_instance['text'] ),
            'url'  => esc_url_raw( $new_instance['url'] ),
        ];
    }
}

// ─── Admin Settings Page ──────────────────────────────────────────────────────
add_action( 'admin_menu', function() {
    add_options_page( 'Door Open Intro', 'Door Open Intro', 'manage_options', 'door-open-intro', 'doi_settings_page' );
} );

add_action( 'admin_init', function() {
    register_setting( 'doi_settings_group', DOI_OPTIONS, [ 'sanitize_callback' => 'doi_sanitize_settings' ] );
} );

function doi_sanitize_settings( $input ) {
    return [
        'site_intro_enabled' => ! empty( $input['site_intro_enabled'] ) ? 1 : 0,
        'widget_enabled'     => ! empty( $input['widget_enabled'] )     ? 1 : 0,
        'button_text'        => sanitize_text_field( $input['button_text'] ?? 'JOIN THE JOURNEY' ),
        'preload_url'        => esc_url_raw( $input['preload_url'] ?? '' ),
        'session_once'       => ! empty( $input['session_once'] ) ? 1 : 0,
    ];
}

function doi_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $saved = false;
    if ( isset( $_POST['_doi_nonce'] ) && wp_verify_nonce( $_POST['_doi_nonce'], 'doi_save' ) ) {
        update_option( DOI_OPTIONS, doi_sanitize_settings( $_POST['doi'] ?? [] ) );
        $saved = true;
    }

    $overlay_file   = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    $file_ok        = file_exists( $overlay_file );
    $file_size      = $file_ok ? round( filesize( $overlay_file ) / 1024 ) . ' KB' : '—';
    $intro_enabled  = (bool) doi_get( 'site_intro_enabled' );
    $widget_enabled = (bool) doi_get( 'widget_enabled' );
    $btn_text       = esc_attr( doi_get( 'button_text' ) );
    $preload_url    = esc_attr( doi_get( 'preload_url' ) );
    $session_once   = (bool) doi_get( 'session_once' );
    ?>
    <style>
      .doi-admin { max-width: 740px; padding-bottom: 40px; }
      .doi-admin h1 { font-size: 1.45rem; margin: 0 0 4px; display:flex; align-items:center; gap:10px; }
      .doi-admin .doi-ver { font-size:0.7rem; color:#999; font-weight:400; }
      .doi-card { background:#fff; border:1px solid #e2e2e2; border-radius:10px; padding:22px 26px; margin:18px 0; box-shadow:0 2px 6px rgba(0,0,0,0.04); }
      .doi-card h2 { margin:0 0 5px; font-size:0.97rem; }
      .doi-card .doi-desc { color:#666; font-size:0.83rem; margin:0 0 18px; line-height:1.6; }
      .doi-row { display:flex; align-items:center; justify-content:space-between; gap:16px; margin:10px 0; }
      .doi-row-label { font-size:0.9rem; color:#1d2327; }
      .doi-row-label small { display:block; color:#888; font-size:0.77rem; margin-top:2px; }
      /* Toggle */
      .doi-toggle { position:relative; display:inline-block; width:46px; height:24px; flex-shrink:0; }
      .doi-toggle input { opacity:0; width:0; height:0; }
      .doi-slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:24px; transition:.3s; }
      .doi-slider:before { content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; box-shadow:0 1px 4px rgba(0,0,0,0.2); }
      .doi-toggle input:checked + .doi-slider { background:#9a7470; }
      .doi-toggle input:checked + .doi-slider:before { transform:translateX(22px); }
      /* Fields */
      .doi-field { margin:14px 0 0; }
      .doi-field label { display:block; font-size:0.83rem; color:#555; margin-bottom:4px; font-weight:600; }
      .doi-field input[type=text], .doi-field input[type=url] { width:100%; max-width:420px; padding:8px 11px; border:1px solid #d0d0d0; border-radius:6px; font-size:0.87rem; transition:border-color .2s; }
      .doi-field input:focus { border-color:#9a7470; outline:none; box-shadow:0 0 0 2px rgba(154,116,112,0.15); }
      .doi-field .doi-hint { font-size:0.75rem; color:#888; margin-top:4px; line-height:1.5; }
      .doi-hr { border:none; border-top:1px solid #eee; margin:16px 0; }
      .doi-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:12px; font-size:0.75rem; font-weight:600; }
      .doi-badge--ok  { background:#e8f5e9; color:#2e7d32; }
      .doi-badge--err { background:#fdecea; color:#c62828; }
      .doi-code { background:#f6f7f7; border:1px solid #e2e2e2; border-radius:5px; padding:8px 12px; font-family:monospace; font-size:0.8rem; color:#444; margin:8px 0; }
      .doi-save-row { margin:22px 0 0; display:flex; align-items:center; gap:14px; }
      .doi-save-btn { padding:10px 26px; background:#9a7470; color:#fff; border:none; border-radius:6px; font-size:0.88rem; cursor:pointer; font-weight:600; transition:background .2s; }
      .doi-save-btn:hover { background:#7f5e5b; }
      .doi-ok-msg { color:#2e7d32; font-size:0.85rem; font-weight:600; }
    </style>

    <div class="wrap doi-admin">
      <h1>🚪 Door Open Intro <span class="doi-ver">v<?php echo esc_html(DOI_VERSION); ?></span></h1>

      <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible"><p><strong>✅ Settings saved!</strong></p></div>
      <?php endif; ?>

      <p style="margin:8px 0;">
        <?php if ($file_ok): ?>
          <span class="doi-badge doi-badge--ok">✅ overlay.html installed (<?php echo esc_html($file_size); ?>)</span>
        <?php else: ?>
          <span class="doi-badge doi-badge--err">❌ overlay.html not found — see Installation below</span>
        <?php endif; ?>
      </p>

      <form method="post" action="">
        <?php wp_nonce_field('doi_save', '_doi_nonce'); ?>

        <!-- Toggle 1: Site Intro -->
        <div class="doi-card">
          <h2>🎬 Site Intro Mode</h2>
          <p class="doi-desc">
            Door animation auto-plays when visitors arrive at your site.
            The WordPress page loads behind it — visible through the arch doorway.
            Doors swing open → zoom through → overlay fades away.
          </p>

          <div class="doi-row">
            <div class="doi-row-label">
              Enable Site Intro
              <small>Auto-plays the door animation on site entry</small>
            </div>
            <label class="doi-toggle">
              <input type="checkbox" name="doi[site_intro_enabled]" value="1" <?php checked($intro_enabled); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <hr class="doi-hr">

          <div class="doi-row">
            <div class="doi-row-label">
              Show Once Per Session
              <small>ON = one time per browser session &nbsp;|&nbsp; OFF = every page load</small>
            </div>
            <label class="doi-toggle">
              <input type="checkbox" name="doi[session_once]" value="1" <?php checked($session_once); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>
        </div>

        <!-- Toggle 2: Widget / Shortcode -->
        <div class="doi-card">
          <h2>🧩 Widget / Shortcode Mode</h2>
          <p class="doi-desc">
            Place a door-open button anywhere. Clicking it triggers the full-screen door animation.
            Works with Elementor, Gutenberg, Classic Editor, and all page builders.
          </p>

          <div class="doi-row">
            <div class="doi-row-label">
              Enable Widget &amp; Shortcode
              <small>Activates <code>[door_open]</code> shortcode + "Door Open Animation" sidebar widget</small>
            </div>
            <label class="doi-toggle">
              <input type="checkbox" name="doi[widget_enabled]" value="1" <?php checked($widget_enabled); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <?php if ($widget_enabled): ?>
          <hr class="doi-hr">
          <p style="font-size:0.83rem;color:#555;margin:0 0 4px;"><strong>Shortcode:</strong></p>
          <div class="doi-code">[door_open]</div>
          <div class="doi-code">[door_open text="Enter" url="/about"]</div>
          <p style="font-size:0.75rem;color:#888;margin:4px 0 0;">
            <code>text</code> — button label &nbsp;|&nbsp; <code>url</code> — page to go to after animation
          </p>
          <?php endif; ?>
        </div>

        <!-- Global Settings -->
        <div class="doi-card">
          <h2>⚙️ Global Settings</h2>

          <div class="doi-field">
            <label for="doi-btn-text">Button Text</label>
            <input type="text" id="doi-btn-text" name="doi[button_text]"
              value="<?php echo $btn_text; ?>" placeholder="JOIN THE JOURNEY" maxlength="60">
            <p class="doi-hint">Default text on the button / CTA label. Shortcode <code>text=""</code> attribute overrides this per-button.</p>
          </div>

          <div class="doi-field" style="margin-top:18px;">
            <label for="doi-preload-url">Default Redirect URL (Widget Mode)</label>
            <input type="url" id="doi-preload-url" name="doi[preload_url]"
              value="<?php echo $preload_url; ?>" placeholder="<?php echo esc_attr(home_url('/')); ?>">
            <p class="doi-hint">After the animation completes, navigate to this URL. Leave blank to stay on the same page. Shortcode <code>url=""</code> attribute overrides per-button.</p>
          </div>
        </div>

        <!-- Installation -->
        <div class="doi-card">
          <h2>📂 Installation / Update</h2>
          <ol style="line-height:2;color:#555;margin:0 0 0 18px;">
            <li>In the React project: <code style="background:#f6f7f7;padding:2px 6px;border-radius:3px;">npm run build:overlay</code></li>
            <li>Copy <code>dist-overlay/overlay.html</code> → <code>wp-content/plugins/door-open-intro/door-overlay/overlay.html</code></li>
          </ol>
          <hr class="doi-hr">
          <p>
            <a href="<?php echo esc_url(DOI_PLUGIN_URL . 'door-overlay/overlay.html?trigger=click&btn=Preview'); ?>"
               target="_blank" class="button button-secondary">Preview Overlay ↗</a>
            &nbsp;
            <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button button-primary">View Site ↗</a>
          </p>
          <p style="font-size:0.75rem;color:#888;margin:8px 0 0;">
            <em>To replay: DevTools → Application → Session Storage → delete <code>doi_v21_shown</code></em>
          </p>
        </div>

        <div class="doi-save-row">
          <button type="submit" class="doi-save-btn">💾 Save Settings</button>
          <?php if ($saved): ?><span class="doi-ok-msg">✅ Saved!</span><?php endif; ?>
        </div>
      </form>
    </div>
    <?php
}
