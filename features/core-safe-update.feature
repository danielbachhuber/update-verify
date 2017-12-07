Feature: Safely update WordPress core

  Background:
    Given a WP install
    And I run `wp core download --force --version=4.6`
    And I run `wp option update home 'http://localhost:8080'`
    And I run `wp option update siteurl 'http://localhost:8080'`
    And I launch in the background `wp server --host=localhost --port=8080`

  Scenario: core safe-update updates WordPress successfully
    When I run `wp core safe-update`
    Then STDOUT should contain:
      """
      Fetching post-update site response...
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
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
      HTTP status code: 500
      No closing </body> tag detected.
      No uncaught fatal error detected.
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
