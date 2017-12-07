# Update Verify #
**Contributors:** danielbachhuber, dreamhost  
**Tags:** wordpress updates  
**Requires at least:** 4.4  
**Tested up to:** 4.8  
**Stable tag:** 0.0.0  
**License:** MIT  
**License URI:** https://opensource.org/licenses/MIT  

Verifies the WordPress update process

## Description ##

Verifies the WordPress update process by comparing details about the WordPress installation before and after the update.

Captured in a HTTP GET request against the home URL, the heuristics include:

* HTTP status code (200 or otherwise).
* Whether or not a PHP fatal error is detected.
* Whether or not the closing `</body>` tag is detected.

Because Update Verify hooks into the WordPress update process, the default behavior is to output this heuristic information alongside web or CLI update output.

To control the update process based on the heuristics, use the `wp core safe-update` WP-CLI command:

    ###
    # 500 status code is observed while updating from WP 4.6 to 4.9
    ###
    $ wp core safe-update
    Currently running version 4.6
    Updating to version 4.9.1 (en_US)...
    Fetching pre-update site response...
    HTTP status code: 200
    Detected closing </body> tag.
    No uncaught fatal error detected.
    Unpacking the update...
    Fetching post-update site response...
    HTTP status code: 500
    No closing </body> tag detected.
    No uncaught fatal error detected.
    Rolling WordPress back to version 4.6...
    Downloading WordPress 4.6 (en_US)...
    154 files cleaned up.
    Success: WordPress downloaded.
    Error: Failed post-update status code check (HTTP code 500).

Under the hood, this WP-CLI command aborts the update process if it detects WordPress to already be broken, and rolls back to the prior WordPress version if the update process caused detectable breakage.

## Installation ##

Update Verify can be installed as a WP-CLI package or as a WordPress plugin.

To install as a WordPress plugin, which means Update Verify will be executed during web-based updates:

    wp plugin install --activate https://github.com/danielbachhuber/update-verify

To install as a WP-CLI package, which means Update Verify can be used globally on a server and will only execute during CLI-based updates:

    wp package install danielbachhuber/update-verify

## Changelog ##

### 0.1.0 (??? ??, 2017) ###
* Initial release.
