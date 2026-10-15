<?php
/**
 * One-off: the demo page (528) becomes the Awakening waitlist: "the door is not open yet", with an Elementor form
 * that collects sign-ups under Elementor → Submissions. The page before it is kept in _doi_elementor_backup_20261010.
 */
return (function () {
$post_id = 528;
$raw     = get_post_meta( $post_id, '_elementor_data', true );
update_post_meta( $post_id, '_doi_elementor_backup_20261010', wp_slash( $raw ) );

$ids = array();
$id  = function () use ( &$ids ) { do { $n = substr( md5( uniqid( '', true ) ), 0, 7 ); } while ( isset( $ids[ $n ] ) ); $ids[ $n ] = 1; return $n; };
$px  = function ( $n ) { return array( 'unit' => 'px', 'size' => $n, 'sizes' => array() ); };
$pc  = function ( $n ) { return array( 'unit' => '%', 'size' => $n, 'sizes' => array() ); };
$em  = function ( $n ) { return array( 'unit' => 'em', 'size' => $n, 'sizes' => array() ); };
$box = function ( $t, $r, $b, $l, $u = 'px' ) { return array( 'unit' => $u, 'top' => (string) $t, 'right' => (string) $r, 'bottom' => (string) $b, 'left' => (string) $l, 'isLinked' => false ); };
$gap = function ( $n ) { return array( 'column' => (string) $n, 'row' => (string) $n, 'isLinked' => true, 'unit' => 'px', 'size' => $n ); };
$typo = function ( $prefix, $family, $sizes, $weight = '400', $lh = null, $extra = array() ) use ( $px, $em ) {
	$o = array( $prefix . '_typography' => 'custom', $prefix . '_font_size' => $px( $sizes[0] ), $prefix . '_font_size_tablet' => $px( $sizes[1] ), $prefix . '_font_size_mobile' => $px( $sizes[2] ), $prefix . '_font_weight' => $weight );
	if ( $family ) { $o[ $prefix . '_font_family' ] = $family; }
	if ( $lh ) { $o[ $prefix . '_line_height' ] = $em( $lh ); }
	foreach ( $extra as $k => $v ) { $o[ $prefix . '_' . $k ] = $v; }
	return $o;
};
$con = function ( $title, $settings, $children, $inner = true ) use ( $id, $box, $gap ) {
	return array( 'id' => $id(), 'elType' => 'container', 'isInner' => $inner,
		'settings' => array_merge( array( '_title' => $title, 'content_width' => 'full', 'padding' => $box( 0, 0, 0, 0 ), 'margin' => $box( 0, 0, 0, 0 ), 'flex_gap' => $gap( 0 ) ), $settings ),
		'elements' => $children );
};
$w = function ( $type, $settings ) use ( $id ) { return array( 'id' => $id(), 'elType' => 'widget', 'widgetType' => $type, 'isInner' => false, 'settings' => $settings, 'elements' => array() ); };

$INK = '#2B2620'; $TEXT = '#4A3E33'; $MUTED = '#6B6558'; $GOLD2 = '#B08D5A'; $BROWN = '#4B2A18'; $IVORY = '#FBF8F4';

$door = 356; // the closed carved door with light through the seam: "the door is not open yet"

$text = $con( 'Waitlist text', array(
	'flex_direction' => 'column', 'flex_align_items' => 'flex-start',
	'width' => $pc( 46 ), 'width_tablet' => $pc( 100 ), 'width_mobile' => $pc( 100 ),
	'z_index' => 1, 'animation' => 'fadeInUp',
), array(
	$w( 'heading', array_merge( array( 'title' => 'The Awakening Journal · Coming soon', 'header_size' => 'p', 'title_color' => '#9A8F7D' ),
		$typo( 'typography', '', array( 13, 13, 12 ), '600', 1.3, array( 'letter_spacing' => $px( 2 ), 'text_transform' => 'uppercase' ) ) ) ),
	$w( 'divider', array( 'style' => 'solid', 'weight' => $px( 1 ), 'color' => $GOLD2, 'width' => $px( 60 ), 'gap' => $px( 14 ), 'align' => 'left', '_margin' => $box( 4, 0, 18, 0 ) ) ),
	$w( 'heading', array_merge( array( 'title' => 'The door is not open yet.', 'header_size' => 'h1', 'title_color' => $INK, '_margin' => $box( 0, 0, 16, 0 ) ),
		$typo( 'typography', 'Playfair Display', array( 56, 44, 36 ), '400', 1.1 ) ) ),
	$w( 'heading', array_merge( array( 'title' => 'Awakening, the first DRUMI journal, is almost ready.', 'header_size' => 'p', 'title_color' => '#5A4632', '_margin' => $box( 0, 0, 14, 0 ) ),
		$typo( 'typography', 'Cormorant Garamond', array( 24, 23, 21 ), '400', 1.35, array( 'font_style' => 'italic' ) ) ) ),
	$w( 'text-editor', array_merge( array( 'editor' => '<p>Leave your name at the gate and we will let you know the moment it goes live, so you can be among the first to begin your journey.</p>', 'text_color' => $TEXT, 'paragraph_spacing' => $px( 0 ) ),
		$typo( 'typography', '', array( 17, 16, 16 ), '400', 1.75 ) ) ),

	// the waitlist card
	$con( 'Waitlist card', array(
		'flex_direction' => 'column', 'flex_gap' => $gap( 6 ),
		'width' => $pc( 100 ), 'margin' => $box( 30, 0, 0, 0 ), 'margin_mobile' => $box( 26, 0, 0, 0 ),
		'padding' => $box( 30, 30, 26, 30 ), 'padding_mobile' => $box( 24, 20, 20, 20 ),
		'background_background' => 'classic', 'background_color' => 'rgba(255,253,249,0.86)',
		'border_border' => 'solid', 'border_width' => $box( 1, 1, 1, 1 ), 'border_color' => 'rgba(176,141,90,0.35)',
		'border_radius' => $box( 14, 14, 14, 14 ),
		'box_shadow_box_shadow_type' => 'yes',
		'box_shadow_box_shadow' => array( 'horizontal' => 0, 'vertical' => 24, 'blur' => 50, 'spread' => -28, 'color' => 'rgba(80,55,25,0.45)' ),
	), array(
		$w( 'heading', array_merge( array( 'title' => 'Join the waitlist', 'header_size' => 'h2', 'title_color' => $INK ),
			$typo( 'typography', 'Playfair Display', array( 26, 25, 23 ), '400', 1.2 ) ) ),
		$w( 'heading', array_merge( array( 'title' => 'One gentle email when the door opens. Nothing else.', 'header_size' => 'p', 'title_color' => $MUTED, '_margin' => $box( 0, 0, 12, 0 ) ),
			$typo( 'typography', 'Cormorant Garamond', array( 19, 19, 18 ), '400', 1.35, array( 'font_style' => 'italic' ) ) ) ),
		$w( 'form', array_merge( array(
			'form_name'   => 'Awakening waitlist',
			'form_fields' => array(
				array( '_id' => $id(), 'custom_id' => 'name', 'field_type' => 'text', 'field_label' => 'Name', 'placeholder' => 'Your name', 'width' => '50', 'width_tablet' => '50', 'width_mobile' => '100' ),
				array( '_id' => $id(), 'custom_id' => 'email', 'field_type' => 'email', 'required' => 'true', 'field_label' => 'Email', 'placeholder' => 'Your email address', 'width' => '50', 'width_tablet' => '50', 'width_mobile' => '100' ),
				array( '_id' => $id(), 'custom_id' => 'journey', 'field_type' => 'hidden', 'field_label' => 'Journey', 'field_value' => 'Awakening (waitlist on the demo page)' ),
			),
			'show_labels' => '', 'input_size' => 'md', 'button_size' => 'md', 'button_width' => '100',
			'button_text' => 'Notify me when it opens',
			'selected_button_icon' => array( 'value' => 'fas fa-arrow-right', 'library' => 'fa-solid' ), 'button_icon_align' => 'row-reverse', 'button_icon_indent' => $px( 12 ),
			'submit_actions' => array( 'save-to-database' ),
			'custom_messages' => 'yes',
			'success_message' => 'Your name is at the gate. When it opens, you will be the first to know. Until then, keep listening to what lives within you.',
			'error_message'   => 'Something went wrong. Please try again in a moment.',
			'required_field_message' => 'Please add your email address.',
			'invalid_message' => 'Please check your email address.',
			'column_gap' => $px( 12 ), 'row_gap' => $px( 12 ),
			'field_text_color' => $INK, 'field_background_color' => '#FFFFFF', 'field_border_color' => 'rgba(176,141,90,0.5)',
			'field_border_width' => $box( 1, 1, 1, 1 ), 'field_border_radius' => $box( 8, 8, 8, 8 ),
			'button_background_color' => $BROWN, 'button_text_color' => '#FBF3E6',
			'button_background_hover_color' => '#2F190D', 'button_hover_color' => '#FFFFFF',
			'button_border_radius' => $box( 8, 8, 8, 8 ), 'button_text_padding' => $box( 16, 24, 16, 24 ),
			'success_message_color' => $BROWN, 'error_message_color' => '#9B3B2A',
		),
			$typo( 'field_typography', '', array( 16, 16, 16 ), '400', 1.4 ),
			$typo( 'button_typography', 'Playfair Display', array( 18, 18, 17 ), '400', 1.2 ),
			$typo( 'message_typography', 'Cormorant Garamond', array( 20, 20, 19 ), '400', 1.4, array( 'font_style' => 'italic' ) )
		) ),
	) ),
	$w( 'text-editor', array_merge( array( 'editor' => '<p>We only use your details to tell you when Awakening is live.</p>', 'text_color' => '#8A8174', 'paragraph_spacing' => $px( 0 ), '_margin' => $box( 14, 0, 0, 0 ) ),
		$typo( 'typography', '', array( 13, 13, 13 ), '400', 1.5 ) ) ),
) );

$css = "/* desktop: a soft ivory wash from the left behind the text; the closed door and its line of light stay clear on the right */\n@media (min-width: 1025px) {\n\tselector::before { background-image: linear-gradient(90deg, rgba(251,248,244,.97) 0%, rgba(251,248,244,.9) 34%, rgba(251,248,244,.45) 52%, rgba(251,248,244,0) 66%) !important; }\n}\n/* tablet & phone: the door sits above the text and fades into the ivory */\n@media (max-width: 1024px) {\n\tselector::before { background-image: linear-gradient(180deg, rgba(251,248,244,0) calc(100vw * .52), #FBF8F4 calc(100vw * .73)) !important; }\n}\n@media (max-width: 767px) {\n\tselector::before { background-image: linear-gradient(180deg, rgba(251,248,244,0) calc(100vw * .8), #FBF8F4 calc(100vw * 1.07)) !important; }\n}";

$section = $con( 'Awakening waitlist', array(
	'html_tag' => 'section', '_element_id' => 'waitlist',
	'min_height' => array( 'unit' => 'custom', 'size' => 'max(760px, 100vh)', 'sizes' => array() ), 'min_height_tablet' => $px( 0 ), 'min_height_mobile' => $px( 0 ),
	'flex_direction' => 'row', 'flex_align_items' => 'center',
	'background_background' => 'classic', 'background_color' => $IVORY,
	'background_image' => array( 'id' => $door, 'url' => wp_get_attachment_url( $door ), 'source' => 'library', 'alt' => '', 'size' => '' ),
	'background_position' => 'center right', 'background_repeat' => 'no-repeat', 'background_size' => 'cover',
	'background_position_tablet' => 'initial', 'background_xpos_tablet' => array( 'unit' => 'vw', 'size' => -30, 'sizes' => array() ), 'background_ypos_tablet' => $px( 0 ), 'background_size_tablet' => 'initial', 'background_bg_width_tablet' => $pc( 130 ),
	'background_position_mobile' => 'initial', 'background_xpos_mobile' => array( 'unit' => 'vw', 'size' => -89, 'sizes' => array() ), 'background_ypos_mobile' => $px( 0 ), 'background_size_mobile' => 'initial', 'background_bg_width_mobile' => $pc( 190 ),
	'background_overlay_background' => 'gradient', 'background_overlay_color' => 'rgba(251,248,244,0.95)', 'background_overlay_color_b' => 'rgba(251,248,244,0)', 'background_overlay_color_b_stop' => $pc( 62 ),
	'background_overlay_gradient_angle' => array( 'unit' => 'deg', 'size' => 90, 'sizes' => array() ), 'background_overlay_opacity' => array( 'unit' => 'px', 'size' => 1, 'sizes' => array() ),
	'custom_css' => $css,
), array(
	$con( 'Waitlist inner', array(
		'content_width' => 'boxed', 'boxed_width' => $px( 1379 ), 'flex_direction' => 'row', 'flex_align_items' => 'center',
		'padding' => $box( 140, 24, 100, 24 ),
		'padding_tablet' => array( 'unit' => 'custom', 'top' => 'calc(73vw + 24px)', 'right' => '30px', 'bottom' => '84px', 'left' => '30px', 'isLinked' => false ),
		'padding_mobile' => array( 'unit' => 'custom', 'top' => 'calc(107vw + 4px)', 'right' => '20px', 'bottom' => '64px', 'left' => '20px', 'isLinked' => false ),
	), array( $text ) ),
), false );

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( array( $section ) ) ) );
update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
$h = wp_remote_retrieve_body( wp_remote_get( get_permalink( $post_id ) . '?nc=' . time(), array( 'timeout' => 30, 'sslverify' => false ) ) );
$c = file_get_contents( wp_upload_dir()['basedir'] . '/elementor/css/post-' . $post_id . '.css' );
return array( 'ok' => strlen( $h ) > 20000 && false === stripos( $h, 'critical error' ), 'form' => substr_count( $h, 'elementor-form' ), 'html' => base64_encode( gzencode( $h, 9 ) ), 'css' => base64_encode( gzencode( $c, 9 ) ) );
})();
