<?php
/**
 * Plugin Name:       Door Open Intro
 * Plugin URI:        https://github.com/EmonAhammed1/Door-Open
 * Description:       A full-screen DRUMI threshold: the door's engraving ignites, the doors swing open and the visitor walks through into your page, already loaded behind it.
 * Version:           4.7.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Door Open
 * License:           GPL-2.0-or-later
 * Text Domain:       door-open-intro
 */

defined( 'ABSPATH' ) || exit;

define( 'DOI_VERSION', '4.7.4' );
define( 'DOI_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOI_URL', plugin_dir_url( __FILE__ ) );
define( 'DOI_OPTION', 'doi_intro' );

require_once DOI_DIR . 'includes/admin.php';
require_once DOI_DIR . 'includes/portals.php';
require_once DOI_DIR . 'includes/home.php';

function doi_defaults() {
	return array(
		'enabled'  => 1,
		'where'    => 'home', // 'home' | 'all'
		'once'     => 0, // once per tab (sessionStorage); arriving from outside and reloading still show it
		'once_all' => 0, // once per browser session, across all tabs (session cookie)
		'mobile'   => 1,
		'skip'     => 1,
		'wordmark'  => 'DRUMI',
		'tagline'   => '',
		'button'    => 'OPEN THE DOOR',
		'caption'   => 'Ready to come back home?',
		'button_bg' => '#4b2a18',
		'hero'       => 1,
		'hero_title' => 'Welcome Home',
		'hero_sub'   => 'A place where you belong',
		'hero_desktop' => '', // frames prepared from a chosen video (see includes/admin.php); '' is the original corridor
		'hero_mobile'  => '',
	);
}

function doi_settings() {
	$saved = get_option( DOI_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), doi_defaults() );
}

function doi_image_url( $name ) {
	// a lighter WebP copy beside the photograph is sent instead when there is one
	$webp = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $name );
	if ( $webp !== $name && file_exists( DOI_DIR . 'assets/door/' . $webp ) ) {
		$name = $webp;
	}
	// versioned so that replacing a photo reaches visitors who already have the old one cached
	return DOI_URL . 'assets/door/' . $name . '?ver=' . DOI_VERSION;
}

/**
 * A page cache (LiteSpeed Cache) keeps whole pages, and with them the door's text and the portals; when these
 * settings are saved, the cached pages are dropped so visitors see the change straight away.
 */
add_action( 'update_option_' . DOI_OPTION, 'doi_purge_page_cache' );
add_action( 'update_option_doi_portals', 'doi_purge_page_cache' );
function doi_purge_page_cache() {
	do_action( 'litespeed_purge_all' );
}

/**
 * Page builders render the page inside an iframe while you edit it; the door must never cover that.
 */
function doi_in_builder_preview() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only checks of builder query flags.
	foreach ( array( 'elementor-preview', 'et_fb', 'fl_builder', 'ct_builder', 'vc_editable', 'brizy-edit-iframe', 'bricks' ) as $flag ) {
		if ( isset( $_GET[ $flag ] ) ) {
			return true;
		}
	}
	// phpcs:enable
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		return true;
	}
	return false;
}

/**
 * Whether this front-end request gets the door. Decided once, after the main query has run.
 * `?doi_preview=1` shows it even when disabled or already seen, for checking your setup.
 */
function doi_should_play() {
	static $decision = null;
	if ( null !== $decision ) {
		return $decision;
	}
	$decision = false;

	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || is_embed() || is_trackback() || is_customize_preview() || is_preview() || is_404() ) {
		return false;
	}
	if ( doi_in_builder_preview() ) {
		return false;
	}

	$s      = doi_settings();
	$forced = doi_is_forced();
	if ( ! $s['enabled'] && ! $forced ) {
		return false;
	}
	if ( 'home' === $s['where'] && ! is_front_page() && ! $forced ) {
		return false;
	}

	$decision = true;
	return true;
}

function doi_is_forced() {
	return isset( $_GET['doi_preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Everything the browser-side script needs. Text is inserted with textContent, never as HTML.
 */
function doi_client_config() {
	$s     = doi_settings();
	$lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $s['tagline'] ) ), 'strlen' ) );
	return array(
		'idle'      => doi_image_url( 'desktop-idle.jpg' ),
		'lit'       => doi_image_url( 'desktop-lit.jpg' ),
		'phoneIdle' => doi_image_url( 'phone-idle.jpg' ),
		'phoneLit'  => doi_image_url( 'phone-lit.jpg' ),
		'wordmark'  => (string) $s['wordmark'],
		'tagline'   => $lines,
		'button'    => (string) $s['button'],
		'buttonBg'  => (string) $s['button_bg'],
		'caption'   => (string) $s['caption'],
		'skip'      => (bool) $s['skip'],
		'skipLabel' => __( 'Skip intro', 'door-open-intro' ),
		'once'        => (bool) $s['once'],
		'onceBrowser' => (bool) $s['once_all'],
		'cookiePath'  => defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
	);
}

add_action( 'wp_enqueue_scripts', 'doi_enqueue' );
function doi_enqueue() {
	if ( ! doi_should_play() ) {
		return;
	}
	// `display=block` keeps the engraved wordmark from flashing in a fallback face.
	wp_enqueue_style( 'doi-fonts', 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500&family=Cormorant+Garamond:ital,wght@1,400&display=block', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceVersion.MissingVersion
	wp_enqueue_style( 'doi-intro', DOI_URL . 'assets/intro.css', array(), DOI_VERSION );
	wp_enqueue_script( 'doi-intro', DOI_URL . 'assets/intro.js', array(), DOI_VERSION, true );
	wp_add_inline_script( 'doi-intro', 'window.DOI_CONFIG = ' . wp_json_encode( doi_client_config() ) . ';', 'before' );
}

/**
 * The "13 Journeys" homepage door-slider: click a door, watch it split open, then land on its page.
 * Runs on every front-end page (the slider only exists where an editor places it), independent of the
 * main threshold intro above.
 */
add_action( 'wp_enqueue_scripts', 'doi_enqueue_journeys' );
function doi_enqueue_journeys() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || doi_in_builder_preview() ) {
		return;
	}
	wp_enqueue_style( 'doi-journeys', DOI_URL . 'assets/journeys.css', array(), DOI_VERSION );
	wp_enqueue_script( 'doi-journeys', DOI_URL . 'assets/journeys.js', array(), DOI_VERSION, true );
	// the homepage portals (see includes/portals.php); loaded here too so their styles are in the head
	doi_portals_enqueue();
}

/**
 * The homepage hero the door opens onto: scrolling walks down a corridor and around the corner to the sea, then
 * "Welcome home" follows as ordinary page text. It's printed above the homepage's own content rather than kept in
 * the page builder, so saving the page there can't remove it. hero.js draws its frames on a canvas; without the
 * script the corridor is a still of its first frame.
 */
/**
 * Frames prepared from a video chosen in the settings live in uploads/doi-hero/<folder>/001.webp …
 */
function doi_hero_upload_root() {
	$u = wp_upload_dir( null, false );
	return array(
		'dir' => $u['basedir'] . '/doi-hero',
		'url' => set_url_scheme( $u['baseurl'] . '/doi-hero' ),
	);
}

/**
 * The two frame sets the corridor is drawn from: wide screens and portrait screens. Each is the original
 * corridor bundled with the plugin unless a video has been chosen for it in the settings.
 */
function doi_hero_sets() {
	$s    = doi_settings();
	$root = doi_hero_upload_root();
	$sets = array(
		'desktop' => array( 'base' => DOI_URL . 'assets/hero/desktop/', 'count' => 120, 'w' => 1600, 'h' => 900, 'ext' => 'webp' ),
		'mobile'  => array( 'base' => DOI_URL . 'assets/hero/mobile/', 'count' => 120, 'w' => 720, 'h' => 1280, 'ext' => 'webp' ),
	);
	foreach ( array_keys( $sets ) as $set ) {
		$v = $s[ 'hero_' . $set ];
		if ( is_array( $v ) && ! empty( $v['folder'] ) ) {
			$sets[ $set ] = array(
				'base'  => $root['url'] . '/' . $v['folder'] . '/',
				'count' => (int) $v['count'],
				'w'     => (int) $v['w'],
				'h'     => (int) $v['h'],
				'ext'   => $v['ext'],
			);
		}
	}
	return $sets;
}

function doi_hero_poster( $set ) {
	return $set['base'] . '001.' . $set['ext'];
}

function doi_hero_enabled() {
	$s = doi_settings();
	return ! empty( $s['hero'] ) && is_front_page() && ! is_admin() && ! doi_in_builder_preview();
}

add_filter( 'the_content', 'doi_hero_prepend', 20 );
function doi_hero_prepend( $content ) {
	static $printed = false;
	if ( $printed || ! in_the_loop() || ! is_main_query() || ! doi_hero_enabled() ) {
		return $content;
	}
	if ( (int) get_the_ID() !== (int) get_option( 'page_on_front' ) ) {
		return $content;
	}
	$printed = true;
	$s     = doi_settings();
	$title = trim( (string) $s['hero_title'] );
	$sub   = trim( (string) $s['hero_sub'] );

	$sets   = doi_hero_sets();
	$poster = '--doi-hero-poster-d:url("' . esc_url( doi_hero_poster( $sets['desktop'] ) ) . '");--doi-hero-poster-m:url("' . esc_url( doi_hero_poster( $sets['mobile'] ) ) . '")';
	$html   = '<section class="doi-hero" style="' . esc_attr( $poster ) . '"' . ( '' !== $title ? ' aria-label="' . esc_attr( $title ) . '"' : '' ) . '>';
	$html .= '<div class="doi-hero-track"><div class="doi-hero-stage"></div></div>';
	if ( '' !== $title || '' !== $sub ) {
		$html .= '<div class="doi-hero-welcome">';
		if ( '' !== $title ) {
			$html .= '<h1 class="doi-hero-title">' . esc_html( $title ) . '</h1><span class="doi-hero-rule" aria-hidden="true"></span>';
		}
		if ( '' !== $sub ) {
			$html .= '<p class="doi-hero-sub">' . esc_html( $sub ) . '</p>';
		}
		$html .= '</div>';
	}
	$html .= '</section>';
	return $html . $content;
}

add_action( 'wp_enqueue_scripts', 'doi_enqueue_hero' );
function doi_enqueue_hero() {
	if ( ! doi_hero_enabled() ) {
		return;
	}
	// same handle and URL as the door's, so the fonts load once when both are on the page
	wp_enqueue_style( 'doi-fonts', 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500&family=Cormorant+Garamond:ital,wght@1,400&display=block', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceVersion.MissingVersion
	wp_enqueue_style( 'doi-hero', DOI_URL . 'assets/hero.css', array(), DOI_VERSION );
	wp_enqueue_script( 'doi-hero', DOI_URL . 'assets/hero.js', array(), DOI_VERSION, true );
	wp_add_inline_script(
		'doi-hero',
		'window.DOI_HERO = ' . wp_json_encode(
			array(
				'sets'  => doi_hero_sets(),
				'cue'   => __( 'Scroll', 'door-open-intro' ),
			)
		) . ';',
		'before'
	);
}

/**
 * A same-site "walk through the light" transition so clicking a journey door doesn't jump-cut to the
 * next page: the screen fades to the same warm glow as the open door, then the arriving page starts
 * already covered and fades back in. Runs on every front-end page (the arrival side needs it too),
 * independent of the main threshold intro above.
 */
add_action( 'wp_head', 'doi_print_journey_transition_head', 2 );
function doi_print_journey_transition_head() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || doi_in_builder_preview() ) {
		return;
	}
	?>
<style id="doi-jt-style">
.doi-jt-overlay{position:fixed;inset:0;z-index:2147483000;background:radial-gradient(circle at var(--jt-x,50%) var(--jt-y,50%),#fff6df 0%,#f0c988 35%,#c9973f 65%,#5f4a2c 100%);clip-path:circle(0% at var(--jt-x,50%) var(--jt-y,50%));-webkit-clip-path:circle(0% at var(--jt-x,50%) var(--jt-y,50%));pointer-events:none;transition:clip-path .62s cubic-bezier(.65,0,.35,1),-webkit-clip-path .62s cubic-bezier(.65,0,.35,1);transform:scale(1.001)}
html.doi-jt-on .doi-jt-overlay{clip-path:circle(145% at var(--jt-x,50%) var(--jt-y,50%));-webkit-clip-path:circle(145% at var(--jt-x,50%) var(--jt-y,50%))}
html.doi-jt-instant .doi-jt-overlay{transition:none;clip-path:circle(145% at var(--jt-x,50%) var(--jt-y,50%));-webkit-clip-path:circle(145% at var(--jt-x,50%) var(--jt-y,50%))}
</style>
<script id="doi-jt-gate">
(function(d,w){var h=d.documentElement;
try{
if(w.sessionStorage.getItem('doiJT')){
w.sessionStorage.removeItem('doiJT');
h.classList.add('doi-jt-instant','doi-jt-on');
w.addEventListener('load',function(){
w.setTimeout(function(){
h.classList.remove('doi-jt-instant');
w.setTimeout(function(){h.classList.remove('doi-jt-on')},16);
},80);
});
}
}catch(e){}
})(document,window);
</script>
	<?php
}

add_action( 'wp_body_open', 'doi_print_journey_transition_cover', 2 );
add_action( 'wp_footer', 'doi_print_journey_transition_cover', 2 );
function doi_print_journey_transition_cover() {
	static $printed = false;
	if ( $printed || is_admin() || wp_doing_ajax() || wp_doing_cron() || doi_in_builder_preview() ) {
		return;
	}
	$printed = true;
	echo '<div class="doi-jt-overlay" aria-hidden="true"></div>' . "\n";
}

/**
 * Runs in <head>, before anything paints: decides whether this visit plays the door (once-per-visit and
 * phone settings are checked in the browser, so full-page caches stay correct), and hides the page
 * behind a still of the closed door until the intro script takes over.
 *
 * "Once per tab" means: arriving at the site (a fresh visit, a link from elsewhere, a typed address) and reloading
 * always show the door; coming back to the homepage from inside the site in the same tab (a menu link, the back
 * button) goes straight to the page, so the door does not stand in the way twice.
 *
 * If the intro script is not ready 6 s after the page's HTML has arrived, the page shows without the door, so a
 * failed script can never trap the visitor; the clock starts only then, so a slow page still gets its door.
 */
add_action( 'wp_head', 'doi_print_head', 1 );
function doi_print_head() {
	if ( ! doi_should_play() ) {
		return;
	}
	$s    = doi_settings();
	$idle = doi_image_url( 'desktop-idle.jpg' );
	$gate = array(
		'once'    => (bool) $s['once'],
		'onceAll' => (bool) $s['once_all'],
		'mobile'  => (bool) $s['mobile'],
		'forced'  => doi_is_forced(),
	);
	// The loading still has to match the live door exactly, so these rules mirror intro.js: portrait screens
	// (≤ 3:4, `pickSet`) use the phone photographs; either photograph is fitted to 760px of its width
	// (`fitWidth`) instead of `cover` when the screen is too narrow for its door, with a blurred copy above and below.
	$phone     = doi_image_url( 'phone-idle.jpg' );
	$pseudo    = 'content:"";position:absolute;top:0;right:0;bottom:0;left:0;background:50% 50%/cover no-repeat';
	?>
<link rel="preload" as="image" href="<?php echo esc_url( $idle ); ?>" media="(min-aspect-ratio: 3/4)">
<link rel="preload" as="image" href="<?php echo esc_url( doi_image_url( 'desktop-lit.jpg' ) ); ?>" media="(min-aspect-ratio: 3/4)">
<link rel="preload" as="image" href="<?php echo esc_url( $phone ); ?>" media="(max-aspect-ratio: 3/4)">
<link rel="preload" as="image" href="<?php echo esc_url( doi_image_url( 'phone-lit.jpg' ) ); ?>" media="(max-aspect-ratio: 3/4)">
<?php
	// the corridor's first frame, so it is already standing there the moment the door has opened
	if ( doi_hero_enabled() ) :
		$sets = doi_hero_sets();
		?>
<link rel="preload" as="image" href="<?php echo esc_url( doi_hero_poster( $sets['desktop'] ) ); ?>" media="(min-aspect-ratio: 3/4)">
<link rel="preload" as="image" href="<?php echo esc_url( doi_hero_poster( $sets['mobile'] ) ); ?>" media="(max-aspect-ratio: 3/4)">
<?php endif; ?>
<style id="doi-critical">
html.doi-on,html.doi-on body{overflow:hidden!important}
html.doi-on:not(.doi-ready) body{visibility:hidden}
.doi-cover{display:none}
html.doi-on .doi-cover{display:block;visibility:visible;position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483000;overflow:hidden;background:#dcc8a8 url("<?php echo esc_url( $idle ); ?>") 50% 50%/cover no-repeat}
html.doi-on .doi-cover::before,html.doi-on .doi-cover::after{<?php echo $pseudo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS. ?>;display:none}
html.doi-on .doi-cover::before{filter:blur(22px) brightness(.94);transform:scale(1.1)}
@media (max-aspect-ratio:760/941){
html.doi-on .doi-cover{background-image:none}
html.doi-on .doi-cover::before,html.doi-on .doi-cover::after{display:block;background-image:url("<?php echo esc_url( $idle ); ?>")}
html.doi-on .doi-cover::after{background-size:calc(100vw * 1672 / 760) auto}
}
@media (max-aspect-ratio:3/4){
html.doi-on .doi-cover{background-image:url("<?php echo esc_url( $phone ); ?>")}
html.doi-on .doi-cover::before,html.doi-on .doi-cover::after{display:none}
}
@media (max-aspect-ratio:760/1671){
html.doi-on .doi-cover{background-image:none}
html.doi-on .doi-cover::before,html.doi-on .doi-cover::after{display:block;background-image:url("<?php echo esc_url( $phone ); ?>")}
html.doi-on .doi-cover::after{background-size:calc(100vw * 941 / 760) auto}
}
</style>
<script id="doi-gate">
(function(d,w,g){var h=d.documentElement;
var nav='';try{nav=(w.performance.getEntriesByType('navigation')[0]||{}).type||''}catch(e){}
var inside=d.referrer.indexOf(location.protocol+'//'+location.host+'/')===0;
try{if(g.once&&!g.forced&&nav!=='reload'&&(nav==='back_forward'||inside)&&w.sessionStorage.getItem('doi_seen'))return}catch(e){}
if(g.onceAll&&!g.forced&&/(?:^|;\s*)doi_seen=1(?:;|$)/.test(d.cookie))return;
if(!g.mobile&&!g.forced&&w.matchMedia&&w.matchMedia('(max-width: 767px)').matches)return;
h.classList.add('doi-on');
var off=function(){if(!h.classList.contains('doi-ready'))h.classList.remove('doi-on')};
d.addEventListener('DOMContentLoaded',function(){w.setTimeout(off,6000)});w.setTimeout(off,30000);
})(document,window,<?php echo wp_json_encode( $gate ); ?>);
</script>
	<?php
}

/**
 * The closed-door still, first thing in <body>, so the very first paint is the door and never the page.
 * Themes without wp_body_open get it at the end of <body>; until then the page is hidden by the critical CSS.
 */
add_action( 'wp_body_open', 'doi_print_cover', 1 );
add_action( 'wp_footer', 'doi_print_cover', 1 );
function doi_print_cover() {
	static $printed = false;
	if ( $printed || ! doi_should_play() ) {
		return;
	}
	$printed = true;
	echo '<div class="doi-cover" aria-hidden="true"></div>' . "\n";
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'doi_action_links' );
function doi_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=door-open-intro' ) ) . '">' . esc_html__( 'Settings', 'door-open-intro' ) . '</a>' );
	return $links;
}
