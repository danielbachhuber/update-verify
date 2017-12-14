<?php
/**
 * Provides CLI interfaces to safe upgrades.
 *
 * @package Update-Verify
 */

namespace UpdateVerify;

use WP_CLI;
use WP_Error;

/**
 * Provides CLI interfaces to safe upgrades.
 */
class CLI {

	/**
	 * Safely updates WordPress to a newer version.
	 *
	 * Performs a `wp core update` by checking site availability heuristics
	 * before and after to determine whether the update process proceeded
	 * without error. Aborts update process if errors are detected beforehand,
	 * and rolls back to the prior WP version if an error was detected afterward.
	 *
	 * ## OPTIONS
	 *
	 * [--path=<path>]
	 * : Specify the path in which to update WordPress. Defaults to current
	 * directory.
	 *
	 * [--version=<version>]
	 * : Update to a specific version, instead of to the latest version.
	 *
	 * @when before_wp_load
	 */
	public static function safe_update( $args, $assoc_args ) {

		if ( ! is_readable( ABSPATH . 'wp-includes/version.php' ) ) {
			WP_CLI::error(
				"This does not seem to be a WordPress install.\n" .
				'Pass --path=`path/to/wordpress` or run `wp core download`.'
			);
		}
		global $wp_version;
		include ABSPATH . 'wp-includes/version.php';
		$current_version = $wp_version;

		WP_CLI::log( 'Currently running version ' . $current_version );

		$is_site_response_errored = function( $site_response, $stage ) {
			if ( 200 !== $site_response['status_code'] ) {
				return sprintf( 'Failed %s-update status code check (HTTP code %d).', $stage, $site_response['status_code'] );
			} elseif ( ! empty( $site_response['php_fatal'] ) ) {
				return sprintf( 'Failed %s-update PHP fatal error check.', $stage );
			} elseif ( empty( $site_response['closing_body'] ) ) {
				return sprintf( 'Failed %s-update closing </body> tag check.', $stage );
			}
			return false;
		};

		if ( version_compare( $current_version, '3.7', '<' ) ) {
			WP_CLI::log( 'Detected really old WordPress. First updating to version 3.7...' );
			$home_url      = 'http://localhost:8080';
			$site_response = Observer::check_site_response( $home_url );
			$is_errored    = $is_site_response_errored( $site_response, 'pre' );
			if ( $is_errored ) {
				WP_CLI::error( $is_errored );
			}
			WP_CLI::runcommand(
				'core download --skip-content --force --version=3.7', array(
					'launch' => true,
				)
			);
			$site_response = Observer::check_site_response( $home_url );
			$is_errored    = $is_site_response_errored( $site_response, 'post' );
			if ( $is_errored ) {
				WP_CLI::log( "Rolling WordPress back to version {$current_version}..." );
				WP_CLI::runcommand(
					'core download --skip-content --force --version=' . $current_version, array(
						'launch' => true,
					)
				);
				WP_CLI::error( $is_errored );
			}
			WP_CLI::log( 'Forced update to WordPress 3.7. Proceeding with remaining update...' );
		}

		WP_CLI::get_runner()->load_wordpress();

		/**
		 * Bail early if any errors are detected with the site.
		 */
		WP_CLI::add_wp_hook(
			'upgrade_verify_upgrader_pre_download', function( $retval, $site_response ) use ( $is_site_response_errored ) {
				$is_errored = $is_site_response_errored( $site_response, 'pre' );
				if ( $is_errored ) {
					return new \WP_Error( 'upgrade_verify_fail', $is_errored );
				}
				return $retval;
			}, 10, 2
		);

		/**
		 * Roll back to prior version if errors were detected post-update.
		 */
		WP_CLI::add_wp_hook(
			'upgrade_verify_upgrader_process_complete', function( $site_response ) use ( $current_version, $is_site_response_errored ) {
				$is_errored = $is_site_response_errored( $site_response, 'post' );
				if ( $is_errored ) {
					if ( method_exists( 'WP_Upgrader', 'release_lock' ) ) {
						\WP_Upgrader::release_lock( 'core_updater' );
					}
					WP_CLI::log( "Rolling WordPress back to version {$current_version}..." );
					WP_CLI::runcommand(
						'core download --skip-content --force --version=' . $current_version, array(
							'launch' => false,
						)
					);
					WP_CLI::error( $is_errored );
				}
			}
		);

		$update_version = ! empty( $assoc_args['version'] ) ? ' --version=' . $assoc_args['version'] : '';
		$response       = WP_CLI::runcommand(
			'core update' . $update_version, array(
				'launch' => false,
			)
		);

	}

}
