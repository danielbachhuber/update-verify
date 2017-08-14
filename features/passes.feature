Feature: Update verification passes

  Scenario: Update verification passes for a standard WP install
    Given a WP install

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

    When I run `wp core update`
    Then STDOUT should contain:
      """
      Fetching pre-update site response...
      HTTP status code: 200
      """
    And STDOUT should contain:
      """
      Fetching post-update site response...
      HTTP status code: 200
      Success: WordPress updated successfully.
      """
