Feature: Verification fails when there's an uncaught fatal

  Background:
    Given a WP install
    And I run `wp core download --force --version=4.6`
    And I run `wp core update-db`
    And I run `wp theme activate twentysixteen`
    And I run `wp option update home 'http://localhost:8080'`
    And I run `wp option update siteurl 'http://localhost:8080'`
    And I launch in the background `wp server --host=localhost --port=8080`

  Scenario: Verification fails when there's an uncaught fatal
    Given a wp-content/mu-plugins/fail.php file:
      """
      <?php
      ini_set('display_errors', 1);
      if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
        $_wp_version = preg_replace( '/^.*\$wp_version *= *\'([^\']+)\';.*$/s', '\\1', file_get_contents( ABSPATH . WPINC . '/version.php' ) );
        if ( version_compare( $_wp_version, '4.8', '>=' ) ) {
          this_is_an_undefined_function();
        }
      }
      """

    When I run `wp core update`
    Then STDOUT should contain:
      """
      Fetching pre-update site response...
       -> HTTP status code: 200
       -> Detected closing </body> tag.
       -> No uncaught fatal error detected.
      """
    And STDOUT should contain:
      """
      Fetching post-update site response...
       -> HTTP status code: 200
       -> No closing </body> tag detected.
       -> Detected uncaught fatal error.
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
