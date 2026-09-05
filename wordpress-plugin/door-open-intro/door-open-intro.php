<?php
/**
 * Plugin Name:  Door Open Intro
 * Plugin URI:   https://github.com/EmonAhammed1/Door-Open
 * Description:  Cinematic 3D door-opening animation overlay for WordPress. Pure CSS3 3D transforms & Web Audio API. 100% compatible with Elementor, Gutenberg, and all themes.
 * Version:      3.2.0
 * Author:       Door Open
 * License:      GPL-2.0-or-later
 * Text Domain:  door-open-intro
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'DOI_VERSION',    '3.2.0' );
define( 'DOI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DOI_OPTIONS',    'doi_settings' );

// ─── Default settings ─────────────────────────────────────────────────────────
function doi_defaults() {
    return [
        'site_intro_enabled' => 1,
        'widget_enabled'     => 1,
        'sound_enabled'      => 1,
        'session_once'       => 1,
        'button_text'        => 'JOIN THE JOURNEY',
        'preload_url'        => '',
        'door_image_url'     => DOI_PLUGIN_URL . 'assets/threshold-doors.jpg',
        'room_image_url'     => DOI_PLUGIN_URL . 'assets/room-interior.jpg',
    ];
}

function doi_get( $key ) {
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    return $opts[ $key ] ?? null;
}

// ─── Enqueue Frontend Assets (CSS + JS) ──────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'doi_enqueue_frontend_assets' );
function doi_enqueue_frontend_assets() {
    if ( is_admin() ) return;

    $intro_on  = (bool) doi_get( 'site_intro_enabled' );
    $widget_on = (bool) doi_get( 'widget_enabled' );

    // Enqueue if either mode is active
    if ( ! $intro_on && ! $widget_on ) return;

    // Enqueue 3D Door CSS
    wp_enqueue_style(
        'doi-door-open',
        DOI_PLUGIN_URL . 'assets/door-open.css',
        [],
        DOI_VERSION
    );

    // Enqueue 3D Door JS
    wp_enqueue_script(
        'doi-door-open',
        DOI_PLUGIN_URL . 'assets/door-open.js',
        [],
        DOI_VERSION,
        true // in footer
    );

    // Pass settings directly to JavaScript
    $door_img = doi_get( 'door_image_url' ) ?: DOI_PLUGIN_URL . 'assets/threshold-doors.jpg';
    $room_img = doi_get( 'room_image_url' ) ?: DOI_PLUGIN_URL . 'assets/room-interior.jpg';
    $btn_text = doi_get( 'button_text' )     ?: 'JOIN THE JOURNEY';
    $url      = doi_get( 'preload_url' )     ?: '';

    wp_localize_script( 'doi-door-open', 'DOI_CONFIG', [
        'siteIntroEnabled' => $intro_on,
        'widgetEnabled'    => $widget_on,
        'soundEnabled'     => (bool) doi_get( 'sound_enabled' ),
        'sessionOnce'      => (bool) doi_get( 'session_once' ),
        'buttonText'       => esc_html( $btn_text ),
        'preloadUrl'       => esc_url( $url ),
        'doorImageUrl'     => esc_url( $door_img ),
        'roomImageUrl'     => esc_url( $room_img ),
        'pluginUrl'        => esc_url( DOI_PLUGIN_URL ),
    ] );
}

// ─── Site Intro Overlay (Toggle 1) ───────────────────────────────────────────
// Placed in wp_footer so page content renders first underneath the doors
add_action( 'wp_footer', 'doi_render_site_intro_overlay', 1 );
function doi_render_site_intro_overlay() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'site_intro_enabled' ) ) return;

    $door_img  = doi_get( 'door_image_url' ) ?: DOI_PLUGIN_URL . 'assets/threshold-doors.jpg';
    $room_img  = doi_get( 'room_image_url' ) ?: DOI_PLUGIN_URL . 'assets/room-interior.jpg';
    $btn_text  = doi_get( 'button_text' )     ?: 'JOIN THE JOURNEY';
    $url       = doi_get( 'preload_url' )     ?: '';
    $skip_key  = doi_get( 'session_once' )    ? 'doi_session_entered' : '';

    echo doi_get_threshold_html( [
        'door_img' => $door_img,
        'room_img' => $room_img,
        'btn_text' => $btn_text,
        'url'      => $url,
        'skip_key' => $skip_key,
    ] );
}

// ─── Threshold HTML Template ─────────────────────────────────────────────────
function doi_get_threshold_html( $args = [] ) {
    $door_img = esc_url( $args['door_img'] ?? ( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ) );
    $room_img = esc_url( $args['room_img'] ?? ( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ) );
    $btn_text = esc_html( $args['btn_text'] ?? ( doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY' ) );
    $url      = esc_attr( $args['url'] ?? ( doi_get( 'preload_url' ) ?: '' ) );
    $skip_key = esc_attr( $args['skip_key'] ?? '' );

    ob_start();
    ?>
    <section id="tar-threshold" class="tar-threshold"
             data-door-left="35.0" data-door-right="65.0" data-door-top="16.4" data-door-bottom="86.9"
             data-lintel-y="9.0"
             data-home-url="<?php echo $url; ?>"
             data-skip-key="<?php echo $skip_key; ?>">

      <!-- 3D Scene Layer -->
      <div class="tar-scene">
        <div class="tar-scene__base" data-src="<?php echo $door_img; ?>" style="background-image: url('<?php echo $door_img; ?>');"></div>
        <div class="tar-scene__clip">
          <div class="tar-scene__room" data-src="<?php echo $room_img; ?>" style="background-image: url('<?php echo $room_img; ?>');"></div>
        </div>
        <div class="tar-scene__panels">
          <div class="tar-panel tar-panel--left"><span class="tar-panel__shade"></span></div>
          <div class="tar-panel tar-panel--right"><span class="tar-panel__shade"></span></div>
          <div class="tar-seam"></div>
        </div>
      </div>

      <div class="tar-glow"></div>
      <div class="tar-flicker"></div>
      <div class="tar-vignette"></div>
      <div class="tar-grain"></div>

      <!-- ===== UI LAYER ===== -->
      <div class="tar-threshold__ui">
        <header class="tar-topbar">
          <div class="tar-logo">
            <svg class="tar-logo__droplet" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
              <circle cx="20" cy="32" r="7" />
              <circle cx="20" cy="22" r="1.2" fill="currentColor" />
              <circle cx="20" cy="26" r="1.2" fill="currentColor" />
              <circle cx="20" cy="30" r="1.2" fill="currentColor" />
            </svg>
            <span class="tar-logo__name">D R U M I</span>
            <span class="tar-logo__tag">A sanctuary for your dreams</span>
          </div>
          <nav class="tar-topbar__nav">
            <button class="tar-nav-link" type="button" data-tar-sound aria-pressed="false">Sound <span class="tar-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></span></button>
          </nav>
        </header>

        <div class="tar-threshold__content">
          <div class="tar-threshold__scrim">
            <div class="tar-emblem-wrap">
              <svg class="tar-emblem" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
                <circle cx="20" cy="32" r="7" />
                <circle cx="20" cy="22" r="1.2" fill="currentColor" />
                <circle cx="20" cy="26" r="1.2" fill="currentColor" />
                <circle cx="20" cy="30" r="1.2" fill="currentColor" />
              </svg>
            </div>
            <h1 class="tar-display tar-drumi-title">D R U M I</h1>
            <div class="tar-drumi-sub">
              <p>A sanctuary for your dreams.</p>
              <p>A journey back to yourself.</p>
            </div>
            <div class="tar-btn-container">
              <button class="tar-btn" type="button" data-tar-enter
                      style="background-color: #9a7470 !important; background: #9a7470 !important; color: #ffffff !important; padding: 16px 48px !important; border: 1px solid rgba(215, 185, 180, 0.5) !important; border-radius: 4px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; text-decoration: none !important; box-shadow: 0 8px 26px rgba(80, 45, 45, 0.38) !important; font-family: 'Cinzel', serif !important; font-size: 14px !important; letter-spacing: 0.26em !important; text-transform: uppercase !important; font-weight: 500 !important; line-height: 1.2 !important; cursor: pointer !important; opacity: 1 !important; visibility: visible !important;">
                <span style="color: #ffffff !important; font-family: inherit !important; letter-spacing: inherit !important; font-weight: inherit !important; text-transform: uppercase !important;"><?php echo $btn_text; ?></span>
              </button>
              <span class="tar-btn-sub" style="display: block !important; margin-top: 13px !important; font-family: 'Cinzel', serif !important; font-size: 11px !important; letter-spacing: 0.32em !important; text-transform: uppercase !important; font-weight: 500 !important; color: rgba(225, 210, 195, 0.88) !important; text-shadow: 0 1px 8px rgba(0, 0, 0, 0.85) !important; text-align: center !important;">STEP INTO YOUR INNER WORLD</span>
            </div>
          </div>
        </div>

        <footer class="tar-threshold__foot">
          <p class="tar-smallcaps">Slow Down.<br>Listen Within.</p>
          <p class="tar-smallcaps tar-threshold__foot-right">Trust The Message.<br>Return To You.</p>
        </footer>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

// ─── Shortcode: [door_open] (Toggle 2) ────────────────────────────────────────
add_action( 'init', 'doi_register_shortcodes' );
function doi_register_shortcodes() {
    add_shortcode( 'door_open',       'doi_shortcode_render' );
    add_shortcode( 'door_open_intro', 'doi_shortcode_render' ); // alias
}

function doi_shortcode_render( $atts ) {
    $atts = shortcode_atts( [
        'mode'     => 'button', // 'button' or 'intro'
        'text'     => doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY',
        'url'      => doi_get( 'preload_url' ) ?: '',
        'door_img' => doi_get( 'door_image_url' ) ?: ( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ),
        'room_img' => doi_get( 'room_image_url' ) ?: ( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ),
    ], $atts, 'door_open' );

    // Ensure assets are loaded even if page builders bypass standard footer enqueue
    if ( ! wp_script_is( 'doi-door-open', 'enqueued' ) ) {
        doi_enqueue_frontend_assets();
    }

    // Mode: full threshold intro embed
    if ( strtolower( $atts['mode'] ) === 'intro' ) {
        return doi_get_threshold_html( [
            'door_img' => $atts['door_img'],
            'room_img' => $atts['room_img'],
            'btn_text' => $atts['text'],
            'url'      => $atts['url'],
            'skip_key' => '',
        ] );
    }

    // Mode: interactive trigger button
    return sprintf(
        '<div class="doi-widget-button-wrap">
           <button
             type="button"
             class="doi-btn"
             data-doi-trigger="1"
             data-doi-url="%s"
             data-door-img="%s"
             data-room-img="%s"
             data-btn-text="%s"
             onclick="window.doiOpenDoor && window.doiOpenDoor(this)"
             aria-label="%s"
             style="background-color: #9a7470 !important; background: #9a7470 !important; color: #ffffff !important; padding: 16px 48px !important; border: 1px solid rgba(215, 185, 180, 0.5) !important; border-radius: 4px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; text-decoration: none !important; box-shadow: 0 8px 26px rgba(80, 45, 45, 0.38) !important; font-family: \'Cinzel\', serif !important; font-size: 14px !important; letter-spacing: 0.26em !important; text-transform: uppercase !important; font-weight: 500 !important; line-height: 1.2 !important; cursor: pointer !important; opacity: 1 !important; visibility: visible !important;"
           >
             <span style="color: #ffffff !important; font-family: inherit !important; letter-spacing: inherit !important; font-weight: inherit !important; text-transform: uppercase !important;">%s</span>
           </button>
           <span class="doi-btn-sub" style="display: block !important; margin-top: 13px !important; font-family: \'Cinzel\', serif !important; font-size: 11px !important; letter-spacing: 0.32em !important; text-transform: uppercase !important; font-weight: 500 !important; color: rgba(225, 210, 195, 0.88) !important; text-shadow: 0 1px 8px rgba(0, 0, 0, 0.85) !important; text-align: center !important;">STEP INTO YOUR INNER WORLD</span>
         </div>',
        esc_url( $atts['url'] ),
        esc_url( $atts['door_img'] ),
        esc_url( $atts['room_img'] ),
        esc_attr( $atts['text'] ),
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
            [ 'description' => __( 'Cinematic 3D door-opening trigger button.', 'door-open-intro' ) ]
        );
    }
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        $text = ! empty( $instance['text'] ) ? $instance['text'] : ( doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY' );
        $url  = ! empty( $instance['url'] )  ? $instance['url']  : ( doi_get( 'preload_url' ) ?: '' );
        echo do_shortcode( '[door_open text="' . esc_attr( $text ) . '" url="' . esc_attr( $url ) . '"]' );
        echo $args['after_widget'];
    }
    public function form( $instance ) {
        $text = $instance['text'] ?? 'JOIN THE JOURNEY';
        $url  = $instance['url']  ?? '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Button Label:' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $text ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php _e( 'Target Page URL (leave blank to reveal current page):' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>"
                   type="url" value="<?php echo esc_attr( $url ); ?>">
        </p>
        <?php
    }
    public function update( $new, $old ) {
        return [
            'text' => sanitize_text_field( $new['text'] ?? '' ),
            'url'  => esc_url_raw( $new['url'] ?? '' ),
        ];
    }
}

// ─── Settings Page (WP Admin → Settings → Door Open Intro) ───────────────────
add_action( 'admin_menu', 'doi_admin_menu' );
function doi_admin_menu() {
    add_options_page(
        __( 'Door Open Intro Settings', 'door-open-intro' ),
        __( 'Door Open Intro', 'door-open-intro' ),
        'manage_options',
        'door-open-intro',
        'doi_settings_page'
    );
}

add_action( 'admin_init', 'doi_register_settings' );
function doi_register_settings() {
    register_setting( 'doi_settings_group', DOI_OPTIONS, [
        'type'              => 'array',
        'sanitize_callback' => 'doi_sanitize_settings',
    ] );
}

function doi_sanitize_settings( $in ) {
    return [
        'site_intro_enabled' => empty( $in['site_intro_enabled'] ) ? 0 : 1,
        'widget_enabled'     => empty( $in['widget_enabled'] )     ? 0 : 1,
        'sound_enabled'      => empty( $in['sound_enabled'] )      ? 0 : 1,
        'session_once'       => empty( $in['session_once'] )       ? 0 : 1,
        'button_text'        => sanitize_text_field( $in['button_text'] ?? 'JOIN THE JOURNEY' ),
        'preload_url'        => esc_url_raw( $in['preload_url'] ?? '' ),
        'door_image_url'     => esc_url_raw( $in['door_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ) ),
        'room_image_url'     => esc_url_raw( $in['room_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ) ),
    ];
}

function doi_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    ?>
    <div class="wrap" style="max-width:860px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
      <h1 style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:1.4em;">🚪</span>
        <strong>Door Open Intro</strong>
        <span style="font-size:13px; font-weight:normal; background:#e8d5a3; color:#4a3722; padding:3px 10px; border-radius:12px;">v<?php echo DOI_VERSION; ?></span>
      </h1>
      <p style="color:#666; font-size:1.05em; margin-top:2px;">
        Cinematic 3D door-opening animation with procedural audio. Compatible with Elementor, Gutenberg, and all page builders.
      </p>

      <?php settings_errors(); ?>

      <form method="post" action="options.php" style="background:#fff; border:1px solid #ccd0d4; border-radius:10px; padding:28px; box-shadow:0 3px 15px rgba(0,0,0,0.05); margin-top:20px;">
        <?php settings_fields( 'doi_settings_group' ); ?>

        <style>
          .doi-toggle-row { display:flex; justify-content:space-between; align-items:flex-start; padding:18px 0; border-bottom:1px solid #f0f0f1; }
          .doi-toggle-row:last-child { border-bottom:none; }
          .doi-toggle-info { flex:1; padding-right:24px; }
          .doi-toggle-info strong { font-size:1.05em; color:#1d2327; display:block; margin-bottom:4px; }
          .doi-toggle-info p { margin:0; color:#646970; font-size:0.9em; line-height:1.5; }
          .doi-switch { position:relative; display:inline-block; width:52px; height:28px; flex-shrink:0; margin-top:4px; }
          .doi-switch input { opacity:0; width:0; height:0; }
          .doi-slider { position:absolute; cursor:pointer; inset:0; background-color:#ccc; border-radius:28px; transition:0.3s cubic-bezier(0.4,0,0.2,1); }
          .doi-slider:before { position:absolute; content:""; height:20px; width:20px; left:4px; bottom:4px; background-color:white; border-radius:50%; transition:0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:0 2px 4px rgba(0,0,0,0.2); }
          input:checked + .doi-slider { background-color:#c9a66b; }
          input:checked + .doi-slider:before { transform:translateX(24px); }
          .doi-field-row { padding:18px 0; border-bottom:1px solid #f0f0f1; }
          .doi-field-row label { display:block; font-weight:600; margin-bottom:6px; color:#1d2327; }
          .doi-field-row input[type="text"], .doi-field-row input[type="url"] { width:100%; max-width:540px; padding:8px 12px; font-size:14px; border:1px solid #8c8f94; border-radius:6px; }
          .doi-badge { display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:2px 8px; border-radius:4px; }
          .doi-badge--primary { background:#e8f0fe; color:#1a73e8; }
          .doi-badge--gold { background:#fbf3e6; color:#9d7a3f; }
        </style>

        <div class="doi-toggle-row">
          <div class="doi-toggle-info">
            <strong style="display:flex; align-items:center; gap:8px;">
              <span class="doi-badge doi-badge--primary">Mode 1</span>
              Site Intro Mode (Website Opening)
            </strong>
            <p>When someone visits your website, show the cinematic full-screen door intro. The visitor clicks the button (or presses Enter), the procedural sound swells, the 3D doors swing open, camera zooms in, and reveals your website seamlessly.</p>
          </div>
          <label class="doi-switch">
            <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[site_intro_enabled]" value="1" <?php checked( $opts['site_intro_enabled'], 1 ); ?>>
            <span class="doi-slider"></span>
          </label>
        </div>

        <div class="doi-toggle-row">
          <div class="doi-toggle-info">
            <strong style="display:flex; align-items:center; gap:8px;">
              <span class="doi-badge doi-badge--gold">Mode 2</span>
              Widget / Shortcode Mode [door_open]
            </strong>
            <p>Enable the <code>[door_open]</code> shortcode to place door-opening buttons in any Elementor section, container, or page.</p>
          </div>
          <label class="doi-switch">
            <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[widget_enabled]" value="1" <?php checked( $opts['widget_enabled'], 1 ); ?>>
            <span class="doi-slider"></span>
          </label>
        </div>

        <div class="doi-toggle-row">
          <div class="doi-toggle-info">
            <strong>Procedural Ambient Sound (Web Audio)</strong>
            <p>Plays atmospheric drone, candle crackle, and harmonic door swell. Synthesized 100% in browser with Web Audio API — zero audio file downloads.</p>
          </div>
          <label class="doi-switch">
            <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[sound_enabled]" value="1" <?php checked( $opts['sound_enabled'], 1 ); ?>>
            <span class="doi-slider"></span>
          </label>
        </div>

        <div class="doi-toggle-row">
          <div class="doi-toggle-info">
            <strong>Show Once Per Visitor Session</strong>
            <p>When enabled, repeat visitors in the same browser session skip the intro doors and land directly on your site.</p>
          </div>
          <label class="doi-switch">
            <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[session_once]" value="1" <?php checked( $opts['session_once'], 1 ); ?>>
            <span class="doi-slider"></span>
          </label>
        </div>

        <div class="doi-field-row">
          <label for="doi_button_text">Button Text</label>
          <input type="text" id="doi_button_text" name="<?php echo DOI_OPTIONS; ?>[button_text]"
                 value="<?php echo esc_attr( $opts['button_text'] ); ?>" placeholder="JOIN THE JOURNEY">
          <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">The label shown on the door trigger button (e.g. "JOIN THE JOURNEY").</p>
        </div>

        <div class="doi-field-row">
          <label for="doi_preload_url">Target / Destination URL (Optional)</label>
          <input type="text" id="doi_preload_url" name="<?php echo DOI_OPTIONS; ?>[preload_url]"
                 value="<?php echo esc_attr( $opts['preload_url'] ); ?>" placeholder="/home/ or https://yoursite.com/target">
          <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">Leave completely <strong>blank</strong> to smoothly reveal the current WordPress page underneath the doors!</p>
        </div>

        <div class="doi-field-row">
          <label for="doi_door_image_url">Door Artwork URL</label>
          <input type="text" id="doi_door_image_url" name="<?php echo DOI_OPTIONS; ?>[door_image_url]"
                 value="<?php echo esc_attr( $opts['door_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ); ?>">
          <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">Default closed door image. You can replace with any Media Library file URL.</p>
        </div>

        <div class="doi-field-row">
          <label for="doi_room_image_url">Interior Room Artwork URL</label>
          <input type="text" id="doi_room_image_url" name="<?php echo DOI_OPTIONS; ?>[room_image_url]"
                 value="<?php echo esc_attr( $opts['room_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ); ?>">
          <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">Image visible through the door opening as the doors open.</p>
        </div>

        <p class="submit" style="padding-top:16px;">
          <input type="submit" name="submit" id="submit" class="button button-primary" style="background:#c9a66b; border-color:#9d7a3f; font-weight:600; padding:4px 22px; height:auto; font-size:14px;" value="Save Changes">
        </p>
      </form>

      <!-- Shortcode Guide Card -->
      <div style="background:#fff; border:1px solid #ccd0d4; border-radius:10px; padding:24px; margin-top:24px; box-shadow:0 3px 15px rgba(0,0,0,0.05);">
        <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
          <span>🧩</span> How to Use Shortcode in Elementor & Gutenberg
        </h3>
        <p style="color:#50575e; margin-bottom:14px;">Drop an <strong>HTML Widget</strong> or <strong>Shortcode Block</strong> anywhere and use:</p>

        <table class="widefat striped" style="border-radius:6px; overflow:hidden;">
          <thead>
            <tr>
              <th style="width:40%;">Shortcode</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>[door_open]</code></td>
              <td>Creates a luxury button with default label. Clicking plays door animation.</td>
            </tr>
            <tr>
              <td><code>[door_open text="JOIN THE JOURNEY"]</code></td>
              <td>Custom button text.</td>
            </tr>
            <tr>
              <td><code>[door_open text="ENTER" url="/about"]</code></td>
              <td>Opens doors and navigates to <code>/about</code>.</td>
            </tr>
            <tr>
              <td><code>[door_open mode="intro"]</code></td>
              <td>Embeds the full-screen 3D door threshold directly inside this page or Canvas!</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}
