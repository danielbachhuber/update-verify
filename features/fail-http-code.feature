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
      if ( version_compare( $GLOBALS['wp_version'], '4.8', '>=' ) ) {
        status_header( 500 );
        exit;
      }
      """

    When I run `wp core download --version=4.7 --force`
    Then STDOUT should contain:
      """
      Success: WordPress downloaded.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.7
      """

    When I run `wp core update`
    Then STDOUT should contain:
      """
      Fetching pre-update site response...
      HTTP status code: 200
      Detected closing </body> tag.
      No uncaught fatal error detected.
      """
    And STDOUT should contain:
      """
      Fetching post-update site response...
      HTTP status code: 500
      No closing </body> tag detected.
      No uncaught fatal error detected.
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
