<?php
/**
 * Settings → Door Open Intro.
 *
 * "Page behind the door" is WordPress's own homepage setting (Settings → Reading → Your homepage displays),
 * edited from here: the door plays over whatever the homepage is, so choosing the page and choosing the
 * homepage are the same thing. Saving here updates Reading, and changing Reading shows up here.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'doi_admin_menu' );
function doi_admin_menu() {
	add_options_page(
		__( 'Door Open Intro', 'door-open-intro' ),
		__( 'Door Open Intro', 'door-open-intro' ),
		'manage_options',
		'door-open-intro',
		'doi_render_settings_page'
	);
}

add_action( 'admin_init', 'doi_register_settings' );
function doi_register_settings() {
	register_setting(
		'doi_settings_group',
		DOI_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'doi_sanitize',
			'default'           => doi_defaults(),
		)
	);
}

function doi_sanitize( $in ) {
	$d  = doi_defaults();
	$in = is_array( $in ) ? $in : array();

	// Not stored in our option: it lives in WordPress's Reading settings (see file header).
	if ( isset( $in['front_page'] ) ) {
		doi_set_front_page( absint( $in['front_page'] ) );
	}

	$button = isset( $in['button'] ) ? sanitize_text_field( $in['button'] ) : '';

	return array(
		'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
		'where'    => ( isset( $in['where'] ) && 'all' === $in['where'] ) ? 'all' : 'home',
		'once'     => empty( $in['once'] ) ? 0 : 1,
		'once_all' => empty( $in['once_all'] ) ? 0 : 1,
		'mobile'   => empty( $in['mobile'] ) ? 0 : 1,
		'skip'     => empty( $in['skip'] ) ? 0 : 1,
		'wordmark' => isset( $in['wordmark'] ) ? sanitize_text_field( $in['wordmark'] ) : $d['wordmark'],
		'tagline'  => isset( $in['tagline'] ) ? sanitize_textarea_field( $in['tagline'] ) : $d['tagline'],
		'button'   => '' !== $button ? $button : $d['button'],
		'caption'  => isset( $in['caption'] ) ? sanitize_text_field( $in['caption'] ) : $d['caption'],
		'button_bg' => ( isset( $in['button_bg'] ) && sanitize_hex_color( $in['button_bg'] ) ) ? sanitize_hex_color( $in['button_bg'] ) : $d['button_bg'],
		'caption_color'        => ( isset( $in['caption_color'] ) && sanitize_hex_color( $in['caption_color'] ) ) ? sanitize_hex_color( $in['caption_color'] ) : $d['caption_color'],
		'caption_size_desktop' => doi_sanitize_px( isset( $in['caption_size_desktop'] ) ? $in['caption_size_desktop'] : '', $d['caption_size_desktop'] ),
		'caption_size_tablet'  => doi_sanitize_px( isset( $in['caption_size_tablet'] ) ? $in['caption_size_tablet'] : '', $d['caption_size_tablet'] ),
		'caption_size_mobile'  => doi_sanitize_px( isset( $in['caption_size_mobile'] ) ? $in['caption_size_mobile'] : '', $d['caption_size_mobile'] ),
		'hero'       => empty( $in['hero'] ) ? 0 : 1,
		'hero_title' => isset( $in['hero_title'] ) ? sanitize_text_field( $in['hero_title'] ) : $d['hero_title'],
		'hero_sub'   => isset( $in['hero_sub'] ) ? sanitize_text_field( $in['hero_sub'] ) : $d['hero_sub'],
		'hero_desktop' => doi_sanitize_hero_video( isset( $in['hero_desktop'] ) ? $in['hero_desktop'] : '', 'desktop' ),
		'hero_mobile'  => doi_sanitize_hero_video( isset( $in['hero_mobile'] ) ? $in['hero_mobile'] : '', 'mobile' ),
	);
}

/** A text size in px, kept between 10 and 80; empty or 0 falls back to the default. */
function doi_sanitize_px( $value, $fallback ) {
	$px = absint( $value );
	return $px ? min( 80, max( 10, $px ) ) : $fallback;
}

/**
 * @param int $page_id A published page, or 0 for "your latest posts".
 */
function doi_set_front_page( $page_id ) {
	if ( 0 === $page_id ) {
		update_option( 'show_on_front', 'posts' );
		return;
	}
	if ( 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
		add_settings_error( DOI_OPTION, 'doi_bad_page', __( 'That page isn\'t published, so it can\'t be shown behind the door. Publish it first, then choose it again.', 'door-open-intro' ) );
		return;
	}
	if ( (int) get_option( 'page_for_posts' ) === $page_id ) {
		add_settings_error( DOI_OPTION, 'doi_posts_page', __( 'That page is your blog\'s Posts page, so it can\'t also be the page behind the door. Choose another page, or change the Posts page under Settings → Reading.', 'door-open-intro' ) );
		return;
	}
	update_option( 'page_on_front', $page_id );
	update_option( 'show_on_front', 'page' );
}

function doi_current_front_page() {
	return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
}

/**
 * Right after installing, the homepage is usually still "your latest posts". Point that out where it's
 * relevant, until a page has been chosen.
 */
add_action( 'admin_notices', 'doi_setup_notice' );
function doi_setup_notice() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'dashboard' ), true ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( doi_current_front_page() ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'Door Open Intro: your homepage currently shows your latest posts, so that is what visitors will walk into.', 'door-open-intro' ),
		esc_url( admin_url( 'options-general.php?page=door-open-intro' ) ),
		esc_html__( 'Choose the page behind the door', 'door-open-intro' )
	);
}

function doi_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s       = doi_settings();
	$name    = DOI_OPTION;
	$front   = doi_current_front_page();
	$preview = add_query_arg( 'doi_preview', '1', home_url( '/' ) );
	?>
	<div class="wrap doi-admin">
		<style>
			.doi-admin .doi-hero{display:flex;gap:20px;align-items:center;margin:14px 0 6px;max-width:880px}
			.doi-admin .doi-hero img{width:220px;height:auto;border-radius:6px;box-shadow:0 4px 18px rgba(0,0,0,.12);flex:none}
			.doi-admin .doi-hero p{margin:0 0 8px;max-width:56ch;color:#50575e}
			.doi-admin .form-table th{width:220px}
			.doi-admin textarea{width:100%;max-width:420px}
			.doi-admin .doi-sizes label{display:inline-block;margin:0 22px 6px 0}
			@media (max-width:640px){.doi-admin .doi-hero{flex-direction:column;align-items:flex-start}}
			.doi-admin .doi-video{display:flex;gap:16px;align-items:flex-start;max-width:680px}
			.doi-admin .doi-video-thumb{width:160px;height:auto;border-radius:4px;box-shadow:0 2px 10px rgba(0,0,0,.12);flex:none;background:#efe6d8}
			.doi-admin .doi-video[data-set=mobile] .doi-video-thumb{width:72px}
			.doi-admin .doi-video-body p{margin:0 0 8px}
			.doi-admin .doi-video-name{font-weight:600}
			.doi-admin .doi-video-progress{width:260px;display:block;margin:0 0 8px}
			.doi-admin .doi-video-status.is-error{color:#b32d2e}
			.doi-admin .doi-video-status.is-ready{color:#00a32a}
		</style>

		<h1><?php esc_html_e( 'Door Open Intro', 'door-open-intro' ); ?></h1>

		<div class="doi-hero">
			<img src="<?php echo esc_url( doi_image_url( 'desktop-lit.jpg' ) ); ?>" alt="">
			<div>
				<p><?php esc_html_e( 'Visitors arrive at the closed door. When they press the button, its engraving lights up from the centre, the doors swing open and they walk through into the page you choose below, which has already loaded behind the door.', 'door-open-intro' ); ?></p>
				<a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview on your site ↗', 'door-open-intro' ); ?></a>
			</div>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'doi_settings_group' ); ?>

			<h2><?php esc_html_e( 'Page behind the door', 'door-open-intro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="doi-front-page"><?php esc_html_e( 'Visitors walk into', 'door-open-intro' ); ?></label></th>
					<td>
						<?php
						$dropdown = wp_dropdown_pages(
							array(
								'name'              => $name . '[front_page]',
								'id'                => 'doi-front-page',
								'selected'          => (int) $front,
								'show_option_none'  => esc_html__( '— Your latest posts —', 'door-open-intro' ),
								'option_none_value' => '0',
								'echo'              => 0,
							)
						);
						if ( $dropdown ) {
							echo $dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated markup.
						} else {
							echo '<p>' . esc_html__( 'You have no published pages yet. Publish the page visitors should walk into, then choose it here.', 'door-open-intro' ) . '</p>';
						}
						?>
						<p class="description"><?php esc_html_e( 'This is your homepage; the door opens onto it. It is the same setting as Settings → Reading → "Your homepage displays", so changing it here updates it there too.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'When the door appears', 'door-open-intro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Door intro', 'door-open-intro' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> <?php esc_html_e( 'Show the door to visitors', 'door-open-intro' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Where', 'door-open-intro' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[where]" value="home" <?php checked( $s['where'], 'home' ); ?>> <?php esc_html_e( 'On the homepage', 'door-open-intro' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[where]" value="all" <?php checked( $s['where'], 'all' ); ?>> <?php esc_html_e( 'On every page (the door opens onto whichever page they arrived at)', 'door-open-intro' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Repeat visits', 'door-open-intro' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[once]" value="1" <?php checked( $s['once'], 1 ); ?>> <?php esc_html_e( 'Only once per visit in the same tab (a new tab shows it again)', 'door-open-intro' ); ?></label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[once_all]" value="1" <?php checked( $s['once_all'], 1 ); ?>> <?php esc_html_e( 'Only once until the visitor closes their browser, across all tabs', 'door-open-intro' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Phones', 'door-open-intro' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[mobile]" value="1" <?php checked( $s['mobile'], 1 ); ?>> <?php esc_html_e( 'Show on phones too', 'door-open-intro' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Skip', 'door-open-intro' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[skip]" value="1" <?php checked( $s['skip'], 1 ); ?>> <?php esc_html_e( 'Show a small "Skip intro" link', 'door-open-intro' ); ?></label>
						<p class="description"><?php esc_html_e( 'Pressing Esc always skips.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Text on the door', 'door-open-intro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="doi-wordmark"><?php esc_html_e( 'Name', 'door-open-intro' ); ?></label></th>
					<td><input type="text" class="regular-text" id="doi-wordmark" name="<?php echo esc_attr( $name ); ?>[wordmark]" value="<?php echo esc_attr( $s['wordmark'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-tagline"><?php esc_html_e( 'Tagline', 'door-open-intro' ); ?></label></th>
					<td>
						<textarea id="doi-tagline" rows="2" name="<?php echo esc_attr( $name ); ?>[tagline]"><?php echo esc_textarea( $s['tagline'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One line per row. Leave empty for no tagline.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-button"><?php esc_html_e( 'Button', 'door-open-intro' ); ?></label></th>
					<td><input type="text" class="regular-text" id="doi-button" name="<?php echo esc_attr( $name ); ?>[button]" value="<?php echo esc_attr( $s['button'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-caption"><?php esc_html_e( 'Under the button', 'door-open-intro' ); ?></label></th>
					<td><input type="text" class="regular-text" id="doi-caption" name="<?php echo esc_attr( $name ); ?>[caption]" value="<?php echo esc_attr( $s['caption'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-caption-color"><?php esc_html_e( 'Colour of the line under the button', 'door-open-intro' ); ?></label></th>
					<td><input type="color" id="doi-caption-color" name="<?php echo esc_attr( $name ); ?>[caption_color]" value="<?php echo esc_attr( $s['caption_color'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Size of the line under the button', 'door-open-intro' ); ?></th>
					<td>
						<fieldset class="doi-sizes">
							<?php
							$sizes = array(
								'desktop' => __( 'Computers', 'door-open-intro' ),
								'tablet'  => __( 'Tablets', 'door-open-intro' ),
								'mobile'  => __( 'Phones', 'door-open-intro' ),
							);
							foreach ( $sizes as $device => $label ) :
								?>
							<label><?php echo esc_html( $label ); ?><br><input type="number" class="small-text" min="10" max="80" step="1" name="<?php echo esc_attr( $name ); ?>[caption_size_<?php echo esc_attr( $device ); ?>]" value="<?php echo esc_attr( $s[ 'caption_size_' . $device ] ); ?>"> px</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Text size in pixels on each kind of screen: phones up to 767px wide, tablets up to 1024px, computers above that.', 'door-open-intro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-button-bg"><?php esc_html_e( 'Button colour', 'door-open-intro' ); ?></label></th>
					<td><input type="color" id="doi-button-bg" name="<?php echo esc_attr( $name ); ?>[button_bg]" value="<?php echo esc_attr( $s['button_bg'] ); ?>"></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Homepage corridor', 'door-open-intro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Corridor', 'door-open-intro' ); ?></th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hero]" value="1" <?php checked( $s['hero'], 1 ); ?>> <?php esc_html_e( 'At the top of the homepage, a corridor visitors walk down as they scroll, around the corner to the sea', 'door-open-intro' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-hero-title"><?php esc_html_e( 'Heading after it', 'door-open-intro' ); ?></label></th>
					<td><input type="text" class="regular-text" id="doi-hero-title" name="<?php echo esc_attr( $name ); ?>[hero_title]" value="<?php echo esc_attr( $s['hero_title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="doi-hero-sub"><?php esc_html_e( 'Line under the heading', 'door-open-intro' ); ?></label></th>
					<td><input type="text" class="regular-text" id="doi-hero-sub" name="<?php echo esc_attr( $name ); ?>[hero_sub]" value="<?php echo esc_attr( $s['hero_sub'] ); ?>"></td>
				</tr>
				<?php
				$hero_sets = doi_hero_sets();
				$hero_rows = array(
					'desktop' => array( __( 'Video for computers', 'door-open-intro' ), __( 'Landscape (16:9), shown on computers and landscape tablets.', 'door-open-intro' ), 1600 ),
					'mobile'  => array( __( 'Video for phones', 'door-open-intro' ), __( 'Portrait (9:16), shown on phones and upright tablets.', 'door-open-intro' ), 1280 ),
				);
				foreach ( $hero_rows as $set => $row ) :
					$chosen = $s[ 'hero_' . $set ];
					$custom = is_array( $chosen ) && ! empty( $chosen['folder'] );
					$file   = $custom && ! empty( $chosen['video'] ) ? get_attached_file( $chosen['video'] ) : '';
					?>
				<tr>
					<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
					<td>
						<div class="doi-video" data-set="<?php echo esc_attr( $set ); ?>" data-cap="<?php echo esc_attr( $row[2] ); ?>" data-original-thumb="<?php echo esc_url( DOI_URL . 'assets/hero/' . $set . '/001.webp' ); ?>">
							<img class="doi-video-thumb" src="<?php echo esc_url( doi_hero_poster( $hero_sets[ $set ] ) ); ?>" alt="">
							<div class="doi-video-body">
								<p class="doi-video-name"><?php echo esc_html( $custom ? ( $file ? wp_basename( $file ) : __( 'Your video', 'door-open-intro' ) ) : __( 'The original corridor', 'door-open-intro' ) ); ?></p>
								<p>
									<button type="button" class="button doi-video-pick"><?php esc_html_e( 'Choose video…', 'door-open-intro' ); ?></button>
									<button type="button" class="button-link doi-video-reset"<?php echo $custom ? '' : ' hidden'; ?>><?php esc_html_e( 'Use the original corridor', 'door-open-intro' ); ?></button>
								</p>
								<p class="doi-video-status" aria-live="polite"></p>
								<progress class="doi-video-progress" max="100" value="0" hidden></progress>
								<p class="description"><?php echo esc_html( $row[1] ); ?></p>
							</div>
							<input type="hidden" class="doi-video-value" name="<?php echo esc_attr( $name ); ?>[hero_<?php echo esc_attr( $set ); ?>]" value="<?php echo esc_attr( $custom ? wp_json_encode( $chosen ) : '' ); ?>">
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"></th>
					<td><p class="description"><?php esc_html_e( 'A chosen video is turned into still frames right here in your browser, which takes up to a minute: keep this page open and in front until it says it is ready, then save. Short videos (8 to 15 seconds) walk best.', 'door-open-intro' ); ?></p></td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// ------------------------------------------------------------------------------------------------------------
// Homepage corridor videos. The server can't convert video, so the settings page does it in the browser
// (assets/admin-hero.js): it draws the chosen video frame by frame onto a canvas and sends the stills here.
// ------------------------------------------------------------------------------------------------------------

const DOI_HERO_FOLDER = '/^(desktop|mobile)-\d+-[a-z0-9]{6}$/';

/**
 * A prepared video as saved in the settings, or '' for the original corridor. Only accepted when every
 * frame it names is actually on the server.
 */
function doi_sanitize_hero_video( $raw, $set ) {
	if ( is_array( $raw ) ) {
		$v = $raw;
	} else {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		$v = json_decode( $raw, true );
	}
	if ( ! is_array( $v ) || empty( $v['folder'] ) || ! preg_match( DOI_HERO_FOLDER, $v['folder'] ) || 0 !== strpos( $v['folder'], $set . '-' ) ) {
		return '';
	}
	$ext   = ( isset( $v['ext'] ) && 'jpg' === $v['ext'] ) ? 'jpg' : 'webp';
	$count = isset( $v['count'] ) ? absint( $v['count'] ) : 0;
	$root  = doi_hero_upload_root();
	if ( $count < 10 || $count > 400 ) {
		return '';
	}
	for ( $i = 1; $i <= $count; $i++ ) {
		if ( ! file_exists( $root['dir'] . '/' . $v['folder'] . '/' . sprintf( '%03d.%s', $i, $ext ) ) ) {
			add_settings_error( DOI_OPTION, 'doi_hero_incomplete', __( 'A video wasn\'t fully prepared, so the corridor keeps its previous video. Choose it again and wait until it says it is ready before saving.', 'door-open-intro' ) );
			$old = doi_settings();
			return $old[ 'hero_' . $set ];
		}
	}
	return array(
		'folder' => $v['folder'],
		'count'  => $count,
		'w'      => isset( $v['w'] ) ? absint( $v['w'] ) : 0,
		'h'      => isset( $v['h'] ) ? absint( $v['h'] ) : 0,
		'ext'    => $ext,
		'video'  => isset( $v['video'] ) ? absint( $v['video'] ) : 0,
	);
}

add_action( 'admin_enqueue_scripts', 'doi_admin_enqueue' );
function doi_admin_enqueue( $hook ) {
	if ( 'settings_page_door-open-intro' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'doi-admin-hero', DOI_URL . 'assets/admin-hero.js', array(), DOI_VERSION, true );
	wp_add_inline_script(
		'doi-admin-hero',
		'window.DOI_HERO_ADMIN = ' . wp_json_encode(
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'doi_hero_frames' ),
				'text'  => array(
					'choose'    => __( 'Choose a video for the corridor', 'door-open-intro' ),
					'use'       => __( 'Use this video', 'door-open-intro' ),
					'loading'   => __( 'Opening the video…', 'door-open-intro' ),
					'preparing' => __( 'Preparing frames: %1$d of %2$d', 'door-open-intro' ),
					'ready'     => __( 'Ready. Click “Save Changes” to use this video.', 'door-open-intro' ),
					'reset'     => __( 'Click “Save Changes” to go back to the original corridor.', 'door-open-intro' ),
					'original'  => __( 'The original corridor', 'door-open-intro' ),
					'failed'    => __( 'This video couldn\'t be prepared. Try an MP4 (H.264) file.', 'door-open-intro' ),
					'stuck'     => __( 'The video stopped moving while it was being prepared, so nothing was changed. Keep this tab in front until it says it is ready, then try again.', 'door-open-intro' ),
					'busy'      => __( 'A video is still being prepared. Leave this page anyway?', 'door-open-intro' ),
				),
			)
		) . ';',
		'before'
	);
}

function doi_hero_ajax_guard() {
	check_ajax_referer( 'doi_hero_frames', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}
}

/** Starts a new frame folder for one video. */
add_action( 'wp_ajax_doi_hero_begin', 'doi_ajax_hero_begin' );
function doi_ajax_hero_begin() {
	doi_hero_ajax_guard();
	$set  = ( isset( $_POST['set'] ) && 'mobile' === $_POST['set'] ) ? 'mobile' : 'desktop'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in doi_hero_ajax_guard().
	$root = doi_hero_upload_root();
	if ( ! wp_mkdir_p( $root['dir'] ) ) {
		wp_send_json_error( array( 'message' => 'uploads not writable' ), 500 );
	}
	if ( ! file_exists( $root['dir'] . '/index.php' ) ) {
		file_put_contents( $root['dir'] . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	$folder = $set . '-' . time() . '-' . strtolower( wp_generate_password( 6, false ) );
	wp_mkdir_p( $root['dir'] . '/' . $folder );
	wp_send_json_success( array( 'folder' => $folder, 'base' => $root['url'] . '/' . $folder . '/' ) );
}

/** Receives a batch of frames (f[] with their numbers in n[]) for a folder started above. */
add_action( 'wp_ajax_doi_hero_frames', 'doi_ajax_hero_frames' );
function doi_ajax_hero_frames() {
	doi_hero_ajax_guard();
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in doi_hero_ajax_guard().
	$folder = isset( $_POST['folder'] ) ? sanitize_text_field( wp_unslash( $_POST['folder'] ) ) : '';
	$ext    = ( isset( $_POST['ext'] ) && 'jpg' === $_POST['ext'] ) ? 'jpg' : 'webp';
	$nums   = isset( $_POST['n'] ) ? array_map( 'absint', (array) $_POST['n'] ) : array();
	// phpcs:enable
	$root = doi_hero_upload_root();
	$dir  = $root['dir'] . '/' . $folder;
	if ( ! preg_match( DOI_HERO_FOLDER, $folder ) || ! is_dir( $dir ) || empty( $_FILES['f']['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => 'bad request' ), 400 );
	}
	$want  = 'jpg' === $ext ? IMAGETYPE_JPEG : IMAGETYPE_WEBP;
	$saved = 0;
	foreach ( (array) $_FILES['f']['tmp_name'] as $k => $tmp ) {
		$n = isset( $nums[ $k ] ) ? $nums[ $k ] : 0;
		if ( $n < 1 || $n > 400 || ! is_uploaded_file( $tmp ) || filesize( $tmp ) > 4 * MB_IN_BYTES ) {
			continue;
		}
		$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- not an image is an expected outcome.
		if ( ! $info || $want !== $info[2] ) {
			continue;
		}
		if ( move_uploaded_file( $tmp, $dir . '/' . sprintf( '%03d.%s', $n, $ext ) ) ) {
			$saved++;
		}
	}
	wp_send_json_success( array( 'saved' => $saved ) );
}

/**
 * Frame folders no longer in use are removed when the settings are saved. Folders made in the last hour are
 * left alone, in case a video is still being prepared in another tab.
 */
add_action( 'update_option_' . DOI_OPTION, 'doi_hero_cleanup', 10, 2 );
function doi_hero_cleanup( $old, $new ) {
	$root = doi_hero_upload_root();
	if ( ! is_dir( $root['dir'] ) ) {
		return;
	}
	$keep = array();
	foreach ( array( 'hero_desktop', 'hero_mobile' ) as $key ) {
		if ( is_array( $new ) && ! empty( $new[ $key ]['folder'] ) ) {
			$keep[] = $new[ $key ]['folder'];
		}
	}
	foreach ( (array) glob( $root['dir'] . '/*', GLOB_ONLYDIR ) as $dir ) {
		$name = wp_basename( $dir );
		if ( in_array( $name, $keep, true ) || ! preg_match( DOI_HERO_FOLDER, $name ) ) {
			continue;
		}
		$parts = explode( '-', $name );
		if ( time() - (int) $parts[1] < HOUR_IN_SECONDS ) {
			continue;
		}
		foreach ( (array) glob( $dir . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}
}