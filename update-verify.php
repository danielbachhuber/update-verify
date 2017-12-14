<?php
/**
 * Plugin Name:     Update Verify
 * Plugin URI:      https://danielbachhuber.com
 * Description:     Verify the WordPress update process.
 * Author:          Daniel Bachhuber
 * Author URI:      https://danielbachhuber.com
 * Text Domain:     update-verify
 * Domain Path:     /languages
 * Version:         0.1.0
 * License:         GPL v2+
 *
 * @package Update-Verify
 */

require_once dirname( __FILE__ ) . '/src/CLI.php';
require_once dirname( __FILE__ ) . '/src/Observer.php';

require_once dirname( __FILE__ ) . '/register-command.php';

add_filter( 'upgrader_pre_download', array( 'UpdateVerify\Observer', 'filter_upgrader_pre_download' ), 10, 3 );
add_filter( 'upgrader_process_complete', array( 'UpdateVerify\Observer', 'action_upgrader_process_complete' ), 10, 2 );
