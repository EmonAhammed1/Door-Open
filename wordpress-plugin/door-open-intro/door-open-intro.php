<?php
/**
 * Plugin Name:  Door Open Intro
 * Plugin URI:   https://github.com/EmonAhammed1/Door-Open
 * Description:  Cinematic door-opening animation overlay for WordPress. Use as a site intro (auto-plays on entry) or as a widget/shortcode for any section.
 * Version:      2.0.0
 * Author:       Door Open
 * License:      GPL-2.0-or-later
 * Text Domain:  door-open-intro
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'DOI_VERSION',    '2.0.0' );
define( 'DOI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DOI_OPTIONS',    'doi_settings' );

// ─── Default settings ─────────────────────────────────────────────────────────
function doi_defaults() {
    return [
        'site_intro_enabled' => 1,       // Toggle 1: auto-play on site entry
        'widget_enabled'     => 0,       // Toggle 2: enable shortcode/widget
        'button_text'        => 'JOIN THE JOURNEY',
        'preload_url'        => '',      // URL to show behind the door (blank = current page)
        'session_once'       => 1,       // Site intro: show only once per session
        'overlay_bg'         => 'transparent', // "transparent" or "#e8ded5"
    ];
}

function doi_get( $key ) {
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    return $opts[ $key ] ?? null;
}

// ─── Helper: build the overlay iframe src URL ─────────────────────────────────
function doi_overlay_src( $trigger = 'auto', $redirect = '' ) {
    $btn = doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY';
    $base = DOI_PLUGIN_URL . 'door-overlay/overlay.html';
    $args = [
        'trigger'  => $trigger,
        'btn'      => rawurlencode( $btn ),
    ];
    if ( $redirect ) {
        $args['redirect'] = rawurlencode( $redirect );
    }
    return add_query_arg( $args, $base );
}

// ─── TOGGLE 1: Site Intro — inject into every front-end page ──────────────────
add_action( 'wp_footer', 'doi_site_intro_overlay', 1 );
function doi_site_intro_overlay() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'site_intro_enabled' ) ) return;

    $overlay_src  = doi_overlay_src( 'auto' );
    $session_once = doi_get( 'session_once' ) ? 'true' : 'false';
    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';

    if ( ! file_exists( $overlay_file ) ) return; // Safety check
    ?>
    <!-- Door Open Intro v<?php echo esc_html( DOI_VERSION ); ?> — Site Intro -->
    <style id="doi-intro-styles">
      #doi-site-overlay {
        position: fixed; inset: 0;
        width: 100vw; height: 100vh;
        z-index: 999999;
        background: transparent;
        transition: opacity 1.1s cubic-bezier(0.4,0,0.2,1), visibility 1.1s;
        opacity: 1; visibility: visible;
        pointer-events: auto;
      }
      #doi-site-overlay.doi--fading {
        opacity: 0; visibility: hidden; pointer-events: none;
      }
      #doi-site-overlay.doi--gone { display: none; }
      #doi-site-overlay iframe {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        border: none; background: transparent;
      }
      body.doi-locked { overflow: hidden !important; }
    </style>

    <div id="doi-site-overlay" role="presentation" aria-hidden="true">
      <iframe
        src="<?php echo esc_url( $overlay_src ); ?>"
        title="Welcome animation"
        allow="autoplay"
        allowtransparency="true"
        scrolling="no"
        frameborder="0"
      ></iframe>
    </div>

    <script id="doi-intro-js">
    (function(){
      var SESSION_KEY = 'doi_v2_shown';
      var useSession  = <?php echo $session_once; ?>;
      var overlay     = document.getElementById('doi-site-overlay');
      if (!overlay) return;

      if (useSession && sessionStorage.getItem(SESSION_KEY)) {
        overlay.classList.add('doi--gone');
        return;
      }
      if (useSession) sessionStorage.setItem(SESSION_KEY, '1');

      document.body.classList.add('doi-locked');

      var done = false;
      function dismiss() {
        if (done) return; done = true;
        document.body.classList.remove('doi-locked');
        overlay.classList.add('doi--fading');
        setTimeout(function(){ overlay.classList.add('doi--gone'); }, 1200);
      }

      window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'door-animation-done') dismiss();
      });

      // Safety: max 15s
      setTimeout(dismiss, 15000);

      // ESC to skip
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') dismiss();
      });
    })();
    </script>
    <?php
}

// ─── TOGGLE 2: Widget/Shortcode ───────────────────────────────────────────────
// Always register shortcodes — the render function checks widget_enabled at render time.
// This ensures shortcodes work even if the toggle is enabled AFTER a page is cached.
add_action( 'init', 'doi_register_shortcode' );
function doi_register_shortcode() {
    add_shortcode( 'door_open',       'doi_shortcode_render' );
    add_shortcode( 'door_open_intro', 'doi_shortcode_render' ); // alias
}

/**
 * Shortcode: [door_open text="Enter" url="/about" bg="#e8ded5"]
 *
 * @param array $atts  text — button label (overrides plugin setting)
 *                     url  — redirect target after animation
 *                     bg   — overlay background (default transparent)
 */
function doi_shortcode_render( $atts ) {
    // Show a hint in admin view, return nothing in widget-disabled mode on frontend
    if ( ! doi_get( 'widget_enabled' ) ) {
        if ( current_user_can( 'edit_posts' ) ) {
            return '<p style="background:#fff3cd;color:#856404;padding:8px 12px;border-radius:4px;font-size:0.8em;display:inline-block;">
                    ⚠️ <strong>[door_open]</strong>: Enable <em>Widget / Shortcode Mode</em> in
                    <a href="' . admin_url('options-general.php?page=door-open-intro') . '">Door Open Intro settings</a> to activate this shortcode.
                    </p>';
        }
        return '';
    }

    $overlay_file = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    if ( ! file_exists( $overlay_file ) ) {
        return '<p style="color:#c62828;font-size:0.8em;">[door_open] — overlay.html not installed. See <a href="' . admin_url('options-general.php?page=door-open-intro') . '">plugin settings</a>.</p>';
    }

    $atts = shortcode_atts([
        'text' => doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY',
        'url'  => doi_get( 'preload_url' ) ?: '',
        'bg'   => 'transparent',
    ], $atts, 'door_open' );

    // Unique ID so multiple shortcodes on the same page don't conflict
    static $doi_instance = 0;
    $doi_instance++;
    $id  = 'doi-btn-' . $doi_instance;
    $src = doi_overlay_src( 'click', esc_url( $atts['url'] ) );

    ob_start();
    ?>
<div class="doi-widget" id="<?php echo esc_attr( $id ); ?>-wrap">

  <button
    type="button"
    id="<?php echo esc_attr( $id ); ?>"
    class="doi-widget__btn"
    data-doi-src="<?php echo esc_attr( $src ); ?>"
    data-doi-bg="<?php echo esc_attr( $atts['bg'] ); ?>"
    onclick="doiOpenOverlay(this)"
  >
    <span class="doi-widget__label"><?php echo esc_html( $atts['text'] ); ?></span>
    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="width:16px;height:16px;flex-shrink:0;">
      <path d="M4 10H16M16 10L11 5M16 10L11 15"/>
    </svg>
  </button>

</div>

<?php if ( $doi_instance === 1 ): // Output shared CSS + JS only once per page ?>
<style id="doi-widget-styles">
.doi-widget { display: inline-block; }
.doi-widget__btn {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 14px 32px;
  border-radius: 999px;
  background: #9a7470;
  color: #fbf7f4;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  font-weight: 500;
  border: 1.5px solid rgba(196,162,159,0.5);
  cursor: pointer;
  box-shadow: 0 8px 32px rgba(100,55,50,0.4), 0 2px 8px rgba(0,0,0,0.15);
  transition: background 0.25s ease, transform 0.15s ease, box-shadow 0.25s ease;
  position: relative; overflow: hidden;
}
.doi-widget__btn:hover { background: #7f5e5b; box-shadow: 0 12px 40px rgba(100,55,50,0.5); }
.doi-widget__btn:active { transform: scale(0.97); }
/* Full-screen overlay */
#doi-fullscreen-overlay {
  display: none;
  position: fixed; inset: 0;
  width: 100vw; height: 100vh;
  z-index: 999999;
  background: transparent;
  opacity: 1;
  transition: opacity 1.1s cubic-bezier(0.4,0,0.2,1);
}
#doi-fullscreen-overlay.doi--visible { display: block; }
#doi-fullscreen-overlay.doi--fading { opacity: 0; pointer-events: none; }
#doi-fullscreen-overlay iframe {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  border: none; background: transparent;
}
</style>

<!-- Door Open Intro: shared overlay element (created once, reused) -->
<div id="doi-fullscreen-overlay" role="dialog" aria-label="Door animation" aria-hidden="true">
  <iframe
    id="doi-fullscreen-iframe"
    title="Welcome animation"
    allow="autoplay"
    allowtransparency="true"
    scrolling="no"
    frameborder="0"
  ></iframe>
</div>

<script>
/* Door Open Intro — widget click handler (self-contained, no external deps) */
function doiOpenOverlay(btn) {
  var src     = btn.getAttribute('data-doi-src');
  var bg      = btn.getAttribute('data-doi-bg') || 'transparent';
  var overlay = document.getElementById('doi-fullscreen-overlay');
  var iframe  = document.getElementById('doi-fullscreen-iframe');
  if (!overlay || !iframe || !src) { console.warn('[door_open] Missing overlay elements or src'); return; }

  // Set iframe src fresh each time (triggers reload/auto-start)
  iframe.src = src;
  overlay.style.background = bg;
  overlay.setAttribute('aria-hidden', 'false');
  overlay.classList.add('doi--visible');
  overlay.classList.remove('doi--fading');
  document.body.style.overflow = 'hidden';

  var done = false;
  function dismiss() {
    if (done) return; done = true;
    document.body.style.overflow = '';
    overlay.classList.add('doi--fading');
    overlay.setAttribute('aria-hidden', 'true');
    setTimeout(function() {
      overlay.classList.remove('doi--visible', 'doi--fading');
      iframe.src = ''; // reset so next click replays from scratch
    }, 1200);
  }

  // Listen for animation-done message from iframe
  function msgHandler(e) {
    if (e.data && e.data.type === 'door-animation-done') {
      window.removeEventListener('message', msgHandler);
      dismiss();
    }
  }
  window.addEventListener('message', msgHandler);

  // Safety: dismiss after 16s max
  var safety = setTimeout(dismiss, 16000);

  // ESC key to skip
  function onKey(e) {
    if (e.key === 'Escape') {
      clearTimeout(safety);
      window.removeEventListener('message', msgHandler);
      document.removeEventListener('keydown', onKey);
      dismiss();
    }
  }
  document.addEventListener('keydown', onKey);
}
</script>
<?php endif; ?>
    <?php
    return ob_get_clean();
}

// ─── Widget JS removed — now embedded directly in shortcode output (self-contained) ─────
// This ensures it works with ALL page builders, themes, and maintenance plugins.
// The doiOpenOverlay() function is only output when [door_open] shortcode is used.

// ─── (Keeping only the wp_footer hook for backward-compat widget support) ──────────────
add_action( 'wp_footer', 'doi_widget_js_compat', 99 );
function doi_widget_js_compat() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'widget_enabled' ) ) return;
    // Only output if doiOpenOverlay wasn't already defined by a shortcode on this page
    ?>
    <script id="doi-widget-js">
    /* Fallback: define doiOpenOverlay if shortcode wasn't used on this page
       (e.g. the button came from a classic WP widget) */
    if (typeof window.doiOpenOverlay === 'undefined') {
      // Inject the shared overlay DOM if not already present
      if (!document.getElementById('doi-fullscreen-overlay')) {
        var _ov = document.createElement('div');
        _ov.id = 'doi-fullscreen-overlay';
        _ov.setAttribute('role','dialog');
        _ov.setAttribute('aria-label','Door animation');
        _ov.setAttribute('aria-hidden','true');
        _ov.style.cssText = 'display:none;position:fixed;inset:0;width:100vw;height:100vh;z-index:999999;background:transparent;opacity:1;transition:opacity 1.1s cubic-bezier(0.4,0,0.2,1);';
        var _ifr = document.createElement('iframe');
        _ifr.id = 'doi-fullscreen-iframe';
        _ifr.title = 'Welcome animation';
        _ifr.allow = 'autoplay';
        _ifr.setAttribute('allowtransparency','true');
        _ifr.setAttribute('scrolling','no');
        _ifr.setAttribute('frameborder','0');
        _ifr.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;border:none;background:transparent;';
        _ov.appendChild(_ifr);
        document.body.appendChild(_ov);
        var _st = document.createElement('style');
        _st.textContent = '#doi-fullscreen-overlay.doi--visible{display:block}#doi-fullscreen-overlay.doi--fading{opacity:0;pointer-events:none}';
        document.head.appendChild(_st);
      }

      window.doiOpenOverlay = function(btn) {
        var src     = btn.getAttribute('data-doi-src') || btn.getAttribute('data-src');
        var bg      = btn.getAttribute('data-doi-bg')  || btn.getAttribute('data-bg') || 'transparent';
        var overlay = document.getElementById('doi-fullscreen-overlay');
        var iframe  = document.getElementById('doi-fullscreen-iframe');
        if (!overlay || !iframe || !src) return;
        iframe.src = src;
        overlay.style.background = bg;
        overlay.setAttribute('aria-hidden','false');
        overlay.classList.add('doi--visible');
        overlay.classList.remove('doi--fading');
        document.body.style.overflow = 'hidden';
        var done = false;
        function dismiss() {
          if (done) return; done = true;
          document.body.style.overflow = '';
          overlay.classList.add('doi--fading');
          overlay.setAttribute('aria-hidden','true');
          setTimeout(function(){ overlay.classList.remove('doi--visible','doi--fading'); iframe.src=''; }, 1200);
        }
        function msgH(e){ if(e.data&&e.data.type==='door-animation-done'){window.removeEventListener('message',msgH);dismiss();} }
        window.addEventListener('message', msgH);
        var t = setTimeout(dismiss, 16000);
        function onK(e){ if(e.key==='Escape'){clearTimeout(t);window.removeEventListener('message',msgH);document.removeEventListener('keydown',onK);dismiss();} }
        document.addEventListener('keydown', onK);
      };

      // Also support old data-src attribute (event delegation fallback)
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.doi-widget__btn');
        if (btn && !btn.getAttribute('onclick')) window.doiOpenOverlay(btn);
      });
    }
    </script>
    <?php
}


// ─── Register Widget (classic WP sidebar widget) ──────────────────────────────
add_action( 'widgets_init', 'doi_register_widget' );
function doi_register_widget() {
    if ( doi_get( 'widget_enabled' ) ) {
        register_widget( 'DOI_Widget' );
    }
}

class DOI_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'doi_door_widget',
            __( 'Door Open Animation', 'door-open-intro' ),
            [ 'description' => __( 'A cinematic door-opening button that overlays the animation.', 'door-open-intro' ) ]
        );
    }

    public function widget( $args, $instance ) {
        $text     = ! empty( $instance['text'] ) ? $instance['text'] : doi_get( 'button_text' );
        $redirect = ! empty( $instance['url'] )  ? $instance['url']  : '';
        echo $args['before_widget'];
        echo do_shortcode( '[door_open text="' . esc_attr( $text ) . '" url="' . esc_url( $redirect ) . '"]' );
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $text = ! empty( $instance['text'] ) ? $instance['text'] : '';
        $url  = ! empty( $instance['url'] )  ? $instance['url']  : '';
        ?>
        <p>
          <label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Button Text:', 'door-open-intro'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('text'); ?>"
            name="<?php echo $this->get_field_name('text'); ?>" type="text"
            value="<?php echo esc_attr($text); ?>"
            placeholder="<?php echo esc_attr( doi_get('button_text') ); ?>">
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('url'); ?>"><?php _e('Redirect URL (after animation):', 'door-open-intro'); ?></label>
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
    add_options_page(
        'Door Open Intro',
        'Door Open Intro',
        'manage_options',
        'door-open-intro',
        'doi_settings_page'
    );
});

add_action( 'admin_init', function() {
    register_setting( 'doi_settings_group', DOI_OPTIONS, [
        'sanitize_callback' => 'doi_sanitize_settings',
    ]);
});

function doi_sanitize_settings( $input ) {
    $defaults = doi_defaults();
    return [
        'site_intro_enabled' => ! empty( $input['site_intro_enabled'] ) ? 1 : 0,
        'widget_enabled'     => ! empty( $input['widget_enabled'] ) ? 1 : 0,
        'button_text'        => sanitize_text_field( $input['button_text'] ?? $defaults['button_text'] ),
        'preload_url'        => esc_url_raw( $input['preload_url'] ?? '' ),
        'session_once'       => ! empty( $input['session_once'] ) ? 1 : 0,
        'overlay_bg'         => sanitize_hex_color( $input['overlay_bg'] ?? '' ) ?: 'transparent',
    ];
}

function doi_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $saved = false;
    if ( isset( $_POST['_doi_nonce'] ) && wp_verify_nonce( $_POST['_doi_nonce'], 'doi_save' ) ) {
        update_option( DOI_OPTIONS, doi_sanitize_settings( $_POST['doi'] ?? [] ) );
        $saved = true;
    }

    $overlay_file  = DOI_PLUGIN_DIR . 'door-overlay/overlay.html';
    $file_ok       = file_exists( $overlay_file );
    $file_size     = $file_ok ? round( filesize( $overlay_file ) / 1024 ) . ' KB' : '—';
    $intro_enabled = (bool) doi_get( 'site_intro_enabled' );
    $widget_enabled= (bool) doi_get( 'widget_enabled' );
    $btn_text      = esc_attr( doi_get( 'button_text' ) );
    $preload_url   = esc_attr( doi_get( 'preload_url' ) );
    $session_once  = (bool) doi_get( 'session_once' );
    ?>
    <!— inline CSS so we don't need to enqueue a stylesheet just for this page —>
    <style>
      .doi-admin { max-width: 760px; padding: 0 0 40px; }
      .doi-admin h1 { font-size: 1.5rem; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
      .doi-admin .doi-version { font-size: 0.72rem; color: #999; font-weight: 400; letter-spacing: 0.05em; }
      .doi-card {
        background: #fff; border: 1px solid #e2e2e2; border-radius: 10px;
        padding: 24px 28px; margin: 20px 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
      }
      .doi-card h2 { margin: 0 0 6px; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
      .doi-card p.doi-desc { color: #666; font-size: 0.85rem; margin: 0 0 20px; }
      /* Toggle switch */
      .doi-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
      .doi-toggle-label { font-size: 0.92rem; color: #1d2327; }
      .doi-toggle-label small { display: block; color: #888; font-size: 0.78rem; margin-top: 2px; }
      .doi-switch { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
      .doi-switch input { opacity: 0; width: 0; height: 0; }
      .doi-slider {
        position: absolute; cursor: pointer; inset: 0;
        background: #ccc; border-radius: 26px;
        transition: 0.3s;
      }
      .doi-slider:before {
        content: ''; position: absolute;
        height: 20px; width: 20px; left: 3px; bottom: 3px;
        background: #fff; border-radius: 50%;
        transition: 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,0.2);
      }
      .doi-switch input:checked + .doi-slider { background: #9a7470; }
      .doi-switch input:checked + .doi-slider:before { transform: translateX(22px); }
      /* Field rows */
      .doi-field-row { margin: 16px 0 0; }
      .doi-field-row label { display: block; font-size: 0.85rem; color: #555; margin-bottom: 5px; font-weight: 600; }
      .doi-field-row input[type=text],
      .doi-field-row input[type=url] {
        width: 100%; max-width: 440px;
        padding: 9px 12px; border: 1px solid #d0d0d0;
        border-radius: 6px; font-size: 0.88rem;
        transition: border-color 0.2s;
      }
      .doi-field-row input:focus { border-color: #9a7470; outline: none; box-shadow: 0 0 0 2px rgba(154,116,112,0.15); }
      .doi-field-row .doi-hint { font-size: 0.76rem; color: #888; margin-top: 4px; }
      /* Status badge */
      .doi-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
      .doi-badge--ok  { background: #e8f5e9; color: #2e7d32; }
      .doi-badge--err { background: #fdecea; color: #c62828; }
      /* Shortcode preview */
      .doi-shortcode-box {
        background: #f6f7f7; border: 1px solid #e2e2e2;
        border-radius: 6px; padding: 10px 14px;
        font-family: monospace; font-size: 0.82rem; color: #444;
        margin: 12px 0 0;
      }
      /* Save button */
      .doi-save-row { margin: 24px 0 0; display: flex; align-items: center; gap: 14px; }
      .doi-save-btn {
        padding: 10px 28px; background: #9a7470; color: #fff;
        border: none; border-radius: 6px; font-size: 0.9rem; cursor: pointer;
        font-weight: 600; transition: background 0.2s;
      }
      .doi-save-btn:hover { background: #7f5e5b; }
      .doi-saved-msg { color: #2e7d32; font-size: 0.85rem; font-weight: 600; }
      /* Divider in card */
      .doi-divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>

    <div class="wrap doi-admin">
      <h1>
        <span>🚪</span>
        Door Open Intro
        <span class="doi-version">v<?php echo esc_html( DOI_VERSION ); ?></span>
      </h1>
      <p style="color:#666; margin:0 0 4px; font-size:0.88rem;">
        Cinematic door animation for WordPress — site intro &amp; widget mode.
      </p>

      <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible"><p><strong>Settings saved!</strong></p></div>
      <?php endif; ?>

      <!-- Status -->
      <?php if ($file_ok): ?>
        <p style="margin:8px 0;">
          <span class="doi-badge doi-badge--ok">✅ overlay.html installed (<?php echo esc_html($file_size); ?>)</span>
        </p>
      <?php else: ?>
        <p style="margin:8px 0;">
          <span class="doi-badge doi-badge--err">❌ overlay.html not found — see installation steps below</span>
        </p>
      <?php endif; ?>

      <form method="post" action="">
        <?php wp_nonce_field( 'doi_save', '_doi_nonce' ); ?>

        <!-- ══ Toggle 1: Site Intro ════════════════════════════════════════ -->
        <div class="doi-card">
          <h2>🎬 Site Intro Mode</h2>
          <p class="doi-desc">
            Automatically plays the door animation when visitors arrive at your site.
            The stone arch appears over your page — the website is visible through the transparent doorway hole.
            Doors swing open → camera zooms through → overlay fades away.
          </p>

          <div class="doi-toggle-row">
            <div class="doi-toggle-label">
              Enable Site Intro
              <small>Show the door animation automatically when the site loads</small>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="doi[site_intro_enabled]" value="1"
                <?php checked( $intro_enabled ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <hr class="doi-divider">

          <div class="doi-toggle-row">
            <div class="doi-toggle-label">
              Show Once Per Session
              <small>If ON — animation plays once per browser session. If OFF — plays on every page load.</small>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="doi[session_once]" value="1"
                <?php checked( $session_once ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>
        </div>

        <!-- ══ Toggle 2: Widget / Shortcode ══════════════════════════════ -->
        <div class="doi-card">
          <h2>🧩 Widget / Shortcode Mode</h2>
          <p class="doi-desc">
            Adds a styled button anywhere on your site. Clicking it triggers the door animation as a full-screen overlay.
            Perfect for landing sections, hero areas, or calls-to-action.
          </p>

          <div class="doi-toggle-row">
            <div class="doi-toggle-label">
              Enable Widget &amp; Shortcode
              <small>Activates <code>[door_open]</code> shortcode and the "Door Open Animation" sidebar widget</small>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="doi[widget_enabled]" value="1"
                <?php checked( $widget_enabled ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <?php if ($widget_enabled): ?>
          <hr class="doi-divider">
          <p style="font-size:0.85rem; color:#555; margin:0 0 6px;">
            <strong>Shortcode usage:</strong>
          </p>
          <div class="doi-shortcode-box">[door_open text="Enter" url="/about"]</div>
          <div class="doi-shortcode-box" style="margin-top:6px;">[door_open]  &nbsp;← uses settings below as defaults</div>
          <p style="font-size:0.76rem; color:#888; margin:6px 0 0;">
            <code>text</code> — button label &nbsp;|&nbsp;
            <code>url</code> — page to navigate to after the animation completes
          </p>
          <?php endif; ?>
        </div>

        <!-- ══ Global Settings ════════════════════════════════════════════ -->
        <div class="doi-card">
          <h2>⚙️ Global Settings</h2>
          <p class="doi-desc">These settings apply to both Site Intro and Widget modes.</p>

          <div class="doi-field-row">
            <label for="doi-btn-text">Button Text</label>
            <input
              type="text"
              id="doi-btn-text"
              name="doi[button_text]"
              value="<?php echo $btn_text; ?>"
              placeholder="JOIN THE JOURNEY"
              maxlength="60"
            >
            <p class="doi-hint">
              The label shown on the button / CTA text above the doorway.<br>
              In site intro mode this is a decorative label. In widget mode it's the clickable button.
            </p>
          </div>

          <div class="doi-field-row" style="margin-top:20px;">
            <label for="doi-preload-url">Default Target Page URL</label>
            <input
              type="url"
              id="doi-preload-url"
              name="doi[preload_url]"
              value="<?php echo $preload_url; ?>"
              placeholder="<?php echo esc_attr( home_url('/') ); ?>"
            >
            <p class="doi-hint">
              <strong>For widget/shortcode mode:</strong> the page to navigate to after the animation finishes.<br>
              Leave blank to stay on the current page. The <code>url</code> shortcode attribute overrides this per-button.
            </p>
          </div>
        </div>

        <!-- ══ Installation ═══════════════════════════════════════════════ -->
        <div class="doi-card">
          <h2>📂 Installation / Update</h2>
          <ol style="line-height: 2; color: #555; margin: 0 0 0 18px;">
            <li>In the React project: run <code style="background:#f6f7f7;padding:2px 6px;border-radius:3px;">npm run build:overlay</code></li>
            <li>Copy <code>dist-overlay/overlay.html</code> → <code>wp-content/plugins/door-open-intro/door-overlay/overlay.html</code></li>
            <li>
              Expected file path:<br>
              <code style="font-size:0.78rem; color:#666;"><?php echo esc_html( $overlay_file ); ?></code>
            </li>
          </ol>
          <hr class="doi-divider">
          <p style="margin:0;">
            <a href="<?php echo esc_url( DOI_PLUGIN_URL . 'door-overlay/overlay.html?trigger=click&btn=Preview+Door' ); ?>"
               target="_blank" class="button button-secondary">
              Preview Overlay (click mode) ↗
            </a>
            &nbsp;
            <a href="<?php echo esc_url( home_url('/') ); ?>" target="_blank" class="button button-primary">
              View Homepage ↗
            </a>
          </p>
          <p style="font-size:0.76rem; color:#888; margin:10px 0 0;">
            <em>Tip: To replay the animation, go to DevTools → Application → Session Storage → delete <code>doi_v2_shown</code></em>
          </p>
        </div>

        <!-- Save -->
        <div class="doi-save-row">
          <button type="submit" class="doi-save-btn">💾 Save Settings</button>
          <?php if ($saved): ?>
            <span class="doi-saved-msg">✅ Settings saved successfully!</span>
          <?php endif; ?>
        </div>

      </form>
    </div>
    <?php
}
