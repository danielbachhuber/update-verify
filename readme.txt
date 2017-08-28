=== Update Verify ===
Contributors: danielbachhuber, dreamhost
Tags: wordpress updates
Requires at least: 4.4
Tested up to: 4.8
Stable tag: 0.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Verifies the WordPress update process

== Description ==

Compares details about the WordPress instance before and after update to determine whether an update was successful.

Uses a HTTP GET request to the homepage to capture:

* HTTP status code.
* Whether or not a fatal error is detected.
* Whether or not the closing `</body>` tag is detected.

Based on these characteristics, the underlying subsystem can make a determination of whether the update was successful (or if a rollback needs to be initiated).

== Installation ==

Update Verify can be installed as a WordPress plugin or as a WP-CLI package.

To install as a WordPress plugin, which means Update Verify will be executed during web-based updates:

    wp plugin install --activate https://github.com/danielbachhuber/update-verify

To install as a WP-CLI package, which means Update Verify can be used globally on a server and will only execute during CLI-based updates:

    wp package install danielbachhuber/update-verify

== Changelog ==

= 0.1.0 (??? ??, 2017) =
* Initial release.
