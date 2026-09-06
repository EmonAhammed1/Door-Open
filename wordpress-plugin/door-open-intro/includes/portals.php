<?php
/**
 * DRUMI portals: the thirteen journeys on the homepage, and where each one leads.
 *
 * Each portal moves through three stages, changed by hand under DRUMI Portals (waitlist sign-ups never move one):
 *   soon     Coming soon. Dark and closed, not a link.
 *   interest Open for interest. Lit, and leads to a page about the journey with its own waitlist.
 *   released Released. Stands ajar and leads to its product, which can be bought only inside one of the portal's
 *            ordering windows (Amsterdam time). Outside them the product page stays open with checkout closed.
 *
 * [drumi_portals] prints the portals as a turning ring: the active door stands in the middle, larger and glowing, with
 * its name and its own button under it; the ring loops, starting from the door chosen under DRUMI Portals.
 */

defined( 'ABSPATH' ) || exit;

define( 'DOI_PORTALS_OPTION', 'doi_portals' );
define( 'DOI_PORTALS_TZ', 'Europe/Amsterdam' );

function doi_portal_stages() {
	return array(
		'soon'     => __( 'Coming soon', 'door-open-intro' ),
		'interest' => __( 'Open for interest', 'door-open-intro' ),
		'released' => __( 'Released', 'door-open-intro' ),
	);
}

function doi_portals_defaults() {
	$journeys = array(
		array( 'Awakening', '#F1E9D9' ),
		array( 'Awareness', '#A6AD97' ),
		array( 'Dream Language', '#9BAFBA' ),
		array( 'Emotions', '#C49A9D' ),
		array( 'Creative Power', '#BD8069' ),
		array( 'Patterns', '#85866B' ),
		array( 'Relationships', '#A78391' ),
		array( 'Transformation', '#633F4B' ),
		array( 'Wisdom', '#A78761' ),
		array( 'Direction', '#42556A' ),
		array( 'Vision', '#426C70' ),
		array( 'The Unconscious', '#514356' ),
		array( 'Integration', '#6A5045' ),
	);
	$items = array();
	foreach ( $journeys as $i => $j ) {
		$items[] = array(
			'name'         => $j[0],
			'color'        => $j[1],
			'design'       => substr( 'abcd', $i % 4, 1 ),
			'stage'        => $i ? 'soon' : 'interest',
			'interest_url' => $i ? '' : home_url( '/demo/' ),
			'product_id'   => 0,
			'windows'      => $i ? array() : array( array( 'start' => '2027-03-08T00:00', 'end' => '' ) ),
			'button'       => '', // empty: the default button text below, with the journey's name
		);
	}
	return array(
		'labels'         => array(
			'soon'     => 'Coming Soon',
			'interest' => 'Explore',
			'released' => 'Enter',
		),
		'feature'        => 0,
		'feature_button' => 'Explore %s',
		'closed_text'    => 'Orders for this journal are closed right now.',
		'next_text'      => 'The next ordering window opens on %s (Amsterdam time).',
		'notify'         => '',
		'items'          => $items,
	);
}

function doi_portals() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$d     = doi_portals_defaults();
	$saved = get_option( DOI_PORTALS_OPTION );
	if ( ! is_array( $saved ) ) {
		return $cache = $d;
	}
	$s           = wp_parse_args( $saved, $d );
	$s['labels'] = wp_parse_args( (array) $s['labels'], $d['labels'] );
	$items       = array();
	foreach ( $d['items'] as $i => $def ) {
		$items[] = isset( $s['items'][ $i ] ) && is_array( $s['items'][ $i ] ) ? wp_parse_args( $s['items'][ $i ], $def ) : $def;
	}
	$s['items'] = $items;
	return $cache = $s;
}

/** Where a portal leads: its product once released (if one is chosen and published), otherwise its interest page. */
function doi_portal_url( $p ) {
	if ( 'soon' === $p['stage'] ) {
		return '';
	}
	if ( 'released' === $p['stage'] && $p['product_id'] && 'publish' === get_post_status( $p['product_id'] ) ) {
		return get_permalink( $p['product_id'] );
	}
	return (string) $p['interest_url'];
}

function doi_portal_is_light( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return false;
	}
	list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
	return ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b ) / 255 > 0.8;
}

/* Ordering windows ------------------------------------------------------------------------------------------- */

function doi_portal_tz() {
	return new DateTimeZone( DOI_PORTALS_TZ );
}

/** A window edge as typed in the settings ("2027-03-08T00:00", Amsterdam time) → timestamp, or null. */
function doi_portal_time( $local ) {
	$local = substr( trim( (string) $local ), 0, 16 );
	if ( '' === $local ) {
		return null;
	}
	$dt = DateTime::createFromFormat( '!Y-m-d\TH:i', $local, doi_portal_tz() );
	return $dt ? $dt->getTimestamp() : null;
}

function doi_portal_date( $ts ) {
	return wp_date( 'j F Y, H:i', $ts, doi_portal_tz() );
}

/** The window open at $now (if any) and the next one to open, each as array( start, end|null ). */
function doi_portal_window_state( $p, $now = null ) {
	$now  = null === $now ? time() : $now;
	$open = null;
	$next = null;
	foreach ( (array) $p['windows'] as $win ) {
		$start = doi_portal_time( isset( $win['start'] ) ? $win['start'] : '' );
		if ( null === $start ) {
			continue;
		}
		$end = doi_portal_time( isset( $win['end'] ) ? $win['end'] : '' );
		if ( $start <= $now && ( null === $end || $now < $end ) ) {
			$open = array( $start, $end );
		} elseif ( $start > $now && ( null === $next || $start < $next[0] ) ) {
			$next = array( $start, $end );
		}
	}
	return array( 'open' => $open, 'next' => $next );
}

function doi_portal_orders_open( $p, $now = null ) {
	if ( 'released' !== $p['stage'] ) {
		return false;
	}
	$state = doi_portal_window_state( $p, $now );
	return null !== $state['open'];
}

/* WooCommerce: a journal linked to a portal sells only while that portal is released and a window is open ------ */

function doi_portal_for_product( $product ) {
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return null;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( ! $id ) {
		return null;
	}
	foreach ( doi_portals()['items'] as $p ) {
		if ( (int) $p['product_id'] === (int) $id ) {
			return $p;
		}
	}
	return null;
}

add_filter( 'woocommerce_is_purchasable', 'doi_portal_purchasable', 20, 2 );
add_filter( 'woocommerce_variation_is_purchasable', 'doi_portal_purchasable', 20, 2 );
function doi_portal_purchasable( $purchasable, $product ) {
	$p = doi_portal_for_product( $product );
	return ( $p && ! doi_portal_orders_open( $p ) ) ? false : $purchasable;
}

add_action( 'woocommerce_single_product_summary', 'doi_portal_closed_notice', 31 );
function doi_portal_closed_notice() {
	global $product;
	$p = doi_portal_for_product( $product );
	if ( ! $p || doi_portal_orders_open( $p ) ) {
		return;
	}
	$cfg   = doi_portals();
	$state = doi_portal_window_state( $p );
	$text  = $cfg['closed_text'];
	if ( 'released' === $p['stage'] && $state['next'] && false !== strpos( $cfg['next_text'], '%s' ) ) {
		$text .= ' ' . sprintf( $cfg['next_text'], doi_portal_date( $state['next'][0] ) );
	}
	echo '<div class="doi-orders-closed"><p>' . esc_html( $text ) . '</p>';
	if ( '' !== trim( $cfg['notify'] ) ) {
		echo do_shortcode( $cfg['notify'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- a shortcode chosen by an admin
	}
	echo '</div>';
}

/* The row on the homepage --------------------------------------------------------------------------------------- */

function doi_portals_enqueue() {
	wp_enqueue_style( 'doi-portals', DOI_URL . 'assets/portals.css', array(), DOI_VERSION );
	wp_enqueue_script( 'doi-portals', DOI_URL . 'assets/portals.js', array(), DOI_VERSION, true );
}

/** A door's button text: its own, or the default, with %s replaced by its name. */
function doi_portal_button_text( $cfg, $p ) {
	$text = '' !== trim( (string) $p['button'] ) ? $p['button'] : $cfg['feature_button'];
	return false !== strpos( $text, '%s' ) ? sprintf( $text, $p['name'] ) : $text;
}

add_shortcode( 'drumi_portals', 'doi_portals_shortcode' );
function doi_portals_shortcode() {
	doi_portals_enqueue();
	$cfg   = doi_portals();
	$n     = count( $cfg['items'] );
	$start = isset( $cfg['items'][ $cfg['feature'] ] ) ? (int) $cfg['feature'] : 0;
	$html  = '<div class="doi-portals" data-start="' . $start . '" role="region" aria-roledescription="carousel" aria-label="' . esc_attr__( 'The DRUMI journeys', 'door-open-intro' ) . '">'
		. '<div class="doi-portals-track">';
	$first = null;
	foreach ( $cfg['items'] as $i => $p ) {
		$url = doi_portal_url( $p );
		// a portal that is open but has nowhere to lead yet stays closed rather than becoming a dead link
		$stage = '' === $url ? 'soon' : $p['stage'];
		$cta   = 'soon' === $stage ? $cfg['labels']['soon'] : doi_portal_button_text( $cfg, $p );
		// where the door stands when the page opens: 0 in the middle, then -1 / +1 beside it, and so on round the ring
		$pos   = ( ( $i - $start ) % $n + $n ) % $n;
		$pos   = $pos > $n / 2 ? $pos - $n : $pos;
		$tex   = DOI_URL . 'assets/door/portal-' . $p['design'] . '.webp';
		$class = 'doi-portal is-' . $stage . ( doi_portal_is_light( $p['color'] ) ? ' is-light' : '' ) . ( 0 === $pos ? ' is-active' : ' is-side' );
		$attrs = ' class="' . esc_attr( $class ) . '" data-portal="' . (int) $i . '" data-pos="' . (int) $pos . '"'
			. ' data-name="' . esc_attr( $p['name'] ) . '" data-cta="' . esc_attr( $cta ) . '" data-href="' . esc_url( $url ) . '"'
			. ' aria-label="' . esc_attr( $p['name'] . ' — ' . $cfg['labels'][ $stage ] ) . '"'
			. ' style="' . esc_attr( '--pc:' . $p['color'] . ';--doi-tex:url(' . $tex . ')' ) . '"';
		$inner = '<span class="doi-portal-door" aria-hidden="true"><span class="doi-portal-light"></span>'
			. '<span class="doi-portal-leaf is-left"></span><span class="doi-portal-leaf is-right"></span>'
			. '<span class="doi-portal-seam"></span>'
			. '<span class="doi-portal-tag">' . esc_html( $cfg['labels'][ $stage ] ) . '</span></span>';
		$html .= 'soon' === $stage
			? '<div' . $attrs . '>' . $inner . '</div>'
			: '<a' . $attrs . ' href="' . esc_url( $url ) . '">' . $inner . '</a>';
		if ( $i === $start ) {
			$first = array( $p['name'], $cta, $url );
		}
	}
	$html .= '</div>';

	// the active door's name and button; portals.js swaps them as the ring turns
	$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="%s"/></svg>';
	$html .= '<div class="doi-portals-caption" aria-live="polite">'
		. '<p class="doi-portals-name">' . esc_html( $first[0] ) . '</p>'
		. '<a class="doi-portals-cta"' . ( '' !== $first[2] ? ' href="' . esc_url( $first[2] ) . '"' : ' aria-disabled="true"' ) . '>'
		. '<span class="doi-portals-cta-text">' . esc_html( $first[1] ) . '</span>' . sprintf( $arrow, 'M5 12h14M13 6l6 6-6 6' ) . '</a>'
		. '</div>';

	$dots = '';
	foreach ( $cfg['items'] as $i => $p ) {
		$dots .= '<button type="button" class="doi-portals-dot" data-portal="' . (int) $i . '" aria-label="' . esc_attr( $p['name'] ) . '"' . ( $i === $start ? ' aria-current="true"' : '' ) . '></button>';
	}
	$html .= '<div class="doi-portals-nav">'
		. '<button type="button" class="doi-portals-arrow doi-portals-prev" aria-label="' . esc_attr__( 'Previous journey', 'door-open-intro' ) . '">' . sprintf( $arrow, 'M15 5l-7 7 7 7' ) . '</button>'
		. '<div class="doi-portals-dots">' . $dots . '</div>'
		. '<button type="button" class="doi-portals-arrow doi-portals-next" aria-label="' . esc_attr__( 'Next journey', 'door-open-intro' ) . '">' . sprintf( $arrow, 'M9 5l7 7-7 7' ) . '</button>'
		. '</div>';
	return $html . '</div>';
}

/* Admin: DRUMI Portals ----------------------------------------------------------------------------------------- */

add_action( 'admin_menu', 'doi_portals_menu' );
function doi_portals_menu() {
	add_menu_page( __( 'DRUMI Portals', 'door-open-intro' ), __( 'DRUMI Portals', 'door-open-intro' ), 'manage_options', 'drumi-portals', 'doi_portals_page', 'dashicons-admin-multisite', 58 );
}

add_action( 'admin_init', 'doi_portals_register' );
function doi_portals_register() {
	register_setting( 'doi_portals', DOI_PORTALS_OPTION, array( 'sanitize_callback' => 'doi_portals_sanitize' ) );
}

function doi_portals_sanitize( $in ) {
	$d = doi_portals_defaults();
	if ( ! is_array( $in ) ) {
		return doi_portals();
	}
	$text = function ( $key, $fallback ) use ( $in ) {
		return isset( $in[ $key ] ) && '' !== trim( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : $fallback;
	};
	$out = array(
		'labels'         => array(),
		'feature'        => isset( $in['feature'] ) ? max( 0, min( count( $d['items'] ) - 1, (int) $in['feature'] ) ) : 0,
		'feature_button' => $text( 'feature_button', $d['feature_button'] ),
		'closed_text'    => $text( 'closed_text', $d['closed_text'] ),
		'next_text'      => $text( 'next_text', $d['next_text'] ),
		'notify'         => isset( $in['notify'] ) ? sanitize_text_field( $in['notify'] ) : '',
		'items'          => array(),
	);
	foreach ( $d['labels'] as $k => $v ) {
		$out['labels'][ $k ] = isset( $in['labels'][ $k ] ) && '' !== trim( $in['labels'][ $k ] ) ? sanitize_text_field( $in['labels'][ $k ] ) : $v;
	}
	foreach ( $d['items'] as $i => $def ) {
		$r    = isset( $in['items'][ $i ] ) && is_array( $in['items'][ $i ] ) ? $in['items'][ $i ] : array();
		$name = isset( $r['name'] ) && '' !== trim( $r['name'] ) ? sanitize_text_field( $r['name'] ) : $def['name'];
		$pid  = isset( $r['product_id'] ) ? absint( $r['product_id'] ) : 0;
		$item = array(
			'name'         => $name,
			'color'        => ( isset( $r['color'] ) && sanitize_hex_color( $r['color'] ) ) ? strtoupper( sanitize_hex_color( $r['color'] ) ) : $def['color'],
			'design'       => ( isset( $r['design'] ) && in_array( $r['design'], array( 'a', 'b', 'c', 'd' ), true ) ) ? $r['design'] : $def['design'],
			'stage'        => ( isset( $r['stage'] ) && array_key_exists( $r['stage'], doi_portal_stages() ) ) ? $r['stage'] : 'soon',
			'interest_url' => isset( $r['interest_url'] ) ? esc_url_raw( trim( $r['interest_url'] ) ) : '',
			'product_id'   => ( $pid && 'product' === get_post_type( $pid ) ) ? $pid : 0,
			'windows'      => array(),
			'button'       => isset( $r['button'] ) ? sanitize_text_field( $r['button'] ) : '',
		);
		foreach ( isset( $r['windows'] ) && is_array( $r['windows'] ) ? $r['windows'] : array() as $win ) {
			$start = isset( $win['start'] ) ? substr( trim( $win['start'] ), 0, 16 ) : '';
			$end   = isset( $win['end'] ) ? substr( trim( $win['end'] ), 0, 16 ) : '';
			if ( '' === $start && '' === $end ) {
				continue;
			}
			$ts = doi_portal_time( $start );
			$te = '' === $end ? null : doi_portal_time( $end );
			if ( null === $ts || ( '' !== $end && null === $te ) ) {
				/* translators: %s: journey name */
				add_settings_error( DOI_PORTALS_OPTION, 'doi-window-' . $i, sprintf( __( 'An ordering window for %s needs an opening date and time, so it was left out.', 'door-open-intro' ), $name ) );
				continue;
			}
			if ( null !== $te && $te <= $ts ) {
				/* translators: %s: journey name */
				add_settings_error( DOI_PORTALS_OPTION, 'doi-window-order-' . $i, sprintf( __( 'An ordering window for %s closed before it opened, so it was left out.', 'door-open-intro' ), $name ) );
				continue;
			}
			$item['windows'][] = array( 'start' => $start, 'end' => $end );
		}
		usort(
			$item['windows'],
			function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);
		$out['items'][] = $item;
	}
	return $out;
}

/** One line under each portal in the admin saying what visitors get right now. */
function doi_portal_admin_status( $p ) {
	if ( 'soon' === $p['stage'] ) {
		return __( 'Shown dark with “Coming soon”. Not clickable.', 'door-open-intro' );
	}
	$url = doi_portal_url( $p );
	if ( '' === $url ) {
		return __( 'Shown as Coming soon until it has a page to lead to.', 'door-open-intro' );
	}
	if ( 'interest' === $p['stage'] ) {
		/* translators: %s: link */
		return sprintf( __( 'Lit and clickable. Leads to %s. Not for sale.', 'door-open-intro' ), $url );
	}
	$state = doi_portal_window_state( $p );
	if ( ! $p['product_id'] ) {
		$orders = __( 'No product chosen yet, so nothing can be bought.', 'door-open-intro' );
	} elseif ( $state['open'] ) {
		$orders = $state['open'][1]
			/* translators: %s: date */
			? sprintf( __( 'Orders are OPEN until %s.', 'door-open-intro' ), doi_portal_date( $state['open'][1] ) )
			: __( 'Orders are OPEN until you set a closing date.', 'door-open-intro' );
	} elseif ( $state['next'] ) {
		/* translators: %s: date */
		$orders = sprintf( __( 'Orders are closed. The next window opens %s.', 'door-open-intro' ), doi_portal_date( $state['next'][0] ) );
	} else {
		$orders = __( 'Orders are closed. No window is planned.', 'door-open-intro' );
	}
	/* translators: 1: link, 2: order status */
	return sprintf( __( 'Stands ajar and leads to %1$s. %2$s', 'door-open-intro' ), $url, $orders );
}

function doi_portals_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cfg      = doi_portals();
	$name     = DOI_PORTALS_OPTION;
	$stages   = doi_portal_stages();
	$products = post_type_exists( 'product' ) ? get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) ) : array();
	$pages    = get_pages( array( 'post_status' => 'publish' ) );
	$tex      = DOI_URL . 'assets/door/portal-';
	?>
	<div class="wrap doi-portals-admin">
		<h1><?php esc_html_e( 'DRUMI Portals', 'door-open-intro' ); ?></h1>
		<p class="doi-lead"><?php esc_html_e( 'The thirteen journeys on the homepage. Change a portal’s stage whenever you decide to: waitlist sign-ups never change it by themselves.', 'door-open-intro' ); ?></p>
		<ol class="doi-stage-help">
			<li><strong><?php echo esc_html( $stages['soon'] ); ?></strong> — <?php esc_html_e( 'dark and closed, nothing to click.', 'door-open-intro' ); ?></li>
			<li><strong><?php echo esc_html( $stages['interest'] ); ?></strong> — <?php esc_html_e( 'lit and clickable; leads to the journey’s own page with its own waitlist form. Nothing can be bought.', 'door-open-intro' ); ?></li>
			<li><strong><?php echo esc_html( $stages['released'] ); ?></strong> — <?php esc_html_e( 'stands ajar and leads to its product. The product can be bought only inside an ordering window below; outside them the page stays open with checkout closed.', 'door-open-intro' ); ?></li>
		</ol>
		<p class="description"><?php esc_html_e( 'All dates and times on this page are Amsterdam time (Europe/Amsterdam), including summer time.', 'door-open-intro' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'doi_portals' ); ?>
			<datalist id="doi-page-urls">
				<?php foreach ( $pages as $pg ) : ?>
					<option value="<?php echo esc_url( get_permalink( $pg ) ); ?>"><?php echo esc_html( get_the_title( $pg ) ); ?></option>
				<?php endforeach; ?>
			</datalist>

			<div class="doi-portal-grid">
			<?php foreach ( $cfg['items'] as $i => $p ) : $f = $name . '[items][' . $i . ']'; ?>
				<section class="doi-portal-card is-<?php echo esc_attr( $p['stage'] ); ?>" data-tex="<?php echo esc_url( $tex ); ?>">
					<header>
						<span class="doi-pa-door<?php echo 'soon' === $p['stage'] ? ' is-dim' : ''; ?>" style="<?php echo esc_attr( '--pc:' . $p['color'] . ';--tex:url(' . $tex . $p['design'] . '.webp)' ); ?>"></span>
						<div>
							<span class="doi-pa-no"><?php echo (int) ( $i + 1 ); ?></span>
							<input class="doi-pa-name" type="text" name="<?php echo esc_attr( $f ); ?>[name]" value="<?php echo esc_attr( $p['name'] ); ?>" aria-label="<?php esc_attr_e( 'Name', 'door-open-intro' ); ?>">
							<p class="doi-pa-status"><?php echo esc_html( doi_portal_admin_status( $p ) ); ?></p>
						</div>
					</header>

					<fieldset class="doi-pa-stage">
						<legend><?php esc_html_e( 'Stage', 'door-open-intro' ); ?></legend>
						<?php foreach ( $stages as $key => $label ) : ?>
							<label><input type="radio" name="<?php echo esc_attr( $f ); ?>[stage]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $p['stage'], $key ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</fieldset>

					<div class="doi-pa-row">
						<label><?php esc_html_e( 'Colour', 'door-open-intro' ); ?><br><input type="color" class="doi-pa-color" name="<?php echo esc_attr( $f ); ?>[color]" value="<?php echo esc_attr( strtolower( $p['color'] ) ); ?>"></label>
						<label><?php esc_html_e( 'Carving', 'door-open-intro' ); ?><br>
							<select class="doi-pa-design" name="<?php echo esc_attr( $f ); ?>[design]">
								<?php foreach ( array( 'a' => 'Flame', 'b' => 'Vine', 'c' => 'Sunrise', 'd' => 'Arches' ) as $k => $label ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $p['design'], $k ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>

					<label class="doi-pa-field"><?php esc_html_e( 'Page for “Open for interest” (about the journey + its waitlist)', 'door-open-intro' ); ?>
						<input type="text" list="doi-page-urls" name="<?php echo esc_attr( $f ); ?>[interest_url]" value="<?php echo esc_attr( $p['interest_url'] ); ?>" placeholder="https://…">
					</label>

					<label class="doi-pa-field"><?php esc_html_e( 'Product for “Released”', 'door-open-intro' ); ?>
						<select name="<?php echo esc_attr( $f ); ?>[product_id]">
							<option value="0"><?php echo $products ? esc_html__( '— None yet —', 'door-open-intro' ) : esc_html__( '— No products in the shop yet —', 'door-open-intro' ); ?></option>
							<?php foreach ( $products as $prod ) : ?>
								<option value="<?php echo (int) $prod->ID; ?>" <?php selected( (int) $p['product_id'], $prod->ID ); ?>><?php echo esc_html( get_the_title( $prod ) . ( 'publish' !== $prod->post_status ? ' (' . $prod->post_status . ')' : '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<div class="doi-pa-field">
						<?php esc_html_e( 'Ordering windows (Amsterdam time). Leave “Closes” empty while the date is not decided.', 'door-open-intro' ); ?>
						<table class="doi-pa-windows" data-name="<?php echo esc_attr( $f ); ?>[windows]">
							<thead><tr><th><?php esc_html_e( 'Opens', 'door-open-intro' ); ?></th><th><?php esc_html_e( 'Closes', 'door-open-intro' ); ?></th><th></th></tr></thead>
							<tbody>
							<?php foreach ( $p['windows'] as $w => $win ) : ?>
								<tr>
									<td><input type="datetime-local" step="60" name="<?php echo esc_attr( $f . '[windows][' . $w . '][start]' ); ?>" value="<?php echo esc_attr( $win['start'] ); ?>"></td>
									<td><input type="datetime-local" step="60" name="<?php echo esc_attr( $f . '[windows][' . $w . '][end]' ); ?>" value="<?php echo esc_attr( $win['end'] ); ?>"></td>
									<td><button type="button" class="button-link doi-pa-remove"><?php esc_html_e( 'Remove', 'door-open-intro' ); ?></button></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<button type="button" class="button doi-pa-add"><?php esc_html_e( 'Add an ordering window', 'door-open-intro' ); ?></button>
					</div>

					<label class="doi-pa-field"><?php esc_html_e( 'Button text under this door when it is in the middle', 'door-open-intro' ); ?>
						<input type="text" name="<?php echo esc_attr( $f ); ?>[button]" value="<?php echo esc_attr( $p['button'] ); ?>" placeholder="<?php echo esc_attr( doi_portal_button_text( $cfg, array_merge( $p, array( 'button' => '' ) ) ) ); ?>">
						<span class="description"><?php esc_html_e( 'Leave empty for the default. The button leads where the door leads; while the door is “Coming soon” it shows “Coming Soon” and can’t be clicked.', 'door-open-intro' ); ?></span>
					</label>
				</section>
			<?php endforeach; ?>
			</div>

			<h2><?php esc_html_e( 'Wording', 'door-open-intro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Door in the middle when the page opens', 'door-open-intro' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $name ); ?>[feature]">
							<?php foreach ( $cfg['items'] as $i => $p ) : ?>
								<option value="<?php echo (int) $i; ?>" <?php selected( (int) $cfg['feature'], $i ); ?>><?php echo esc_html( $p['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'The ring starts here and continues in the order above, round and round.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-fb"><?php esc_html_e( 'Default button text', 'door-open-intro' ); ?></label></th>
					<td><input id="doi-fb" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>[feature_button]" value="<?php echo esc_attr( $cfg['feature_button'] ); ?>"><p class="description"><?php esc_html_e( '%s is replaced by the portal’s name.', 'door-open-intro' ); ?></p></td>
				</tr>
				<?php foreach ( $stages as $key => $label ) : ?>
				<tr>
					<th scope="row"><label for="doi-l-<?php echo esc_attr( $key ); ?>"><?php /* translators: %s: stage */ printf( esc_html__( 'Label under a “%s” portal', 'door-open-intro' ), esc_html( $label ) ); ?></label></th>
					<td><input id="doi-l-<?php echo esc_attr( $key ); ?>" class="regular-text" type="text" name="<?php echo esc_attr( $name . '[labels][' . $key . ']' ); ?>" value="<?php echo esc_attr( $cfg['labels'][ $key ] ); ?>"></td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="doi-ct"><?php esc_html_e( 'On a product page while orders are closed', 'door-open-intro' ); ?></label></th>
					<td>
						<input id="doi-ct" class="large-text" type="text" name="<?php echo esc_attr( $name ); ?>[closed_text]" value="<?php echo esc_attr( $cfg['closed_text'] ); ?>">
						<input class="large-text" type="text" name="<?php echo esc_attr( $name ); ?>[next_text]" value="<?php echo esc_attr( $cfg['next_text'] ); ?>" aria-label="<?php esc_attr_e( 'Next window sentence', 'door-open-intro' ); ?>">
						<p class="description"><?php esc_html_e( 'The second sentence is added when a next window is planned; %s is replaced by its date.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-nf"><?php esc_html_e( 'Sign-up form while orders are closed', 'door-open-intro' ); ?></label></th>
					<td><input id="doi-nf" class="large-text code" type="text" name="<?php echo esc_attr( $name ); ?>[notify]" value="<?php echo esc_attr( $cfg['notify'] ); ?>" placeholder='[elementor-template id="123"]'><p class="description"><?php esc_html_e( 'A shortcode for a form (for example an Elementor form saved as a template) so visitors can hear when orders reopen.', 'door-open-intro' ); ?></p></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>

	<style>
	.doi-portals-admin .doi-lead{font-size:14px;max-width:760px}
	.doi-stage-help{max-width:820px;margin-left:1.4em}
	.doi-portal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;margin-top:18px}
	.doi-portal-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px 18px;display:flex;flex-direction:column;gap:12px}
	.doi-portal-card.is-interest{border-color:#c9a86a;box-shadow:0 0 0 1px #c9a86a}
	.doi-portal-card.is-released{border-color:#8a6d45;box-shadow:0 0 0 2px #8a6d45}
	.doi-portal-card header{display:flex;gap:14px;align-items:flex-start}
	.doi-pa-door{flex:0 0 auto;width:46px;aspect-ratio:370/772;background-color:var(--pc);background-image:var(--tex);background-size:100% 100%;background-blend-mode:overlay;-webkit-mask:var(--tex) 0 0/100% 100% no-repeat;mask:var(--tex) 0 0/100% 100% no-repeat}
	.doi-pa-door.is-dim{filter:brightness(.55) saturate(.75)}
	.doi-pa-no{display:inline-block;min-width:22px;color:#8a6d45;font-weight:600}
	.doi-pa-name{font-size:16px;font-weight:600;width:calc(100% - 30px)}
	.doi-pa-status{margin:6px 0 0;color:#50575e;font-size:12px;line-height:1.5;word-break:break-word}
	.doi-pa-stage{display:flex;flex-wrap:wrap;gap:6px 16px}
	.doi-pa-stage legend{font-weight:600;margin-bottom:4px}
	.doi-pa-row{display:flex;gap:18px}
	.doi-pa-field{display:block}
	.doi-pa-field input[type=text],.doi-pa-field select,.doi-pa-field textarea{width:100%;margin-top:4px}
	.doi-pa-field .description{display:block;margin-top:4px;font-size:12px}
	.doi-pa-windows{width:100%;table-layout:fixed;border-collapse:collapse;margin:6px 0}
	.doi-pa-windows th:last-child,.doi-pa-windows td:last-child{width:58px}
	.doi-pa-windows th{text-align:left;font-weight:400;color:#50575e;padding:0 4px 2px 0}
	.doi-pa-windows td{padding:2px 4px 2px 0}
	.doi-pa-windows input{width:100%;min-width:0;box-sizing:border-box;padding:0 4px}
	.doi-pa-windows:not(:has(tbody tr)) thead{display:none}
	</style>
	<script>
	(function () {
		document.querySelectorAll('.doi-portal-card').forEach(function (card) {
			var door = card.querySelector('.doi-pa-door');
			var tex = card.getAttribute('data-tex');
			card.querySelector('.doi-pa-color').addEventListener('input', function () { door.style.setProperty('--pc', this.value); });
			card.querySelector('.doi-pa-design').addEventListener('change', function () { door.style.setProperty('--tex', 'url(' + tex + this.value + '.webp)'); });
			card.querySelectorAll('.doi-pa-stage input').forEach(function (r) {
				r.addEventListener('change', function () {
					card.className = 'doi-portal-card is-' + this.value;
					door.classList.toggle('is-dim', this.value === 'soon');
				});
			});
			var table = card.querySelector('.doi-pa-windows');
			var body = table.querySelector('tbody');
			var base = table.getAttribute('data-name');
			var n = body.children.length;
			card.querySelector('.doi-pa-add').addEventListener('click', function () {
				var k = n++;
				var tr = document.createElement('tr');
				tr.innerHTML = '<td><input type="datetime-local" step="60"></td><td><input type="datetime-local" step="60"></td><td><button type="button" class="button-link doi-pa-remove"><?php echo esc_js( __( 'Remove', 'door-open-intro' ) ); ?></button></td>';
				tr.querySelectorAll('input')[0].name = base + '[' + k + '][start]';
				tr.querySelectorAll('input')[1].name = base + '[' + k + '][end]';
				body.appendChild(tr);
				tr.querySelector('input').focus();
			});
			body.addEventListener('click', function (e) {
				if (e.target.classList.contains('doi-pa-remove')) e.target.closest('tr').remove();
			});
		});
	})();
	</script>
	<?php
}
