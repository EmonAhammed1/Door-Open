<?php
/**
 * One-off: lays out the homepage (page 98) for "website 2.0" with Elementor containers and widgets only.
 * Run once on the server (Novamira execute-php); the page before it is kept in the meta _doi_elementor_backup_20261010.
 */
return (function () {
$post_id = 98;
$orig    = get_post_meta( $post_id, '_doi_elementor_backup_20261010', true );
if ( ! $orig ) {
	return array( 'error' => 'no backup' );
}
$data = json_decode( $orig, true );

/* ---- helpers: Elementor settings ---- */
$ids = array();
$id  = function () use ( &$ids ) {
	do {
		$n = substr( md5( uniqid( '', true ) ), 0, 7 );
	} while ( isset( $ids[ $n ] ) );
	$ids[ $n ] = 1;
	return $n;
};
$px   = function ( $n ) { return array( 'unit' => 'px', 'size' => $n, 'sizes' => array() ); };
$pc   = function ( $n ) { return array( 'unit' => '%', 'size' => $n, 'sizes' => array() ); };
$em   = function ( $n ) { return array( 'unit' => 'em', 'size' => $n, 'sizes' => array() ); };
$box  = function ( $t, $r, $b, $l ) { return array( 'unit' => 'px', 'top' => (string) $t, 'right' => (string) $r, 'bottom' => (string) $b, 'left' => (string) $l, 'isLinked' => false ); };
$gap  = function ( $n ) { return array( 'column' => (string) $n, 'row' => (string) $n, 'isLinked' => true, 'unit' => 'px', 'size' => $n ); };
$resp = function ( $key, $d, $t = null, $m = null ) {
	$o = array( $key => $d );
	if ( null !== $t ) { $o[ $key . '_tablet' ] = $t; }
	if ( null !== $m ) { $o[ $key . '_mobile' ] = $m; }
	return $o;
};
$typo = function ( $family, $sizes, $weight = '400', $lh = null, $extra = array() ) use ( $px, $em ) {
	$o = array(
		'typography_typography'       => 'custom',
		'typography_font_size'        => $px( $sizes[0] ),
		'typography_font_size_tablet' => $px( $sizes[1] ),
		'typography_font_size_mobile' => $px( $sizes[2] ),
		'typography_font_weight'      => $weight,
	);
	if ( $family ) { $o['typography_font_family'] = $family; }
	if ( $lh ) { $o['typography_line_height'] = $em( $lh ); }
	return array_merge( $o, $extra );
};

/* Playfair Display draws old-style figures ("o1"); numbers here use lining figures, as in the reference */
$LINING = array( 'custom_css' => "/* lining figures: 01, 02, 03 and the price */\nselector .elementor-heading-title { font-variant-numeric: lining-nums; }" );

/* colours */
$INK = '#2B2620'; $TEXT = '#4A3E33'; $MUTED = '#6B6558'; $GOLD = '#9C7A45'; $GOLD2 = '#B08D5A';
$IVORY = '#FBF8F3'; $IVORY2 = '#F4EFE8'; $BROWN = '#4B2A18';

$con = function ( $title, $settings, $children, $inner = true ) use ( $id, $box, $gap ) {
	return array(
		'id'       => $id(),
		'elType'   => 'container',
		'isInner'  => $inner,
		'settings' => array_merge( array( '_title' => $title, 'content_width' => 'full', 'padding' => $box( 0, 0, 0, 0 ), 'margin' => $box( 0, 0, 0, 0 ), 'flex_gap' => $gap( 0 ) ), $settings ),
		'elements' => $children,
	);
};
$w = function ( $type, $settings ) use ( $id ) {
	return array( 'id' => $id(), 'elType' => 'widget', 'widgetType' => $type, 'isInner' => false, 'settings' => $settings, 'elements' => array() );
};

/* text pieces */
$kicker = function ( $text ) use ( $w, $typo, $px ) {
	return $w( 'heading', array_merge( array( 'title' => $text, 'header_size' => 'p', 'title_color' => '#9A8F7D' ),
		$typo( '', array( 13, 13, 13 ), '600', 1.3, array( 'typography_letter_spacing' => $px( 2 ), 'typography_text_transform' => 'uppercase' ) ) ) );
};
$rule = function () use ( $w, $px, $GOLD2 ) {
	return $w( 'divider', array( 'style' => 'solid', 'weight' => $px( 1 ), 'color' => $GOLD2, 'width' => $px( 60 ), 'gap' => $px( 14 ), 'align' => 'left', '_margin' => array( 'unit' => 'px', 'top' => '4', 'right' => '0', 'bottom' => '18', 'left' => '0', 'isLinked' => false ) ) );
};
$title = function ( $text, $sizes = array( 52, 40, 32 ) ) use ( $w, $typo, $box, $INK ) {
	return $w( 'heading', array_merge( array( 'title' => $text, 'header_size' => 'h2', 'title_color' => $INK, '_margin' => $box( 0, 0, 24, 0 ), '_margin_mobile' => $box( 0, 0, 18, 0 ) ),
		$typo( 'Playfair Display', $sizes, '400', 1.15 ) ) );
};
$copy = function ( $html, $sizes = array( 17, 16, 16 ) ) use ( $w, $typo, $px, $TEXT ) {
	return $w( 'text-editor', array_merge( array( 'editor' => $html, 'text_color' => $TEXT, 'paragraph_spacing' => $px( 14 ) ),
		$typo( '', $sizes, '400', 1.75 ) ) );
};
$serif_line = function ( $text, $tag, $sizes, $color, $margin = null, $italic = true ) use ( $w, $typo ) {
	return $w( 'heading', array_merge( array( 'title' => $text, 'header_size' => $tag, 'title_color' => $color ), $margin ? array( '_margin' => $margin ) : array(),
		$typo( 'Cormorant Garamond', $sizes, '400', 1.4, $italic ? array( 'typography_font_style' => 'italic' ) : array() ) ) );
};
$arrow = array( 'selected_icon' => array( 'value' => 'fas fa-arrow-right', 'library' => 'fa-solid' ), 'icon_align' => 'row-reverse', 'icon_indent' => array( 'unit' => 'px', 'size' => 14, 'sizes' => array() ) );
$solid_btn = function ( $text, $url, $bg, $bg_hover, $radius, $extra = array() ) use ( $w, $typo, $box, $arrow ) {
	return $w( 'button', array_merge( array(
		'text' => $text, 'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
		'align' => 'left', 'align_mobile' => 'justify',
		'background_color' => $bg, 'button_text_color' => '#FBF3E6',
		'button_background_hover_color' => $bg_hover, 'hover_color' => '#FFFFFF',
		'border_radius' => $box( $radius, $radius, $radius, $radius ),
		'text_padding' => $box( 20, 44, 20, 44 ), 'text_padding_mobile' => $box( 18, 24, 18, 24 ),
		'button_box_shadow_box_shadow_type' => 'yes',
		'button_box_shadow_box_shadow' => array( 'horizontal' => 0, 'vertical' => 16, 'blur' => 32, 'spread' => -18, 'color' => 'rgba(75,42,24,0.75)' ),
		'hover_animation' => 'float',
	), $typo( 'Playfair Display', array( 20, 19, 18 ), '400', 1.2 ), $arrow, $extra ) );
};
$line_btn = function ( $text, $url ) use ( $w, $typo, $box, $px, $arrow, $GOLD, $BROWN ) {
	return $w( 'button', array_merge( array(
		'text' => $text, 'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
		'align' => 'left', 'align_mobile' => 'justify',
		'background_color' => 'rgba(255,255,255,0)', 'button_text_color' => '#7A5C33',
		'button_background_hover_color' => $BROWN, 'hover_color' => '#FBF3E6', 'button_hover_border_color' => $BROWN,
		'border_border' => 'solid', 'border_width' => $box( 1, 1, 1, 1 ), 'border_color' => $GOLD,
		'border_radius' => $box( 4, 4, 4, 4 ),
		'text_padding' => $box( 16, 26, 16, 26 ),
	), $typo( 'Playfair Display', array( 17, 17, 17 ), '400', 1.2 ), $arrow ) );
};
/* a photo as a container background, faded into the ivory by Elementor's own gradient overlay
   (Style → Background Overlay), so the image and the fade can both be changed in Elementor */
$photo_box = function ( $title_, $att, $heights, $overlay, $extra = array() ) use ( $con, $px, $pc ) {
	return $con( $title_, array_merge( array(
		'min_height' => $px( $heights[0] ), 'min_height_tablet' => $px( $heights[1] ), 'min_height_mobile' => $px( $heights[2] ),
		'background_background' => 'classic',
		'background_image' => array( 'id' => $att, 'url' => wp_get_attachment_url( $att ), 'source' => 'library', 'alt' => '', 'size' => '' ),
		'background_position' => 'center center', 'background_repeat' => 'no-repeat', 'background_size' => 'cover',
		'background_overlay_background' => 'gradient',
		'background_overlay_opacity' => array( 'unit' => 'px', 'size' => 1, 'sizes' => array() ),
	), $overlay, $extra ), array() );
};
/* linear: ivory on the text side, clear by $stop; on tablet and phone (photo on top) ivory at the bottom */
$fade_linear = function ( $ivory, $angle ) use ( $pc ) {
	return array(
		'background_overlay_color' => $ivory, 'background_overlay_color_stop' => $pc( 0 ),
		'background_overlay_color_b' => 'rgba(251,248,244,0)', 'background_overlay_color_b_stop' => $pc( 46 ), 'background_overlay_color_b_stop_tablet' => $pc( 38 ), 'background_overlay_color_b_stop_mobile' => $pc( 38 ),
		'background_overlay_gradient_type' => 'linear',
		'background_overlay_gradient_angle' => array( 'unit' => 'deg', 'size' => $angle, 'sizes' => array() ),
		'background_overlay_gradient_angle_tablet' => array( 'unit' => 'deg', 'size' => 0, 'sizes' => array() ),
		'background_overlay_gradient_angle_mobile' => array( 'unit' => 'deg', 'size' => 0, 'sizes' => array() ),
	);
};

/* =========================================================================================================
   1. The Awakening Journal: "Meet DRUMI" and "How it works" in one, after the client's reference
   ========================================================================================================= */
$step = function ( $no, $name, $line, $img, $last ) use ( $con, $w, $typo, $px, $pc, $box, $gap, $GOLD, $GOLD2, $INK, $MUTED, $resp, $LINING ) {
	$row = array(
		$w( 'heading', array_merge( array( 'title' => $no, 'header_size' => 'p', 'title_color' => $GOLD ), $typo( 'Playfair Display', array( 40, 34, 32 ), '400', 1 ), $LINING ) ),
		$w( 'heading', array_merge( array( 'title' => $name, 'header_size' => 'h3', 'title_color' => $INK ), $typo( 'Playfair Display', array( 40, 34, 32 ), '400', 1 ) ) ),
	);
	if ( ! $last ) {
		// the fine gold thread running on to the next step (desktop only)
		$row[] = $w( 'divider', array( 'style' => 'solid', 'weight' => $px( 1 ), 'color' => $GOLD2, 'width' => $pc( 100 ), 'gap' => $px( 2 ), '_flex_size' => 'grow', 'hide_tablet' => 'hidden-tablet', 'hide_mobile' => 'hidden-mobile' ) );
	}
	return $con( 'Step ' . $no, array_merge(
		array( 'flex_direction' => 'column', 'width' => $pc( 31 ), 'width_mobile' => $pc( 100 ), '_flex_size' => 'grow', 'animation' => 'fadeInUp' ),
		$resp( 'animation_delay', 0 )
	), array(
		$w( 'image', array(
			'image' => array( 'id' => $img, 'url' => wp_get_attachment_url( $img ), 'source' => 'library', 'alt' => get_post_meta( $img, '_wp_attachment_image_alt', true ) ),
			'image_size' => 'full', 'width' => $pc( 100 ), 'height' => $px( 240 ), 'height_tablet' => $px( 170 ), 'height_mobile' => $px( 210 ), 'object-fit' => 'cover',
			'image_border_radius' => $box( 2, 2, 2, 2 ),
			'image_box_shadow_box_shadow_type' => 'yes',
			'image_box_shadow_box_shadow' => array( 'horizontal' => 0, 'vertical' => 24, 'blur' => 40, 'spread' => -28, 'color' => 'rgba(80,55,25,0.55)' ),
		) ),
		$con( 'Step title', array( 'flex_direction' => 'row', 'flex_wrap' => 'nowrap', 'flex_align_items' => 'center', 'flex_gap' => $gap( 16 ), 'margin' => $box( 26, 0, 8, 0 ), 'margin_mobile' => $box( 20, 0, 6, 0 ) ), $row ),
		$w( 'text-editor', array_merge( array( 'editor' => '<p>' . $line . '</p>', 'text_color' => $MUTED, 'paragraph_spacing' => $px( 0 ),
			'_padding' => $box( 0, 0, 0, 62 ), '_padding_tablet' => $box( 0, 0, 0, 0 ), '_padding_mobile' => $box( 0, 0, 0, 52 ) ),
			$typo( 'Cormorant Garamond', array( 21, 19, 20 ), '400', 1.4 ) ) ),
	) );
};
$journal = $con( 'The Awakening Journal', array(
	'_element_id' => 'awakening-journal', 'html_tag' => 'section',
	'content_width' => 'boxed', 'boxed_width' => $px( 1379 ), 'flex_direction' => 'column',
	'padding' => $box( 110, 24, 110, 24 ), 'padding_tablet' => $box( 84, 30, 84, 30 ), 'padding_mobile' => $box( 64, 20, 64, 20 ),
	'background_background' => 'classic', 'background_color' => '#FBF8F4',
	'custom_css' => "/* lands below the fixed site header when a button links here */\nselector { scroll-margin-top: 84px; }",
), array(
	$con( 'Journal intro + photo', array(
		'flex_direction' => 'row', 'flex_direction_mobile' => 'column-reverse', 'flex_align_items' => 'center', 'flex_gap' => $gap( 0 ),
	), array(
		$con( 'Journal intro', array(
			'flex_direction' => 'column', 'flex_align_items' => 'flex-start',
			'width' => $pc( 50 ), 'width_tablet' => $pc( 52 ), 'width_mobile' => $pc( 100 ),
			'padding' => $box( 0, 40, 0, 0 ), 'padding_tablet' => $box( 0, 24, 0, 0 ), 'padding_mobile' => $box( 24, 0, 0, 0 ), 'z_index' => 1, 'animation' => 'fadeInUp',
		), array(
			$kicker( 'The Awakening Journal' ),
			$rule(),
			$title( 'Give yourself the first attention of the day.' ),
			$copy( '<p>DRUMI is a dream and reflection journal.<br />Your personal journey, guided on paper.</p><p>A morning ritual to explore your dreams, reconnect with yourself, and let who you truly are guide how you live.</p>' ),
		) ),
		// a radial overlay: the journal clear in the middle, melting into the ivory on every side
		$photo_box( 'Journal photo', 804, array( 560, 420, 320 ), array(
			'background_overlay_color' => 'rgba(251,248,244,0)', 'background_overlay_color_stop' => $pc( 28 ),
			'background_overlay_color_b' => '#FBF8F4', 'background_overlay_color_b_stop' => $pc( 80 ),
			'background_overlay_gradient_type' => 'radial', 'background_overlay_gradient_position' => 'center center',
		), array( 'width' => $pc( 50 ), 'width_tablet' => $pc( 48 ), 'width_mobile' => $pc( 100 ) ) ),
	) ),
	$con( 'Journal steps', array(
		'flex_direction' => 'row', 'flex_direction_mobile' => 'column', 'flex_wrap' => 'nowrap',
		'flex_gap' => $gap( 32 ), 'flex_gap_tablet' => $gap( 20 ), 'flex_gap_mobile' => $gap( 44 ),
		'margin' => $box( 40, 0, 0, 0 ), 'margin_mobile' => $box( 44, 0, 0, 0 ), 'z_index' => 1,
	), array(
		$step( '01', 'Remember', 'Begin with what stayed with you.', 859, false ),
		$step( '02', 'Explore', 'Follow the gentle prompts.', 860, false ),
		$step( '03', 'Awaken', 'Turn your insights into wisdom.', 861, true ),
	) ),
	$con( 'Journal order', array(
		'flex_direction' => 'row', 'flex_direction_mobile' => 'column', 'flex_justify_content' => 'center', 'flex_align_items' => 'center',
		'flex_gap' => $gap( 36 ), 'flex_gap_mobile' => $gap( 18 ),
		'margin' => $box( 72, 0, 0, 0 ), 'margin_mobile' => $box( 52, 0, 0, 0 ), 'animation' => 'fadeInUp',
	), array(
		$w( 'heading', array_merge( array( 'title' => '€69', 'header_size' => 'p', 'title_color' => $GOLD ), $typo( 'Playfair Display', array( 40, 38, 36 ), '400', 1 ), $LINING ) ),
		$solid_btn( 'Order your journal', home_url( '/demo/' ), $BROWN, '#2F190D', 8, array( 'text_padding' => $box( 22, 52, 22, 52 ) ) ),
		$w( 'text-editor', array_merge( array( 'editor' => '<p>No dream experience needed.<br />Go at your own pace.</p>', 'text_color' => $MUTED, 'paragraph_spacing' => $px( 0 ),
			'align_mobile' => 'center',
			'_border_border' => 'solid', '_border_width' => $box( 0, 0, 0, 1 ), '_border_width_mobile' => $box( 0, 0, 0, 0 ), '_border_color' => 'rgba(43,38,32,0.35)',
			'_padding' => $box( 6, 0, 6, 32 ), '_padding_mobile' => $box( 0, 0, 0, 0 ) ),
			$typo( 'Cormorant Garamond', array( 19, 19, 19 ), '400', 1.45 ) ) ),
	) ),
), false );

/* =========================================================================================================
   split sections: text on one side, a photo melting into the ivory on the other; on phones the photo on top
   ========================================================================================================= */
$split = function ( $title_, $bg, $text_children, $photo, $photo_first = false ) use ( $con, $box, $pc, $px ) {
	$text = $con( $title_ . ' text', array(
		'flex_direction' => 'column', 'flex_justify_content' => 'center', 'flex_align_items' => 'flex-start',
		'width' => $pc( 50 ), 'width_tablet' => $pc( 100 ), 'width_mobile' => $pc( 100 ),
		'min_height' => $px( 600 ), 'min_height_tablet' => $px( 0 ), 'min_height_mobile' => $px( 0 ),
		'padding' => $photo_first ? $box( 80, 120, 80, 60 ) : $box( 80, 60, 80, 120 ),
		'padding_tablet' => $box( 24, 30, 72, 30 ), 'padding_mobile' => $box( 16, 20, 64, 20 ),
		'z_index' => 1, 'animation' => 'fadeInUp',
	), $text_children );
	return $con( $title_, array_merge( array(
		'html_tag' => 'section',
		'flex_direction' => 'row', 'flex_direction_tablet' => $photo_first ? 'column' : 'column-reverse', 'flex_direction_mobile' => $photo_first ? 'column' : 'column-reverse',
		'flex_align_items' => 'stretch',
		'background_background' => 'classic', 'background_color' => $bg,
	), array() ), $photo_first ? array( $photo, $text ) : array( $text, $photo ), false );
};
$split_photo = function ( $title_, $att, $bg, $side ) use ( $photo_box, $fade_linear, $pc ) {
	return $photo_box( $title_, $att, array( 600, 460, 320 ), $fade_linear( $bg, 'right' === $side ? 90 : 270 ), array( 'width' => $pc( 50 ), 'width_tablet' => $pc( 100 ), 'width_mobile' => $pc( 100 ) ) );
};

$method = $split( 'The DRUMI Method', '#F4EFE8', array(
	$kicker( 'The DRUMI Method' ),
	$rule(),
	$title( 'A deeper connection begins within.' ),
	$copy( '<p>Behind DRUMI is a carefully crafted method that gently guides you into your inner world. Thoughtful questions invite you to explore your dreams, recognise patterns, and listen to what needs your attention.</p><p>As you bring those discoveries into your choices and actions, something begins to shift. This is where manifestation begins: giving what you discover within a place in the life you live.</p>' ),
	$w( 'spacer', array( 'space' => $px( 30 ), 'space_mobile' => $px( 24 ) ) ),
	$line_btn( 'Explore the world behind DRUMI', home_url( '/demo/' ) ),
), $split_photo( 'Method photo', 88, '#F4EFE8', 'right' ) );

$finder = $split( 'Find your portal', '#F4EFE8', array(
	$kicker( 'Find your portal' ),
	$rule(),
	$title( 'Not sure which door to open?' ),
	$copy( '<p>Start with what’s on your mind. A few thoughtful questions help you explore what you need right now — and discover the DRUMI journey that speaks to you.</p>' ),
	$w( 'spacer', array( 'space' => $px( 30 ), 'space_mobile' => $px( 24 ) ) ),
	$solid_btn( 'Find your portal', '#find-your-portal', '#AE8850', '#8D6A37', 999, array( 'text_padding' => $box( 17, 34, 17, 34 ), 'button_box_shadow_box_shadow' => array( 'horizontal' => 0, 'vertical' => 14, 'blur' => 28, 'spread' => -16, 'color' => 'rgba(150,110,50,0.8)' ) ) + $typo( 'Playfair Display', array( 18, 18, 18 ), '400', 1.2 ) ),
	$serif_line( 'A moment of reflection. A place to begin.', 'p', array( 18, 18, 18 ), $MUTED, $box( 18, 0, 0, 0 ) ),
	$w( 'shortcode', array( 'shortcode' => '[drumi_portal_finder]' ) ),
), $split_photo( 'Find your portal photo', 188, '#F4EFE8', 'right' ) );
$finder['settings']['_element_id'] = 'find-your-portal-section';
$finder['settings']['custom_css']  = "/* lands below the fixed site header when a button links here */\nselector { scroll-margin-top: 84px; }";

$pro = $split( 'DRUMI for professionals', '#F4EFE8', array(
	$kicker( 'DRUMI for coaches & professionals' ),
	$rule(),
	$title( 'Bring the power of dreams into your practice.' ),
	$serif_line( 'Open a new starting point for reflection in your coaching, training or group sessions.', 'p', array( 23, 22, 21 ), '#5A4632', $box( 0, 0, 18, 0 ) ),
	$copy( '<p>Discover how the DRUMI method can help people uncover their own insights, connect with their inner guidance, and move towards change with greater confidence and a stronger sense of ownership.</p>' ),
	$w( 'spacer', array( 'space' => $px( 30 ), 'space_mobile' => $px( 24 ) ) ),
	$line_btn( 'Explore DRUMI for professionals', '#waitlist' ),
), $split_photo( 'Professionals photo', 862, '#F4EFE8', 'left' ), true );

/* =========================================================================================================
   existing sections: the new text from website 2.0
   ========================================================================================================= */
$story_p1 = '<p>I kept falling into familiar patterns, crossing my own boundaries, and finding my way back to myself — over and over again.</p><p>Then I began recognising those same patterns in my dreams. Reflecting on them helped me see what was happening beneath the surface, revealing insights I had struggled to recognise while living in survival mode.</p>';
$story_p2 = '<p>Those insights helped me find new perspectives, make more conscious choices, and begin creating real change in my life.</p><p>That experience became the heart of DRUMI: a space to explore your dreams, understand yourself more deeply, and turn inner insight into personal transformation.</p>';
$set = array(
	'a8b89dd' => array( 'title' => 'Caught in the same patterns.' ),
	'8269733' => array( 'title' => 'Caught in the same patterns.' ),
	'd2b319a' => array( 'title' => 'Caught in the same patterns.' ),
	'e7d3977' => array( 'editor' => $story_p1 ),
	'e8bce7e' => array( 'editor' => $story_p1 ),
	'8fed8bc' => array( 'editor' => $story_p2 ),
	'ee387a9' => array( 'editor' => $story_p2 ),
	'8bb36af' => array( 'editor' => '<p>Ready to break free from repeating patterns, feel more confident in your choices, or build relationships where you can be yourself?</p><p>Each DRUMI portal invites you to explore a theme through your dreams — helping you understand what holds you back, what you need, and how you want to move forward.</p>' ),
	'2c05d15' => array( 'title' => 'Which dream journey is calling you?' ),
	'209300e' => array( '_element_id' => 'waitlist' ),
	'7ba600c' => array( 'padding' => $box( 90, 0, 90, 120 ) ),
);
$found = array();
$apply = function ( &$els ) use ( &$apply, $set, &$found ) {
	foreach ( $els as &$e ) {
		if ( isset( $set[ $e['id'] ] ) ) {
			$e['settings'] = array_merge( $e['settings'], $set[ $e['id'] ] );
			$found[]       = $e['id'];
		}
		if ( ! empty( $e['elements'] ) ) {
			$apply( $e['elements'] );
		}
	}
};
$apply( $data );
if ( count( $found ) !== count( $set ) ) {
	return array( 'missing' => array_values( array_diff( array_keys( $set ), $found ) ) );
}
$top = array();
foreach ( $data as $e ) {
	$top[ $e['id'] ] = $e;
}
$wl                         = $top['209300e'];
$wl['settings']['custom_css'] = trim( ( $wl['settings']['custom_css'] ?? '' ) . "\nselector { scroll-margin-top: 84px; }" );

$order = array( $journal, $method, $top['2ee247a2'], $finder, $top['19a661a'], $top['56f778a'], $pro, $wl, $top['2eb982b'] );
update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $order ) ) );
delete_post_meta( $post_id, '_elementor_css' );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
return array( 'ok' => true, 'sections' => array_map( function ( $e ) { return $e['id'] . ' ' . ( $e['settings']['_title'] ?? '' ); }, $order ) );
})();
