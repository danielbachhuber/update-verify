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
		self::check_site_response();
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
		self::check_site_response();
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
			echo htmlentities( $message ) . PHP_EOL;
		}
	}

	/**
	 * Check a site response for basic operating details and log output
	 */
	private static function check_site_response() {
		$response = self::get_site_response();
		self::log_message( 'HTTP status code: ' . $response['status_code'] );
		if ( false === stripos( $response['body'], '</body>' ) ) {
			self::log_message( 'No closing </body> tag detected.' );
		} else {
			self::log_message( 'Detected closing </body> tag.' );
		}
		$stripped_body = strip_tags( $response['body'] );
		if ( false !== stripos( $stripped_body, 'Fatal error:' ) ) {
			self::log_message( 'Detected uncaught fatal error.' );
		} else {
			self::log_message( 'No uncaught fatal error detected.' );
		}
	}

	/**
	 * Capture basic operating details
	 */
	private static function get_site_response() {
		$response = wp_remote_post(
			get_option( 'home' ), array(
				'timeout' => 5,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return array(
			'status_code' => (int) wp_remote_retrieve_response_code( $response ),
			'body'        => wp_remote_retrieve_body( $response ),
		);
	}

}
