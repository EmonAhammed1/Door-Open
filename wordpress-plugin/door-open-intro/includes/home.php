<?php
/**
 * The portal finder for the homepage's "Which theme speaks to you?" section (website 2.0).
 *
 * The homepage sections themselves are built only from Elementor containers and widgets, with their layout, type,
 * colours and image fades set in Elementor, so everything about them can be changed there; see
 * scripts/build-home-website2.php in the repository for how they were first laid out.
 *
 * [drumi_portal_finder] prints the finder: a few questions in a dialog that suggest a matching theme (portal), say why
 * it resonates and offer the next step: the journal for Awakening, or for a future portal its waitlist with that portal
 * chosen. Any link to #find-your-portal opens it.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The finder's questions. Each answer adds points to portals, by their position under DRUMI Portals
 * (0 Awakening, 1 Awareness, 2 Dream Language, 3 Emotions, 4 Creative Power, 5 Patterns, 6 Relationships,
 * 7 Transformation, 8 Wisdom, 9 Direction, 10 Vision, 11 The Unconscious, 12 Integration).
 */
function doi_finder_questions() {
	$q = array(
		array(
			'q' => 'What is on your mind right now?',
			'a' => array(
				array( 'The same situations keep coming back.', array( 5 => 3, 11 => 1 ) ),
				array( 'My feelings are hard to place.', array( 3 => 3, 1 => 1 ) ),
				array( 'I’m not sure which way to go.', array( 9 => 3, 10 => 1 ) ),
				array( 'My dreams stay with me, and I wonder why.', array( 2 => 3, 11 => 1 ) ),
				array( 'I feel ready to come back to myself.', array( 0 => 3, 7 => 1 ) ),
			),
		),
		array(
			'q' => 'Where do you feel it most?',
			'a' => array(
				array( 'In my relationships.', array( 6 => 3, 3 => 1 ) ),
				array( 'In the choices I make.', array( 12 => 2, 9 => 1, 8 => 1 ) ),
				array( 'In my ideas and creativity.', array( 4 => 3, 10 => 1 ) ),
				array( 'Deep inside, in who I am becoming.', array( 7 => 2, 0 => 1, 11 => 1 ) ),
			),
		),
		array(
			'q' => 'What would help you most right now?',
			'a' => array(
				array( 'To understand myself better.', array( 1 => 2, 2 => 1, 0 => 1 ) ),
				array( 'To trust my own voice.', array( 8 => 3, 0 => 1 ) ),
				array( 'To let go of what no longer fits.', array( 7 => 2, 5 => 1 ) ),
				array( 'To turn insight into action.', array( 12 => 3, 4 => 1 ) ),
				array( 'To see where I want to go.', array( 10 => 3, 9 => 1 ) ),
			),
		),
	);
	return apply_filters( 'doi_finder_questions', $q );
}

/** Why each portal resonates, shown with the result. Same order as the portals. */
function doi_finder_reasons() {
	return apply_filters(
		'doi_finder_reasons',
		array(
			'It is the first door: a gentle beginning that brings you closer to who you are, beneath everything the world asks you to be.',
			'It helps you slow down and notice what is unfolding within you, and what is asking for your attention.',
			'It invites you to explore the images and symbols of your dreams, and what they are trying to tell you.',
			'It makes room for what you feel, so you can listen to what your emotions need.',
			'It reconnects you with your imagination and gives your ideas room to become real.',
			'It helps you recognise what keeps repeating, and opens the possibility of a different choice.',
			'It explores what you need in connection, without losing yourself along the way.',
			'It supports you in becoming who you are, and in leaving behind what no longer fits.',
			'It helps you hear your own voice more clearly and trust the wisdom within.',
			'It helps you find your next step, guided by what feels meaningful to you.',
			'It invites you to explore the life you long for, and what it could look like for you.',
			'It lets you get curious about what lies beneath the surface, and how it shapes your life.',
			'It helps you bring what you discover into the way you choose, act and live.',
		)
	);
}

add_shortcode( 'drumi_portal_finder', 'doi_finder_shortcode' );
function doi_finder_shortcode() {
	static $printed = false;
	if ( $printed ) {
		return '';
	}
	$printed = true;

	$reasons = doi_finder_reasons();
	$cfg     = doi_portals();
	$portals = array();
	$first   = null; // the journal that can be explored now (Awakening): where a future result also points
	foreach ( $cfg['items'] as $i => $p ) {
		$url   = doi_portal_url( $p );
		$stage = '' === $url ? 'soon' : $p['stage'];
		if ( null === $first && '' !== $url ) {
			$first = array(
				'name' => $p['name'],
				'url'  => $url,
			);
		}
		$portals[] = array(
			'name'   => $p['name'],
			'desc'   => $p['desc'],
			'color'  => $p['color'],
			'url'    => $url,
			'label'  => doi_portal_label( $cfg, $p, $stage ),
			'later'  => 'soon' === $stage, // a future journal: the result offers its waitlist, never a purchase
			'reason' => isset( $reasons[ $i ] ) ? $reasons[ $i ] : '',
		);
	}
	$config = array(
		'questions' => doi_finder_questions(),
		'portals'   => $portals,
		'first'     => $first,
		'text'      => array(
			'step'      => __( 'Question %1$s of %2$s', 'door-open-intro' ),
			'back'      => __( 'Back', 'door-open-intro' ),
			'kicker'    => __( 'The theme that speaks to you', 'door-open-intro' ),
			'shared'    => __( 'What you shared', 'door-open-intro' ),
			'why'       => __( 'Why it resonates', 'door-open-intro' ),
			'journal'   => __( 'Explore the journal', 'door-open-intro' ),
			'join'      => $cfg['waitlist_button'],
			'begin'     => __( 'Begin with %s', 'door-open-intro' ),
			'again'     => __( 'Start again', 'door-open-intro' ),
		),
		'journal'   => '#awakening-journal',
		'waitlist'  => '' !== $cfg['waitlist_url'] ? $cfg['waitlist_url'] : '#waitlist',
	);

	wp_enqueue_style( 'doi-finder', DOI_URL . 'assets/finder.css', array(), DOI_VERSION );
	wp_enqueue_script( 'doi-finder', DOI_URL . 'assets/finder.js', array(), DOI_VERSION, true );
	wp_add_inline_script( 'doi-finder', 'window.DOI_FINDER = ' . wp_json_encode( $config ) . ';', 'before' );

	return '<dialog class="doi-finder-dialog" id="find-your-portal" aria-labelledby="doi-finder-title">'
		. '<button type="button" class="doi-finder-close" aria-label="' . esc_attr__( 'Close', 'door-open-intro' ) . '">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>'
		. '<div class="doi-finder-body" aria-live="polite"></div>'
		. '</dialog>';
}
