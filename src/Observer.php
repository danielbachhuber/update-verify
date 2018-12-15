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
	 * @param false       $retval   Returns false to continue the process.
	 * @param string      $package  The package file name.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance.
	 */
	public static function filter_upgrader_pre_download( $retval, $package, $upgrader ) {
		self::log_message( 'Fetching pre-update site response...' );
		$site_response = self::check_site_response( home_url( '/' ) );
		/**
		 * Permit modification of $retval based on the site response.
		 *
		 * @param mixed       $retval        Return value to WP_Upgrader.
		 * @param array       $site_response Values for the site heuristics check.
		 * @param string      $package       The package file name.
		 * @param WP_Upgrader $upgrader      The WP_Upgrader instance.
		 */
		$retval = apply_filters( 'upgrade_verify_upgrader_pre_download', $retval, $site_response, $package, $upgrader );
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
		$site_response = self::check_site_response( home_url( '/' ) );
		/**
		 * Permit action based on the post-update site response check.
		 *
		 * @param array       $site_response Values for the site heuristics check.
		 * @param WP_Upgrader $upgrader      The WP_Upgrader instance.
		 */
		do_action( 'upgrade_verify_upgrader_process_complete', $site_response, $upgrader );
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
	 * Check a site response for basic operating details and log output.
	 *
	 * @param string $url URL to check.
	 * @return array Response data.
	 */
	public static function check_site_response( $url ) {
		$response = self::get_site_response( $url );
		self::log_message( ' -> HTTP status code: ' . $response['status_code'] );
		$site_response = array(
			'status_code'  => $response['status_code'],
			'closing_body' => null,
			'php_fatal'    => null,
		);
		if ( false === stripos( $response['body'], '</body>' ) ) {
			self::log_message( ' -> No closing </body> tag detected.' );
			$site_response['closing_body'] = false;
		} else {
			self::log_message( ' -> Correctly detected closing </body> tag.' );
			$site_response['closing_body'] = true;
		}
		$stripped_body = strip_tags( $response['body'] );
		if ( false !== stripos( $stripped_body, 'Fatal error:' ) ) {
			self::log_message( ' -> Detected uncaught fatal error.' );
			$site_response['php_fatal'] = true;
		} else {
			self::log_message( ' -> No uncaught fatal error detected.' );
			$site_response['php_fatal'] = false;
		}
		return $site_response;
	}

	/**
	 * Capture basic operating details
	 *
	 * @param string $check_url URL to check.
	 */
	private static function get_site_response( $check_url ) {
		if ( class_exists( 'Requests' ) ) {
			$response = \Requests::get( $check_url );
			return array(
				'status_code' => (int) $response->status_code,
				'body'        => $response->body,
			);
		}
		$response = wp_remote_post(
			get_option( 'home' ),
			array(
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
