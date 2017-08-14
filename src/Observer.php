<?php
/**
 * Observes the upgrade process
 *
 * @package Update-Verify
 */

namespace UpdateVerify;

/**
 * Verifies core updates
 */
class Observer {

	/**
	 * Fires near the beginning of the upgrade process
	 *
	 * @param false $retval Returns false to continue the process.
	 */
	public static function filter_upgrader_pre_download( $retval ) {
		self::log_message( 'Fetching pre-update site response...' );
		$response = self::get_site_response();
		self::log_message( 'HTTP status code: ' . $response['status_code'] );
		return $retval;
	}

	/**
	 * Fires at the end of the upgrade process
	 *
	 * @param object $upgrader Upgrader instance.
	 * @param array  $result   Result of the upgrade process.
	 */
	public static function action_upgrader_process_complete( $upgrader, $result ) {
		self::log_message( 'Fetching post-update site response...' );
		$response = self::get_site_response();
		self::log_message( 'HTTP status code: ' . $response['status_code'] );
	}

	/**
	 * Log a message to STDOUT
	 *
	 * @param string $message Message to render.
	 */
	private static function log_message( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $message );
		} else {
			echo $message . PHP_EOL;
		}
	}

	/**
	 * Capture basic operating details
	 */
	private static function get_site_response() {
		$response = wp_remote_post( get_option( 'home' ), array(
			'timeout'  => 5,
		) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return array(
			'status_code'    => (int) wp_remote_retrieve_response_code( $response ),
			'body'           => wp_remote_retrieve_body( $response ),
		);
	}

}
