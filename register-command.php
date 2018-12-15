<?php
/**
 * Registers the WP-CLI command.
 *
 * @package Update-Verify
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	WP_CLI::add_command(
		'core safe-update',
		array( 'UpdateVerify\CLI', 'safe_update' ),
		array(
			'before_invoke' => function() {
				if ( ! version_compare( WP_CLI_VERSION, '1.5.0-alpha', '>=' ) ) {
					WP_CLI::error( 'Safe update requires WP-CLI 1.5.0-alpha-d71d228 or later' );
				}
			},
		)
	);

	// Hack because WP-CLI doesn't like commands registered to 'core' that run on before_wp_load.
	WP_CLI::add_hook(
		'find_command_to_run_pre',
		function() {
			WP_CLI::add_command(
				'core safe-update',
				array( 'UpdateVerify\CLI', 'safe_update' ),
				array(
					'before_invoke' => function() {
						if ( ! version_compare( WP_CLI_VERSION, '1.5.0-alpha', '>=' ) ) {
							WP_CLI::error( 'Safe update requires WP-CLI 1.5.0-alpha-d71d228 or later' );
						}
					},
				)
			);
		}
	);
}
