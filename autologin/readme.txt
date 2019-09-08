=== Login By Signed URL ===
Contributors: Michael Fielding
Tags: Autologin, login, bypass login, automatic login, wordpress login
Requires at least: 4.0
Tested up to: 5.2.3
Stable tag: 1.0.0
License: GPLv2 or later

Login By Signed URL enables other systems to generate links that will automatically sign users into a WordPress site.

== Description ==

Login By Signed URL enables other systems to generate links that will automatically sign users into a WordPress site. 

Basically it enables two customisable query parameters which the plugin will look for when processing a page request. 
One parameter is the username to log in, and the other is an SHA256 hash of the username concatenated with a configurable secret key. 
If the hash is correct for the provided username, the user is logged in. Either way WordPress will continue to process the page
as normal.

== Installation ==

Upload the Login By Signed URL plugin to your site, activate it, and go to the settings page (find it under WordPress Settings).

The Login By Signed URL settings page shows the secret key and allows you to select which WordPress roles can be logged in using a link. 
It also allows customising the query parameter names - by default they are 'name' and 'sig' - and enabling debugging, which sends HTTP 
headers in the response to indicate whether a user was logged in or why not. (Disable debugging in production for better security.)

== Changelog ==

= 1.1.0 =

Major re-write.



















