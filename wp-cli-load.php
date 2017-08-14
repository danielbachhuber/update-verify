<?php
/**
 * Loads the plugin when called from the WP-CLI context
 *
 * @package Update-Verify
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_wp_hook( 'plugins_loaded', function() {
	require_once dirname( __FILE__ ) . '/update-verify.php';
});
