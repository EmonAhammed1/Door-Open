<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'doi_intro' );
delete_option( 'doi_settings' ); // left behind by versions before 4.0
