Feature: Update verification passes

  Background:
    Given a WP install
    And I run `wp option update home 'http://localhost:8080'`
    And I run `wp option update siteurl 'http://localhost:8080'`
    And I launch in the background `wp server --host=localhost --port=8080`

  Scenario: Update verification passes for a standard WP install

    When I run `wp core download --version=4.8 --force`
    Then STDOUT should contain:
      """
      Success: WordPress downloaded.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      4.8
      """

    When I run `wp core update --minor`
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
       -> Detected closing </body> tag.
       -> No uncaught fatal error detected.
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
