<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_wp_hook( 'plugins_loaded', function(){
	require_once dirname( __FILE__ ) . '/update-verify.php';
});
