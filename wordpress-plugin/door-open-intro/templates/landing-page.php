<?php
/**
 * DRUMI Sanctuary — Pure Canvas Landing Page Template
 * ─────────────────────────────────────────────────────────────────────────────
 * Renders the full DRUMI Sanctuary experience from the very top of the page:
 * 1. Hero Door Open Section (100vh full viewport)
 * 2. Section 1: OUR FOUNDER (Editorial copy + Photo)
 * 3. Section 2: OUR VISION (Centered philosophy + concentric water rings)
 * 4. Section 3: FEATURE CARDS (The Dream Journal & The Blog) + SACRED FOOTER BAR
 *
 * Bypasses all theme headers, footers, sidebars, and default blog posts.
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> style="margin:0; padding:0; background:#ede6df;">
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?> — DRUMI Sanctuary</title>

  <!-- Google Fonts: Cinzel & Cormorant Garamond -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500&display=swap" rel="stylesheet">

  <?php wp_head(); ?>

  <style>
    /* Reset body and html for seamless full-bleed experience */
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      background: #ede6df !important;
      color: #342921 !important;
      font-family: "Cormorant Garamond", Georgia, serif !important;
      overflow-x: hidden !important;
      -webkit-font-smoothing: antialiased;
    }
    /* Hide admin bar margin if present */
    html { margin-top: 0 !important; }
    #wpadminbar { opacity: 0.25; transition: opacity 0.3s; }
    #wpadminbar:hover { opacity: 1; }
  </style>
</head>
<body <?php body_class( 'doi-landing-canvas' ); ?>>

<?php
// 1. Hero Door Section
$door_img = doi_get( 'door_image_url' ) ?: DOI_PLUGIN_URL . 'assets/threshold-doors.jpg';
$room_img = doi_get( 'room_image_url' ) ?: DOI_PLUGIN_URL . 'assets/room-interior.jpg';
$btn_text = doi_get( 'button_text' )     ?: 'JOIN THE JOURNEY';
$url      = doi_get( 'preload_url' )     ?: '';
$skip_key = doi_get( 'session_once' )    ? 'doi_session_entered' : '';

echo doi_get_threshold_html( [
    'door_img' => $door_img,
    'room_img' => $room_img,
    'btn_text' => $btn_text,
    'url'      => $url,
    'skip_key' => $skip_key,
    'is_hero'  => true,
] );

// 2. The 3 Sections Underneath (Founder, Vision, Cards & Footer)
echo doi_get_landing_sections_html();

wp_footer();
?>

</body>
</html>
