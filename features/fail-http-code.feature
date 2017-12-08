Feature: Verification fails when http code changes

  Background:
    Given a WP install
    And I run `wp option update home 'http://localhost:8080'`
    And I run `wp option update siteurl 'http://localhost:8080'`
    And I launch in the background `wp server --host=localhost --port=8080`

  Scenario: Verification fails when 500 http code is returned
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

    When I run `wp core download --version=4.7 --force`
    Then STDOUT should contain:
      """
      Success: WordPress downloaded.
      """
    And I run `wp core update-db`

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.7
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
       -> HTTP status code: 500
       -> No closing </body> tag detected.
       -> No uncaught fatal error detected.
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
