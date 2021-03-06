Feature: Safely update WordPress core

  Background:
    Given a WP install
    And I run `wp core download --force --version=4.6`
    And I run `wp core update-db`
    And I run `wp theme activate twentysixteen`
    And I run `wp option update home 'http://localhost:8080'`
    And I run `wp option update siteurl 'http://localhost:8080'`
    And I launch in the background `wp server --host=localhost --port=8080`

  Scenario: core safe-update without --version updates WordPress successfully
    When I run `wp core safe-update`
    Then STDOUT should contain:
      """
      Fetching post-update site response...
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

  Scenario: core safe-update with WordPress already on a newer version
    When I run `wp core safe-update --version=4.5.4`
    Then STDOUT should be:
      """
      Currently running version 4.6
      Success: WordPress is already at a newer version.
      """

    When I run `wp core safe-update --version=4.6`
    Then STDOUT should be:
      """
      Currently running version 4.6
      Success: WordPress is already at a newer version.
      """

  Scenario: core safe-update with --version updates WordPress successfully
    When I run `wp core safe-update --version=4.7.4`
    Then STDOUT should contain:
      """
      Fetching post-update site response...
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.7.4
      """

  Scenario: Early failed HTTP status code prevents core safe-update
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        status_header( 500 );
        exit;
      }
      """

    When I try `wp core safe-update`
    Then STDERR should be:
      """
      Error: Failed pre-update status code check (HTTP code 500).
      """
    And STDOUT should contain:
      """
      Fetching pre-update site response...
       -> HTTP status code: 500
       -> No closing </body> tag detected.
       -> No uncaught fatal error detected.
      """
    And the return code should be 1

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.6
      """

  Scenario: Early failed PHP fatal error check prevents core safe update
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        ini_set('display_errors', 1);
        echo '</body>';
        this_is_an_undefined_function();
      }
      """

    When I try `wp core safe-update`
    Then STDERR should contain:
      """
      Error: Failed pre-update PHP fatal error check.
      """
    And the return code should be 1

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.6
      """

   Scenario: Early failed closing </body> tag check prevents core safe update
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        exit;
      }
      """

    When I try `wp core safe-update`
    Then STDERR should contain:
      """
      Error: Failed pre-update closing </body> tag check.
      """
    And the return code should be 1

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.6
      """

  Scenario: Post-update failed HTTP status code causes rollback
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        $_wp_version = preg_replace( '/^.*\$wp_version *= *\'([^\']+)\';.*$/s', '\\1', file_get_contents( ABSPATH . WPINC . '/version.php' ) );
        if ( version_compare( $_wp_version, '4.8', '>=' ) ) {
          status_header( 500 );
          exit;
        }
      }
      """

    When I try `wp core safe-update`
    Then STDERR should be:
      """
      Error: Failed post-update status code check (HTTP code 500).
      """
    And STDOUT should contain:
      """
      Rolling WordPress back to version 4.6...
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.6
      """

  Scenario: Post-update failed </body> tag check causes rollback
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        $_wp_version = preg_replace( '/^.*\$wp_version *= *\'([^\']+)\';.*$/s', '\\1', file_get_contents( ABSPATH . WPINC . '/version.php' ) );
        if ( version_compare( $_wp_version, '4.8', '>=' ) ) {
          exit;
        }
      }
      """

    When I try `wp core safe-update`
    Then STDERR should be:
      """
      Error: Failed post-update closing </body> tag check.
      """
    And STDOUT should contain:
      """
      Rolling WordPress back to version 4.6...
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.6
      """

  @less-than-php-7.0
  Scenario: Updates a really old WordPress install
    When I run `wp theme install twentythirteen --activate`
    And I run `wp core download --version=4.0 --force`
    And "4.0" replaced with "3.6" in the wp-includes/version.php file
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp core safe-update --version=4.2`
    Then STDOUT should contain:
      """
      Currently running version 3.6
      Detected really old WordPress. First updating to version 3.7...
      """
    And STDOUT should contain:
      """
      Forced update to WordPress 3.7. Proceeding with remaining update...
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.2
      """

  Scenario: Catches Request exception on failed DNS resolution
    When I run `wp core download --version=4.0 --force`
    And I run `wp option update home 'http://foobar.example.com'`
    And "4.0" replaced with "3.6" in the wp-includes/version.php file
    Then STDOUT should contain:
      """
      Success:
      """

    When I try `wp core safe-update --version=4.2`
    Then STDERR should contain:
      """
      Error: Requests Exception -
      """

  Scenario: Catches MySQL query failure
    Given "4.6" replaced with "3.6" in the wp-includes/version.php file
    And "'wp_cli_test'" replaced with "'wp_cli_fail'" in the wp-config.php file

    When I try `wp core safe-update --version=4.2`
    Then STDERR should contain:
      """
      Error: Failed to execute MySQL query:
      """
