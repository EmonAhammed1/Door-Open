<?php
/**
 * Plugin Name:  Door Open Intro
 * Plugin URI:   https://github.com/EmonAhammed1/Door-Open
 * Description:  Cinematic 3D door-opening hero animation & DRUMI sanctuary landing sections for WordPress. Pure CSS3 3D transforms & Web Audio API. Compatible with Elementor, Gutenberg, and all themes.
 * Version:      3.4.1
 * Author:       Door Open
 * License:      GPL-2.0-or-later
 * Text Domain:  door-open-intro
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'DOI_VERSION',    '3.4.1' );
define( 'DOI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DOI_OPTIONS',    'doi_settings' );

// ─── Default settings ─────────────────────────────────────────────────────────
function doi_defaults() {
    return [
        // Mode & Mechanics
        'display_mode'        => 'hero_landing', // 'hero_landing' (Door as Hero + 3 Sections) | 'overlay_intro' (Fixed Fullscreen Overlay)
        'site_intro_enabled'  => 1,
        'widget_enabled'      => 1,
        'sound_enabled'       => 1,
        'session_once'        => 0,
        'target_slugs'        => 'home',
        'button_text'         => 'JOIN THE JOURNEY',
        'button_subtext'      => 'STEP INTO YOUR INNER WORLD',
        'preload_url'         => '',
        'door_image_url'      => DOI_PLUGIN_URL . 'assets/threshold-doors.jpg',
        'room_image_url'      => DOI_PLUGIN_URL . 'assets/room-interior.jpg',

        // Landing Sections Enabled
        'sections_enabled'    => 1,

        // Section 1: Our Founder
        'founder_enabled'     => 1,
        'founder_kicker'      => 'OUR FOUNDER',
        'founder_title'       => "This is a journey\nI once had to take.",
        'founder_text'        => "Through my dreams, I found the wisdom I didn't know I was looking for. DRUMI was created to help you do the same — gently, honestly, and in your own time.",
        'founder_btn_text'    => 'READ MY STORY',
        'founder_btn_url'     => '#founder-story',
        'founder_image_url'   => DOI_PLUGIN_URL . 'assets/founder-photo.png',

        // Section 2: Our Vision
        'vision_enabled'      => 1,
        'vision_kicker'       => 'OUR VISION',
        'vision_title'        => 'Remember. Understand. Integrate.',
        'vision_text'         => "We believe your dreams are more than stories. They are messages from within, guiding you back to what truly matters.",

        // Section 3: Feature Cards & Footer
        'cards_enabled'       => 1,
        // Card 1 (Dream Journal)
        'card1_title'         => 'THE DREAM JOURNAL',
        'card1_text'          => 'Your space to remember, reflect and receive.',
        'card1_btn_text'      => 'DISCOVER THE JOURNAL',
        'card1_btn_url'       => '#dream-journal',
        'card1_image_url'     => DOI_PLUGIN_URL . 'assets/card-journal.png',
        // Card 2 (Blog)
        'card2_title'         => 'THE BLOG',
        'card2_text'          => 'Insights, inspiration and guidance for your journey.',
        'card2_btn_text'      => 'EXPLORE THE BLOG',
        'card2_btn_url'       => '#blog',
        'card2_image_url'     => DOI_PLUGIN_URL . 'assets/card-blog.png',

        // Sacred Footer Bar
        'footer_enabled'      => 1,
        'footer_mantra1'      => 'SLOW DOWN',
        'footer_mantra1_url'  => '#',
        'footer_mantra2'      => 'LISTEN WITHIN',
        'footer_mantra2_url'  => '#',
        'footer_brand'        => 'DRUMI',
        'footer_brand_url'    => '/',
        'footer_mantra3'      => 'TRUST THE MESSAGE',
        'footer_mantra3_url'  => '#',
        'footer_mantra4'      => 'RETURN TO YOU',
        'footer_mantra4_url'  => '#',
    ];
}

function doi_get( $key ) {
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    return $opts[ $key ] ?? null;
}

// ─── Target Page Detection (/ and /home) ─────────────────────────────────────
function doi_is_target_page() {
    if ( is_admin() ) return false;

    // Standard WordPress Front Page or Home check
    if ( is_front_page() || is_home() ) {
        return true;
    }

    if ( function_exists( 'is_page' ) && ( is_page( 'home' ) || is_page( 'homepage' ) || is_page( 'front-page' ) ) ) {
        return true;
    }

    // Check request path for root "/" or "/home" or "/home/"
    $uri  = $_SERVER['REQUEST_URI'] ?? '';
    $path = trim( parse_url( $uri, PHP_URL_PATH ), '/' );

    if ( $path === '' || strtolower( $path ) === 'home' || strtolower( $path ) === 'index.php' ) {
        return true;
    }

    // Also check custom slugs configured in settings if any
    $target_slugs = doi_get( 'target_slugs' );
    if ( ! empty( $target_slugs ) ) {
        $slugs = array_filter( array_map( 'trim', explode( ',', strtolower( $target_slugs ) ) ) );
        if ( in_array( strtolower( $path ), $slugs, true ) ) {
            return true;
        }
    }

    return false;
}

// ─── Homepage Takeover Template Filter ────────────────────────────────────────
// When visiting site root (/) or /home, serve the pure DRUMI Canvas landing page directly!
// Bypasses Astra's theme header, footer, and default blog archive.
add_filter( 'template_include', 'doi_homepage_template', 999 );
function doi_homepage_template( $template ) {
    if ( is_admin() ) return $template;
    if ( ! doi_get( 'site_intro_enabled' ) ) return $template;

    $mode = doi_get( 'display_mode' ) ?: 'hero_landing';
    if ( $mode !== 'hero_landing' ) return $template;

    if ( doi_is_target_page() ) {
        $custom_tpl = DOI_PLUGIN_DIR . 'templates/landing-page.php';
        if ( file_exists( $custom_tpl ) ) {
            return $custom_tpl;
        }
    }

    return $template;
}

// ─── Enqueue Frontend Assets (CSS + JS) ──────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'doi_enqueue_frontend_assets' );
function doi_enqueue_frontend_assets() {
    if ( is_admin() ) return;

    $intro_on  = (bool) doi_get( 'site_intro_enabled' );
    $widget_on = (bool) doi_get( 'widget_enabled' );

    // Enqueue if either mode is active
    if ( ! $intro_on && ! $widget_on ) return;

    // Enqueue 3D Door & Section CSS
    wp_enqueue_style(
        'doi-door-open',
        DOI_PLUGIN_URL . 'assets/door-open.css',
        [],
        DOI_VERSION
    );

    // Enqueue 3D Door & Section JS
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
    $mode     = doi_get( 'display_mode' )    ?: 'hero_landing';

    wp_localize_script( 'doi-door-open', 'DOI_CONFIG', [
        'displayMode'      => $mode,
        'siteIntroEnabled' => $intro_on,
        'widgetEnabled'    => $widget_on,
        'soundEnabled'     => (bool) doi_get( 'sound_enabled' ),
        'sessionOnce'      => (bool) doi_get( 'session_once' ),
        'buttonText'       => esc_html( $btn_text ),
        'buttonSubtext'    => esc_html( doi_get( 'button_subtext' ) ?: 'STEP INTO YOUR INNER WORLD' ),
        'preloadUrl'       => esc_url( $url ),
        'doorImageUrl'     => esc_url( $door_img ),
        'roomImageUrl'     => esc_url( $room_img ),
        'pluginUrl'        => esc_url( DOI_PLUGIN_URL ),
    ] );
}

// ─── Frontend Rendering Hook ──────────────────────────────────────────────────
// Placed in wp_footer for overlay mode or fallback
add_action( 'wp_footer', 'doi_render_site_intro_overlay', 1 );
function doi_render_site_intro_overlay() {
    if ( is_admin() ) return;
    if ( ! doi_get( 'site_intro_enabled' ) ) return;

    $mode = doi_get( 'display_mode' ) ?: 'hero_landing';

    // If on homepage / /home and hero_landing mode is active, landing-page.php has already rendered everything cleanly!
    if ( $mode === 'hero_landing' && doi_is_target_page() ) {
        return;
    }

    // Otherwise render overlay mode (if selected)
    if ( $mode === 'overlay_intro' ) {
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
            'is_hero'  => false,
        ] );
    }
}

// ─── Threshold HTML Template (Hero / Overlay) ─────────────────────────────────
function doi_get_threshold_html( $args = [] ) {
    $door_img   = esc_url( $args['door_img'] ?? ( doi_get( 'door_image_url' ) ?: DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ) );
    $room_img   = esc_url( $args['room_img'] ?? ( doi_get( 'room_image_url' ) ?: DOI_PLUGIN_URL . 'assets/room-interior.jpg' ) );
    $btn_text   = esc_html( $args['btn_text'] ?? ( doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY' ) );
    $btn_sub    = esc_html( doi_get( 'button_subtext' ) ?: 'STEP INTO YOUR INNER WORLD' );
    $url        = esc_attr( $args['url'] ?? ( doi_get( 'preload_url' ) ?: '' ) );
    $skip_key   = esc_attr( $args['skip_key'] ?? '' );
    $is_hero    = ! empty( $args['is_hero'] );

    $extra_classes = $is_hero ? ' is-hero' : ' is-overlay';

    ob_start();
    ?>
    <section id="tar-threshold" class="tar-threshold<?php echo $extra_classes; ?>"
             data-door-left="35.0" data-door-right="65.0" data-door-top="16.4" data-door-bottom="86.9"
             data-lintel-y="9.0"
             data-is-hero="<?php echo $is_hero ? '1' : '0'; ?>"
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
              <span class="tar-btn-sub" style="display: block !important; margin-top: 13px !important; font-family: 'Cinzel', serif !important; font-size: 11px !important; letter-spacing: 0.32em !important; text-transform: uppercase !important; font-weight: 500 !important; color: rgba(225, 210, 195, 0.88) !important; text-shadow: 0 1px 8px rgba(0, 0, 0, 0.85) !important; text-align: center !important;"><?php echo $btn_sub; ?></span>
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

// ─── 3 Landing Page Sections (Founder, Vision, Cards & Footer) ────────────────
function doi_get_landing_sections_html( $custom_opts = [] ) {
    $saved = get_option( DOI_OPTIONS, [] );
    $opts  = wp_parse_args( $saved, doi_defaults() );
    if ( ! empty( $custom_opts ) ) {
        $opts = wp_parse_args( $custom_opts, $opts );
    }

    ob_start();
    ?>
    <div class="doi-landing-sections" id="doi-landing-sections">
      <?php
      if ( ! isset( $opts['founder_enabled'] ) || ! empty( $opts['founder_enabled'] ) ) {
          echo doi_get_founder_html( $opts );
      }
      if ( ! isset( $opts['vision_enabled'] ) || ! empty( $opts['vision_enabled'] ) ) {
          echo doi_get_vision_html( $opts );
      }
      if ( ! isset( $opts['cards_enabled'] ) || ! empty( $opts['cards_enabled'] ) ) {
          echo doi_get_cards_html( $opts );
      }
      if ( ! isset( $opts['footer_enabled'] ) || ! empty( $opts['footer_enabled'] ) ) {
          echo doi_get_footer_bar_html( $opts );
      }
      ?>
    </div>
    <?php
    return ob_get_clean();
}

// ─── Section 1: Our Founder ───────────────────────────────────────────────────
function doi_get_founder_html( $opts ) {
    $kicker   = esc_html( $opts['founder_kicker'] ?? 'OUR FOUNDER' );
    $title    = nl2br( esc_html( $opts['founder_title'] ?? "This is a journey\nI once had to take." ) );
    $text     = nl2br( esc_html( $opts['founder_text'] ?? "Through my dreams, I found the wisdom I didn't know I was looking for. DRUMI was created to help you do the same — gently, honestly, and in your own time." ) );
    $btn_text = esc_html( $opts['founder_btn_text'] ?? 'READ MY STORY' );
    $btn_url  = esc_url( $opts['founder_btn_url'] ?? '#founder-story' );
    $img_url  = esc_url( $opts['founder_image_url'] ?: DOI_PLUGIN_URL . 'assets/founder-photo.png' );

    ob_start();
    ?>
    <section id="doi-founder" class="doi-sec doi-founder-sec">
      <div class="doi-container">
        <div class="doi-founder-grid">
          <!-- Left Column: Editorial Copy -->
          <div class="doi-founder-copy">
            <span class="doi-kicker"><?php echo $kicker; ?></span>
            <h2 class="doi-display-heading"><?php echo $title; ?></h2>
            <p class="doi-body-text"><?php echo $text; ?></p>
            <a href="<?php echo $btn_url; ?>" class="doi-link-underlined" style="cursor:pointer;">
              <span><?php echo $btn_text; ?></span>
            </a>
          </div>

          <!-- Right Column: Founder Window Photo -->
          <div class="doi-founder-media">
            <div class="doi-founder-img-card" <?php if ( ! empty( $btn_url ) && $btn_url !== '#' ) { echo 'onclick="window.location.href=\'' . esc_url( $btn_url ) . '\';" style="cursor:pointer;"'; } ?>>
              <img src="<?php echo $img_url; ?>" alt="<?php echo esc_attr( $kicker ); ?>" loading="lazy">
              <div class="doi-img-inner-shadow" aria-hidden="true"></div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

// ─── Section 2: Our Vision ────────────────────────────────────────────────────
function doi_get_vision_html( $opts ) {
    $kicker = esc_html( $opts['vision_kicker'] ?? 'OUR VISION' );
    $title  = esc_html( $opts['vision_title'] ?? 'Remember. Understand. Integrate.' );
    $text   = nl2br( esc_html( $opts['vision_text'] ?? "We believe your dreams are more than stories. They are messages from within, guiding you back to what truly matters." ) );

    ob_start();
    ?>
    <section id="doi-vision" class="doi-sec doi-vision-sec">
      <div class="doi-vision-rings" aria-hidden="true"></div>
      <div class="doi-container">
        <div class="doi-vision-header">
          <span class="doi-kicker"><?php echo $kicker; ?></span>
          <h2 class="doi-display-heading doi-text-center"><?php echo $title; ?></h2>
          <div class="doi-star-sep" aria-hidden="true">✦</div>
          <p class="doi-body-text doi-vision-body"><?php echo $text; ?></p>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

// ─── Section 3: Feature Cards (Dream Journal & Blog) ──────────────────────────
function doi_get_cards_html( $opts ) {
    $card1_title    = esc_html( $opts['card1_title'] ?? 'THE DREAM JOURNAL' );
    $card1_text     = esc_html( $opts['card1_text'] ?? 'Your space to remember, reflect and receive.' );
    $card1_btn_text = esc_html( $opts['card1_btn_text'] ?? 'DISCOVER THE JOURNAL' );
    $card1_btn_url  = esc_url( $opts['card1_btn_url'] ?? '#dream-journal' );
    $card1_img      = esc_url( $opts['card1_image_url'] ?: DOI_PLUGIN_URL . 'assets/card-journal.png' );

    $card2_title    = esc_html( $opts['card2_title'] ?? 'THE BLOG' );
    $card2_text     = esc_html( $opts['card2_text'] ?? 'Insights, inspiration and guidance for your journey.' );
    $card2_btn_text = esc_html( $opts['card2_btn_text'] ?? 'EXPLORE THE BLOG' );
    $card2_btn_url  = esc_url( $opts['card2_btn_url'] ?? '#blog' );
    $card2_img      = esc_url( $opts['card2_image_url'] ?: DOI_PLUGIN_URL . 'assets/card-blog.png' );

    ob_start();
    ?>
    <section id="doi-cards" class="doi-sec doi-cards-sec">
      <div class="doi-container">
        <div class="doi-cards-grid">
          <!-- Card 1: THE DREAM JOURNAL -->
          <div class="doi-feature-card doi-card-journal" style="background-image: url('<?php echo $card1_img; ?>');" <?php if ( ! empty( $card1_btn_url ) && $card1_btn_url !== '#' ) { echo 'onclick="window.location.href=\'' . esc_url( $card1_btn_url ) . '\';" style="background-image: url(\'' . $card1_img . '\'); cursor:pointer;"'; } ?>>
            <div class="doi-card-overlay doi-card-overlay--rose" aria-hidden="true"></div>
            <div class="doi-card-content">
              <div class="doi-card-icon" aria-hidden="true">
                <svg viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
                  <circle cx="20" cy="32" r="6" />
                </svg>
              </div>
              <h3 class="doi-card-title"><?php echo $card1_title; ?></h3>
              <p class="doi-card-subtitle"><?php echo $card1_text; ?></p>
              <a href="<?php echo $card1_btn_url; ?>" class="doi-card-btn doi-card-btn--light" onclick="event.stopPropagation();">
                <?php echo $card1_btn_text; ?>
              </a>
            </div>
          </div>

          <!-- Card 2: THE BLOG -->
          <div class="doi-feature-card doi-card-blog" style="background-image: url('<?php echo $card2_img; ?>');" <?php if ( ! empty( $card2_btn_url ) && $card2_btn_url !== '#' ) { echo 'onclick="window.location.href=\'' . esc_url( $card2_btn_url ) . '\';" style="background-image: url(\'' . $card2_img . '\'); cursor:pointer;"'; } ?>>
            <div class="doi-card-overlay doi-card-overlay--sand" aria-hidden="true"></div>
            <div class="doi-card-content">
              <div class="doi-card-icon doi-card-icon--dark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z" />
                  <line x1="16" y1="8" x2="2" y2="22" />
                  <line x1="17.5" y1="15" x2="9" y2="15" />
                </svg>
              </div>
              <h3 class="doi-card-title doi-card-title--dark"><?php echo $card2_title; ?></h3>
              <p class="doi-card-subtitle doi-card-subtitle--dark"><?php echo $card2_text; ?></p>
              <a href="<?php echo $card2_btn_url; ?>" class="doi-card-btn doi-card-btn--dark" onclick="event.stopPropagation();">
                <?php echo $card2_btn_text; ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

// ─── Section 4: Sacred Footer Bar ─────────────────────────────────────────────
function doi_get_footer_bar_html( $opts ) {
    $m1      = esc_html( $opts['footer_mantra1'] ?? 'SLOW DOWN' );
    $m1_u    = esc_url( $opts['footer_mantra1_url'] ?? '#' );
    $m2      = esc_html( $opts['footer_mantra2'] ?? 'LISTEN WITHIN' );
    $m2_u    = esc_url( $opts['footer_mantra2_url'] ?? '#' );
    $brand   = esc_html( $opts['footer_brand']   ?? 'DRUMI' );
    $brand_u = esc_url( $opts['footer_brand_url'] ?? '/' );
    $m3      = esc_html( $opts['footer_mantra3'] ?? 'TRUST THE MESSAGE' );
    $m3_u    = esc_url( $opts['footer_mantra3_url'] ?? '#' );
    $m4      = esc_html( $opts['footer_mantra4'] ?? 'RETURN TO YOU' );
    $m4_u    = esc_url( $opts['footer_mantra4_url'] ?? '#' );

    ob_start();
    ?>
    <footer id="doi-footer-bar" class="doi-footer-bar">
      <div class="doi-container">
        <div class="doi-footer-items">
          <!-- Item 1: SLOW DOWN -->
          <a href="<?php echo $m1_u; ?>" class="doi-footer-item" style="text-decoration:none;">
            <svg class="doi-footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
              <path d="M12 2C8 6 4 11 4 16C4 19.314 7.582 22 12 22C16.418 22 20 19.314 20 16C20 11 16 6 12 2Z" />
              <path d="M12 8V18" />
              <path d="M12 12C14 10 16 11 17 13" />
              <path d="M12 14C10 12 8 13 7 15" />
            </svg>
            <span class="doi-footer-label"><?php echo $m1; ?></span>
          </a>

          <!-- Item 2: LISTEN WITHIN -->
          <a href="<?php echo $m2_u; ?>" class="doi-footer-item" style="text-decoration:none;">
            <svg class="doi-footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
              <circle cx="12" cy="12" r="9" />
              <circle cx="12" cy="12" r="5" stroke-dasharray="2 2" />
              <circle cx="12" cy="12" r="1.5" fill="currentColor" />
            </svg>
            <span class="doi-footer-label"><?php echo $m2; ?></span>
          </a>

          <!-- Center Item: DRUMI Emblem -->
          <a href="<?php echo $brand_u; ?>" class="doi-footer-item doi-footer-item--center" style="text-decoration:none;">
            <svg class="doi-footer-emblem" viewBox="0 0 40 48" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M20 2C20 2 6 22 6 32C6 39.732 12.268 46 20 46C27.732 46 34 39.732 34 32C34 22 20 2 20 2Z" />
              <circle cx="20" cy="32" r="6" />
            </svg>
            <span class="doi-footer-brand"><?php echo $brand; ?></span>
          </a>

          <!-- Item 3: TRUST THE MESSAGE -->
          <a href="<?php echo $m3_u; ?>" class="doi-footer-item" style="text-decoration:none;">
            <svg class="doi-footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
              <path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" />
            </svg>
            <span class="doi-footer-label"><?php echo $m3; ?></span>
          </a>

          <!-- Item 4: RETURN TO YOU -->
          <a href="<?php echo $m4_u; ?>" class="doi-footer-item" style="text-decoration:none;">
            <svg class="doi-footer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
              <circle cx="12" cy="12" r="9" />
              <circle cx="12" cy="12" r="3" />
            </svg>
            <span class="doi-footer-label"><?php echo $m4; ?></span>
          </a>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}

// ─── Shortcode: [door_open] & [drumi_landing] ─────────────────────────────────
add_action( 'init', 'doi_register_shortcodes' );
function doi_register_shortcodes() {
    add_shortcode( 'door_open',       'doi_shortcode_render' );
    add_shortcode( 'door_open_intro', 'doi_shortcode_render' ); // alias
    add_shortcode( 'drumi_landing',   'doi_shortcode_landing' ); // full landing page alias
}

function doi_shortcode_landing( $atts ) {
    $atts = shortcode_atts( [ 'mode' => 'hero' ], $atts );
    return doi_shortcode_render( $atts );
}

function doi_shortcode_render( $atts ) {
    $atts = shortcode_atts( [
        'mode'     => 'button', // 'button', 'hero', 'sections', 'intro'
        'text'     => doi_get( 'button_text' ) ?: 'JOIN THE JOURNEY',
        'url'      => doi_get( 'preload_url' ) ?: '',
        'door_img' => doi_get( 'door_image_url' ) ?: ( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ),
        'room_img' => doi_get( 'room_image_url' ) ?: ( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ),
    ], $atts, 'door_open' );

    // Ensure assets are loaded
    if ( ! wp_script_is( 'doi-door-open', 'enqueued' ) ) {
        doi_enqueue_frontend_assets();
    }

    $mode = strtolower( $atts['mode'] );

    // Mode: Hero Door + 3 Sections below
    if ( $mode === 'hero' || $mode === 'landing' ) {
        $html  = doi_get_threshold_html( [
            'door_img' => $atts['door_img'],
            'room_img' => $atts['room_img'],
            'btn_text' => $atts['text'],
            'url'      => $atts['url'],
            'skip_key' => '',
            'is_hero'  => true,
        ] );
        $html .= doi_get_landing_sections_html();
        return $html;
    }

    // Mode: Only the 3 Landing Sections
    if ( $mode === 'sections' ) {
        return doi_get_landing_sections_html();
    }

    // Mode: Full threshold intro embed (Overlay)
    if ( $mode === 'intro' ) {
        return doi_get_threshold_html( [
            'door_img' => $atts['door_img'],
            'room_img' => $atts['room_img'],
            'btn_text' => $atts['text'],
            'url'      => $atts['url'],
            'skip_key' => '',
            'is_hero'  => false,
        ] );
    }

    // Mode: Interactive trigger button
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
           <span class="doi-btn-sub" style="display: block !important; margin-top: 13px !important; font-family: \'Cinzel\', serif !important; font-size: 11px !important; letter-spacing: 0.32em !important; text-transform: uppercase !important; font-weight: 500 !important; color: rgba(225, 210, 195, 0.88) !important; text-shadow: 0 1px 8px rgba(0, 0, 0, 0.85) !important; text-align: center !important;">%s</span>
         </div>',
        esc_url( $atts['url'] ),
        esc_url( $atts['door_img'] ),
        esc_url( $atts['room_img'] ),
        esc_attr( $atts['text'] ),
        esc_attr( $atts['text'] ),
        esc_html( $atts['text'] ),
        esc_html( doi_get( 'button_subtext' ) ?: 'STEP INTO YOUR INNER WORLD' )
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
        // Mode & Mechanics
        'display_mode'       => in_array( $in['display_mode'] ?? '', [ 'hero_landing', 'overlay_intro' ], true ) ? $in['display_mode'] : 'hero_landing',
        'site_intro_enabled' => empty( $in['site_intro_enabled'] ) ? 0 : 1,
        'widget_enabled'     => empty( $in['widget_enabled'] )     ? 0 : 1,
        'sound_enabled'      => empty( $in['sound_enabled'] )      ? 0 : 1,
        'session_once'       => empty( $in['session_once'] )       ? 0 : 1,
        'button_text'        => sanitize_text_field( $in['button_text'] ?? 'JOIN THE JOURNEY' ),
        'button_subtext'     => sanitize_text_field( $in['button_subtext'] ?? 'STEP INTO YOUR INNER WORLD' ),
        'preload_url'        => esc_url_raw( $in['preload_url'] ?? '' ),
        'target_slugs'       => sanitize_text_field( $in['target_slugs'] ?? 'home' ),
        'door_image_url'     => esc_url_raw( $in['door_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ) ),
        'room_image_url'     => esc_url_raw( $in['room_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ) ),

        // Landing Sections Enabled
        'sections_enabled'   => isset( $in['sections_enabled'] ) ? ( empty( $in['sections_enabled'] ) ? 0 : 1 ) : 1,

        // Section 1: Our Founder
        'founder_enabled'    => empty( $in['founder_enabled'] )    ? 0 : 1,
        'founder_kicker'     => sanitize_text_field( $in['founder_kicker'] ?? 'OUR FOUNDER' ),
        'founder_title'      => sanitize_textarea_field( $in['founder_title'] ?? "This is a journey\nI once had to take." ),
        'founder_text'       => sanitize_textarea_field( $in['founder_text'] ?? "Through my dreams, I found the wisdom I didn't know I was looking for. DRUMI was created to help you do the same — gently, honestly, and in your own time." ),
        'founder_btn_text'   => sanitize_text_field( $in['founder_btn_text'] ?? 'READ MY STORY' ),
        'founder_btn_url'    => esc_url_raw( $in['founder_btn_url'] ?? '#founder-story' ),
        'founder_image_url'  => esc_url_raw( $in['founder_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/founder-photo.png' ) ),

        // Section 2: Our Vision
        'vision_enabled'     => empty( $in['vision_enabled'] )     ? 0 : 1,
        'vision_kicker'      => sanitize_text_field( $in['vision_kicker'] ?? 'OUR VISION' ),
        'vision_title'       => sanitize_text_field( $in['vision_title'] ?? 'Remember. Understand. Integrate.' ),
        'vision_text'        => sanitize_textarea_field( $in['vision_text'] ?? "We believe your dreams are more than stories. They are messages from within, guiding you back to what truly matters." ),

        // Section 3: Feature Cards & Footer
        'cards_enabled'      => empty( $in['cards_enabled'] )      ? 0 : 1,
        'card1_title'        => sanitize_text_field( $in['card1_title'] ?? 'THE DREAM JOURNAL' ),
        'card1_text'         => sanitize_text_field( $in['card1_text'] ?? 'Your space to remember, reflect and receive.' ),
        'card1_btn_text'     => sanitize_text_field( $in['card1_btn_text'] ?? 'DISCOVER THE JOURNAL' ),
        'card1_btn_url'      => esc_url_raw( $in['card1_btn_url'] ?? '#dream-journal' ),
        'card1_image_url'    => esc_url_raw( $in['card1_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/card-journal.png' ) ),

        'card2_title'        => sanitize_text_field( $in['card2_title'] ?? 'THE BLOG' ),
        'card2_text'         => sanitize_text_field( $in['card2_text'] ?? 'Insights, inspiration and guidance for your journey.' ),
        'card2_btn_text'     => sanitize_text_field( $in['card2_btn_text'] ?? 'EXPLORE THE BLOG' ),
        'card2_btn_url'      => esc_url_raw( $in['card2_btn_url'] ?? '#blog' ),
        'card2_image_url'    => esc_url_raw( $in['card2_image_url'] ?? ( DOI_PLUGIN_URL . 'assets/card-blog.png' ) ),

        'footer_enabled'     => empty( $in['footer_enabled'] )     ? 0 : 1,
        'footer_mantra1'     => sanitize_text_field( $in['footer_mantra1'] ?? 'SLOW DOWN' ),
        'footer_mantra1_url' => esc_url_raw( $in['footer_mantra1_url'] ?? '#' ),
        'footer_mantra2'     => sanitize_text_field( $in['footer_mantra2'] ?? 'LISTEN WITHIN' ),
        'footer_mantra2_url' => esc_url_raw( $in['footer_mantra2_url'] ?? '#' ),
        'footer_brand'       => sanitize_text_field( $in['footer_brand'] ?? 'DRUMI' ),
        'footer_brand_url'   => esc_url_raw( $in['footer_brand_url'] ?? '/' ),
        'footer_mantra3'     => sanitize_text_field( $in['footer_mantra3'] ?? 'TRUST THE MESSAGE' ),
        'footer_mantra3_url' => esc_url_raw( $in['footer_mantra3_url'] ?? '#' ),
        'footer_mantra4'     => sanitize_text_field( $in['footer_mantra4'] ?? 'RETURN TO YOU' ),
        'footer_mantra4_url' => esc_url_raw( $in['footer_mantra4_url'] ?? '#' ),
    ];
}

function doi_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = wp_parse_args( get_option( DOI_OPTIONS, [] ), doi_defaults() );
    ?>
    <div class="wrap" style="max-width:920px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
      <h1 style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
        <span style="font-size:1.4em;">🚪</span>
        <strong>Door Open Intro & DRUMI Sanctuary</strong>
        <span style="font-size:13px; font-weight:normal; background:#e8d5a3; color:#4a3722; padding:3px 10px; border-radius:12px;">v<?php echo DOI_VERSION; ?></span>
      </h1>
      <p style="color:#666; font-size:1.05em; margin-top:2px;">
        Cinematic 3D door-opening hero animation with procedural Web Audio & customizable DRUMI Sanctuary landing sections.
      </p>

      <?php settings_errors(); ?>

      <form method="post" action="options.php" style="margin-top:20px;">
        <?php settings_fields( 'doi_settings_group' ); ?>

        <style>
          .doi-card { background:#fff; border:1px solid #ccd0d4; border-radius:10px; padding:24px 28px; box-shadow:0 3px 15px rgba(0,0,0,0.04); margin-bottom:24px; }
          .doi-card h2 { margin-top:0; font-size:1.25em; border-bottom:1px solid #f0f0f1; padding-bottom:12px; display:flex; align-items:center; gap:8px; color:#1d2327; }
          .doi-toggle-row { display:flex; justify-content:space-between; align-items:flex-start; padding:16px 0; border-bottom:1px solid #f0f0f1; }
          .doi-toggle-row:last-child { border-bottom:none; }
          .doi-toggle-info { flex:1; padding-right:24px; }
          .doi-toggle-info strong { font-size:1.02em; color:#1d2327; display:block; margin-bottom:4px; }
          .doi-toggle-info p { margin:0; color:#646970; font-size:0.9em; line-height:1.5; }
          .doi-switch { position:relative; display:inline-block; width:50px; height:26px; flex-shrink:0; margin-top:4px; }
          .doi-switch input { opacity:0; width:0; height:0; }
          .doi-slider { position:absolute; cursor:pointer; inset:0; background-color:#ccc; border-radius:26px; transition:0.3s cubic-bezier(0.4,0,0.2,1); }
          .doi-slider:before { position:absolute; content:""; height:18px; width:18px; left:4px; bottom:4px; background-color:white; border-radius:50%; transition:0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:0 2px 4px rgba(0,0,0,0.2); }
          input:checked + .doi-slider { background-color:#9a7470; }
          input:checked + .doi-slider:before { transform:translateX(24px); }
          .doi-field-row { padding:14px 0; border-bottom:1px solid #f0f0f1; }
          .doi-field-row:last-child { border-bottom:none; }
          .doi-field-row label { display:block; font-weight:600; margin-bottom:6px; color:#1d2327; font-size:13px; }
          .doi-field-row input[type="text"], .doi-field-row input[type="url"], .doi-field-row select, .doi-field-row textarea { width:100%; max-width:620px; padding:8px 12px; font-size:14px; border:1px solid #8c8f94; border-radius:6px; }
          .doi-field-row textarea { min-height:75px; resize:vertical; line-height:1.45; }
          .doi-badge { display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:2px 8px; border-radius:4px; }
          .doi-badge--rose { background:#f5ebe9; color:#9a7470; }
          .doi-badge--gold { background:#fbf3e6; color:#9d7a3f; }
          .doi-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; max-width:620px; }
          @media(max-width:640px) { .doi-grid-2 { grid-template-columns:1fr; } }
        </style>

        <!-- CARD 1: Display Mode & Mechanics -->
        <div class="doi-card">
          <h2><span>🌟</span> Display Mode & Door Mechanics</h2>

          <div class="doi-field-row">
            <label for="doi_display_mode">Display Layout Mode</label>
            <select id="doi_display_mode" name="<?php echo DOI_OPTIONS; ?>[display_mode]">
              <option value="hero_landing" <?php selected( $opts['display_mode'], 'hero_landing' ); ?>>
                Hero & Landing Page Mode (Door as Hero + 3 Sections underneath) [Recommended]
              </option>
              <option value="overlay_intro" <?php selected( $opts['display_mode'], 'overlay_intro' ); ?>>
                Full-screen Overlay Intro Mode (Dissolves to reveal existing page content)
              </option>
            </select>
            <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">
              In <strong>Hero & Landing Page Mode</strong>, the cinematic door is the top hero section of the site, and visitors can scroll down to view Our Founder, Our Vision, and the Feature Cards.
            </p>
          </div>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Enable Site Intro / Hero on Homepage</strong>
              <p>Automatically renders the door entrance at the top of your site (replaces theme header/footer on homepage).</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[site_intro_enabled]" value="1" <?php checked( $opts['site_intro_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div class="doi-field-row">
            <label for="doi_target_slugs">Homepage & Target Slugs</label>
            <input type="text" id="doi_target_slugs" name="<?php echo DOI_OPTIONS; ?>[target_slugs]"
                   value="<?php echo esc_attr( $opts['target_slugs'] ); ?>" placeholder="home">
            <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">
              Site root (<code>/</code>) and <code>/home</code> are automatically active. Comma-separated extra slugs can be added (e.g. <code>home, front-page</code>).
            </p>
          </div>

          <div class="doi-field-row" style="background:#fffdfa; border:1px solid #dfd0c4; border-radius:8px; padding:16px; margin:14px 0;">
            <label for="doi_preload_url" style="color:#7d5653; font-size:14px; font-weight:700;">
              🎯 Door Open Target / Redirect URL (Optional)
            </label>
            <input type="text" id="doi_preload_url" name="<?php echo DOI_OPTIONS; ?>[preload_url]"
                   value="<?php echo esc_attr( $opts['preload_url'] ); ?>" placeholder="https://clarkeecha.pixelora.studio/store or /shop">
            <p class="description" style="color:#646970; font-size:12px; margin-top:6px; line-height:1.5;">
              When a visitor clicks <strong>"JOIN THE JOURNEY"</strong>, the 3D doors will swing open with procedural sound, the camera will zoom through the doorway, and then automatically navigate to this page URL.
              <br><em>Leave completely <strong>blank</strong> to smoothly scroll down to the "Our Founder" section below!</em>
            </p>
          </div>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Procedural Ambient Sound (Web Audio)</strong>
              <p>Atmospheric harmonic swell when door opens — synthesized 100% in-browser with zero audio file downloads.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[sound_enabled]" value="1" <?php checked( $opts['sound_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Show Once Per Session (Repeat Visitor Skip)</strong>
              <p>When enabled, visitors who return during the same browser session will skip the intro door animation.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[session_once]" value="1" <?php checked( $opts['session_once'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:8px 0;">
              <label for="doi_button_text">Hero Button Label</label>
              <input type="text" id="doi_button_text" name="<?php echo DOI_OPTIONS; ?>[button_text]"
                     value="<?php echo esc_attr( $opts['button_text'] ); ?>" placeholder="JOIN THE JOURNEY">
            </div>
            <div class="doi-field-row" style="border:none; padding:8px 0;">
              <label for="doi_button_subtext">Hero Button Subtitle</label>
              <input type="text" id="doi_button_subtext" name="<?php echo DOI_OPTIONS; ?>[button_subtext]"
                     value="<?php echo esc_attr( $opts['button_subtext'] ); ?>" placeholder="STEP INTO YOUR INNER WORLD">
            </div>
          </div>

          <div class="doi-field-row">
            <label for="doi_door_image_url">Door Artwork Image URL</label>
            <input type="text" id="doi_door_image_url" name="<?php echo DOI_OPTIONS; ?>[door_image_url]"
                   value="<?php echo esc_attr( $opts['door_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/threshold-doors.jpg' ); ?>">
          </div>

          <div class="doi-field-row">
            <label for="doi_room_image_url">Interior Room / Lake Artwork URL</label>
            <input type="text" id="doi_room_image_url" name="<?php echo DOI_OPTIONS; ?>[room_image_url]"
                   value="<?php echo esc_attr( $opts['room_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/room-interior.jpg' ); ?>">
          </div>

          <div class="doi-toggle-row" style="background:#fcfaf7; border-top:1px solid #eee; padding-top:14px; margin-top:12px;">
            <div class="doi-toggle-info">
              <strong>Enable All Landing Page Sections (Founder, Vision, Cards, Footer)</strong>
              <p>Renders the editorial DRUMI content sections directly underneath the door open hero on your homepage.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[sections_enabled]" value="1" <?php checked( $opts['sections_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>
        </div>

        <!-- CARD 2: Section 1 — Our Founder -->
        <div class="doi-card">
          <h2>
            <span class="doi-badge doi-badge--rose">Section 1</span>
            Our Founder
          </h2>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Enable "Our Founder" Section</strong>
              <p>Displays editorial copy on the left and the founder meditating at window photo on the right.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[founder_enabled]" value="1" <?php checked( $opts['founder_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div class="doi-field-row">
            <label for="doi_founder_kicker">Kicker / Smallcaps Subtitle</label>
            <input type="text" id="doi_founder_kicker" name="<?php echo DOI_OPTIONS; ?>[founder_kicker]"
                   value="<?php echo esc_attr( $opts['founder_kicker'] ); ?>" placeholder="OUR FOUNDER">
          </div>

          <div class="doi-field-row">
            <label for="doi_founder_title">Main Heading</label>
            <textarea id="doi_founder_title" name="<?php echo DOI_OPTIONS; ?>[founder_title]"><?php echo esc_textarea( $opts['founder_title'] ); ?></textarea>
            <p class="description" style="color:#646970; font-size:12px; margin-top:4px;">Line breaks are preserved.</p>
          </div>

          <div class="doi-field-row">
            <label for="doi_founder_text">Bio / Editorial Text</label>
            <textarea id="doi_founder_text" name="<?php echo DOI_OPTIONS; ?>[founder_text]" style="min-height:90px;"><?php echo esc_textarea( $opts['founder_text'] ); ?></textarea>
          </div>

          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:8px 0;">
              <label for="doi_founder_btn_text">Button / Link Text</label>
              <input type="text" id="doi_founder_btn_text" name="<?php echo DOI_OPTIONS; ?>[founder_btn_text]"
                     value="<?php echo esc_attr( $opts['founder_btn_text'] ); ?>" placeholder="READ MY STORY">
            </div>
            <div class="doi-field-row" style="border:none; padding:8px 0;">
              <label for="doi_founder_btn_url">Button Link Destination (URL)</label>
              <input type="text" id="doi_founder_btn_url" name="<?php echo DOI_OPTIONS; ?>[founder_btn_url]"
                     value="<?php echo esc_attr( $opts['founder_btn_url'] ); ?>" placeholder="/story or #founder-story">
            </div>
          </div>

          <div class="doi-field-row">
            <label for="doi_founder_image_url">Founder Photo URL</label>
            <input type="text" id="doi_founder_image_url" name="<?php echo DOI_OPTIONS; ?>[founder_image_url]"
                   value="<?php echo esc_attr( $opts['founder_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/founder-photo.png' ); ?>">
          </div>
        </div>

        <!-- CARD 3: Section 2 — Our Vision -->
        <div class="doi-card">
          <h2>
            <span class="doi-badge doi-badge--gold">Section 2</span>
            Our Vision
          </h2>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Enable "Our Vision" Section</strong>
              <p>Centered philosophical section with subtle concentric zen rings and diamond separator.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[vision_enabled]" value="1" <?php checked( $opts['vision_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div class="doi-field-row">
            <label for="doi_vision_kicker">Kicker / Smallcaps Subtitle</label>
            <input type="text" id="doi_vision_kicker" name="<?php echo DOI_OPTIONS; ?>[vision_kicker]"
                   value="<?php echo esc_attr( $opts['vision_kicker'] ); ?>" placeholder="OUR VISION">
          </div>

          <div class="doi-field-row">
            <label for="doi_vision_title">Vision Heading</label>
            <input type="text" id="doi_vision_title" name="<?php echo DOI_OPTIONS; ?>[vision_title]"
                   value="<?php echo esc_attr( $opts['vision_title'] ); ?>" placeholder="Remember. Understand. Integrate.">
          </div>

          <div class="doi-field-row">
            <label for="doi_vision_text">Vision Statement Paragraph</label>
            <textarea id="doi_vision_text" name="<?php echo DOI_OPTIONS; ?>[vision_text]"><?php echo esc_textarea( $opts['vision_text'] ); ?></textarea>
          </div>
        </div>

        <!-- CARD 4: Section 3 — Feature Cards & Sacred Footer -->
        <div class="doi-card">
          <h2>
            <span class="doi-badge doi-badge--rose">Section 3</span>
            The Feature Cards & Footer Bar
          </h2>

          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Enable The 2 Feature Cards</strong>
              <p>Displays side-by-side cards for The Dream Journal & The Blog with custom botanical backgrounds.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[cards_enabled]" value="1" <?php checked( $opts['cards_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <h3 style="margin:18px 0 8px; color:#9a7470; font-size:1.05em;">Card 1: The Dream Journal</h3>
          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card1_title">Card 1 Title</label>
              <input type="text" id="doi_card1_title" name="<?php echo DOI_OPTIONS; ?>[card1_title]"
                     value="<?php echo esc_attr( $opts['card1_title'] ); ?>" placeholder="THE DREAM JOURNAL">
            </div>
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card1_text">Card 1 Subtitle / Description</label>
              <input type="text" id="doi_card1_text" name="<?php echo DOI_OPTIONS; ?>[card1_text]"
                     value="<?php echo esc_attr( $opts['card1_text'] ); ?>" placeholder="Your space to remember, reflect and receive.">
            </div>
          </div>
          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card1_btn_text">Card 1 Button Label</label>
              <input type="text" id="doi_card1_btn_text" name="<?php echo DOI_OPTIONS; ?>[card1_btn_text]"
                     value="<?php echo esc_attr( $opts['card1_btn_text'] ); ?>" placeholder="DISCOVER THE JOURNAL">
            </div>
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card1_btn_url">Card 1 Link URL</label>
              <input type="text" id="doi_card1_btn_url" name="<?php echo DOI_OPTIONS; ?>[card1_btn_url]"
                     value="<?php echo esc_attr( $opts['card1_btn_url'] ); ?>" placeholder="/journal or #dream-journal">
            </div>
          </div>
          <div class="doi-field-row">
            <label for="doi_card1_image_url">Card 1 Background Image URL</label>
            <input type="text" id="doi_card1_image_url" name="<?php echo DOI_OPTIONS; ?>[card1_image_url]"
                   value="<?php echo esc_attr( $opts['card1_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/card-journal.png' ); ?>">
          </div>

          <hr style="border:none; border-top:1px solid #f0f0f1; margin:20px 0;">

          <h3 style="margin:18px 0 8px; color:#5a483a; font-size:1.05em;">Card 2: The Blog</h3>
          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card2_title">Card 2 Title</label>
              <input type="text" id="doi_card2_title" name="<?php echo DOI_OPTIONS; ?>[card2_title]"
                     value="<?php echo esc_attr( $opts['card2_title'] ); ?>" placeholder="THE BLOG">
            </div>
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card2_text">Card 2 Subtitle / Description</label>
              <input type="text" id="doi_card2_text" name="<?php echo DOI_OPTIONS; ?>[card2_text]"
                     value="<?php echo esc_attr( $opts['card2_text'] ); ?>" placeholder="Insights, inspiration and guidance for your journey.">
            </div>
          </div>
          <div class="doi-grid-2">
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card2_btn_text">Card 2 Button Label</label>
              <input type="text" id="doi_card2_btn_text" name="<?php echo DOI_OPTIONS; ?>[card2_btn_text]"
                     value="<?php echo esc_attr( $opts['card2_btn_text'] ); ?>" placeholder="EXPLORE THE BLOG">
            </div>
            <div class="doi-field-row" style="border:none; padding:6px 0;">
              <label for="doi_card2_btn_url">Card 2 Link URL</label>
              <input type="text" id="doi_card2_btn_url" name="<?php echo DOI_OPTIONS; ?>[card2_btn_url]"
                     value="<?php echo esc_attr( $opts['card2_btn_url'] ); ?>" placeholder="/blog or #blog">
            </div>
          </div>
          <div class="doi-field-row">
            <label for="doi_card2_image_url">Card 2 Background Image URL</label>
            <input type="text" id="doi_card2_image_url" name="<?php echo DOI_OPTIONS; ?>[card2_image_url]"
                   value="<?php echo esc_attr( $opts['card2_image_url'] ); ?>" placeholder="<?php echo esc_attr( DOI_PLUGIN_URL . 'assets/card-blog.png' ); ?>">
          </div>

          <hr style="border:none; border-top:1px solid #f0f0f1; margin:20px 0;">

          <h3 style="margin:18px 0 8px; color:#966f6c; font-size:1.05em;">Sacred Footer Bar</h3>
          <div class="doi-toggle-row">
            <div class="doi-toggle-info">
              <strong>Enable Sacred Footer Bar</strong>
              <p>Muted rose bar at the bottom with mantras: SLOW DOWN, LISTEN WITHIN, DRUMI, TRUST THE MESSAGE, RETURN TO YOU.</p>
            </div>
            <label class="doi-switch">
              <input type="checkbox" name="<?php echo DOI_OPTIONS; ?>[footer_enabled]" value="1" <?php checked( $opts['footer_enabled'], 1 ); ?>>
              <span class="doi-slider"></span>
            </label>
          </div>

          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
            <div class="doi-field-row" style="border:none; padding:4px 0;">
              <label for="doi_footer_mantra1">Mantra 1 Label & Link</label>
              <input type="text" id="doi_footer_mantra1" name="<?php echo DOI_OPTIONS; ?>[footer_mantra1]"
                     value="<?php echo esc_attr( $opts['footer_mantra1'] ); ?>" placeholder="SLOW DOWN" style="margin-bottom:4px;">
              <input type="text" name="<?php echo DOI_OPTIONS; ?>[footer_mantra1_url]"
                     value="<?php echo esc_attr( $opts['footer_mantra1_url'] ?? '#' ); ?>" placeholder="Link (e.g. # or /slow-down)">
            </div>
            <div class="doi-field-row" style="border:none; padding:4px 0;">
              <label for="doi_footer_mantra2">Mantra 2 Label & Link</label>
              <input type="text" id="doi_footer_mantra2" name="<?php echo DOI_OPTIONS; ?>[footer_mantra2]"
                     value="<?php echo esc_attr( $opts['footer_mantra2'] ); ?>" placeholder="LISTEN WITHIN" style="margin-bottom:4px;">
              <input type="text" name="<?php echo DOI_OPTIONS; ?>[footer_mantra2_url]"
                     value="<?php echo esc_attr( $opts['footer_mantra2_url'] ?? '#' ); ?>" placeholder="Link (e.g. # or /meditation)">
            </div>
            <div class="doi-field-row" style="border:none; padding:4px 0;">
              <label for="doi_footer_brand">Center Brand Label & Link</label>
              <input type="text" id="doi_footer_brand" name="<?php echo DOI_OPTIONS; ?>[footer_brand]"
                     value="<?php echo esc_attr( $opts['footer_brand'] ); ?>" placeholder="DRUMI" style="margin-bottom:4px;">
              <input type="text" name="<?php echo DOI_OPTIONS; ?>[footer_brand_url]"
                     value="<?php echo esc_attr( $opts['footer_brand_url'] ?? '/' ); ?>" placeholder="Link (default: /)">
            </div>
            <div class="doi-field-row" style="border:none; padding:4px 0;">
              <label for="doi_footer_mantra3">Mantra 3 Label & Link</label>
              <input type="text" id="doi_footer_mantra3" name="<?php echo DOI_OPTIONS; ?>[footer_mantra3]"
                     value="<?php echo esc_attr( $opts['footer_mantra3'] ); ?>" placeholder="TRUST THE MESSAGE" style="margin-bottom:4px;">
              <input type="text" name="<?php echo DOI_OPTIONS; ?>[footer_mantra3_url]"
                     value="<?php echo esc_attr( $opts['footer_mantra3_url'] ?? '#' ); ?>" placeholder="Link (e.g. # or /trust)">
            </div>
            <div class="doi-field-row" style="border:none; padding:4px 0;">
              <label for="doi_footer_mantra4">Mantra 4 Label & Link</label>
              <input type="text" id="doi_footer_mantra4" name="<?php echo DOI_OPTIONS; ?>[footer_mantra4]"
                     value="<?php echo esc_attr( $opts['footer_mantra4'] ); ?>" placeholder="RETURN TO YOU" style="margin-bottom:4px;">
              <input type="text" name="<?php echo DOI_OPTIONS; ?>[footer_mantra4_url]"
                     value="<?php echo esc_attr( $opts['footer_mantra4_url'] ?? '#' ); ?>" placeholder="Link (e.g. # or /return)">
            </div>
          </div>
        </div>

        <p class="submit" style="padding-top:10px;">
          <input type="submit" name="submit" id="submit" class="button button-primary"
                 style="background:#9a7470; border-color:#7a5855; font-weight:600; padding:6px 28px; height:auto; font-size:15px;"
                 value="Save All Changes">
        </p>
      </form>

      <!-- Shortcode Guide Card -->
      <div class="doi-card" style="margin-top:10px;">
        <h2><span>🧩</span> Shortcodes for Elementor & Gutenberg</h2>
        <p style="color:#50575e; margin-bottom:14px;">Drop an <strong>HTML Widget</strong> or <strong>Shortcode Block</strong> anywhere and use:</p>

        <table class="widefat striped" style="border-radius:6px; overflow:hidden;">
          <thead>
            <tr>
              <th style="width:38%;">Shortcode</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>[door_open mode="hero"]</code> or <code>[drumi_landing]</code></td>
              <td>Renders the full Hero Door + the 3 customizable DRUMI landing sections underneath!</td>
            </tr>
            <tr>
              <td><code>[door_open mode="sections"]</code></td>
              <td>Renders only the 3 landing sections (Founder, Vision, Cards & Footer).</td>
            </tr>
            <tr>
              <td><code>[door_open]</code></td>
              <td>Creates a luxury button trigger that swings the doors open.</td>
            </tr>
            <tr>
              <td><code>[door_open text="ENTER" url="/about"]</code></td>
              <td>Opens doors and navigates to <code>/about</code>.</td>
            </tr>
            <tr>
              <td><code>[door_open mode="intro"]</code></td>
              <td>Embeds the full-screen 3D door threshold overlay.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}
