=== GravityForms eWAY ===
Contributors: webaware
Plugin Name: GravityForms eWAY
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/gravityforms-eway/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8V9YCKATQHKEN
Tags: gravityforms, gravity forms, eway
Requires at least: 3.0.1
Tested up to: 3.3.1
Stable tag: 1.0.0

Add a credit card payment gateway for eWAY to the GravityForms plugin

== Description ==

GravityForms eWAY adds a credit card payment gateway for [eWAY in Australia](http://www.eway.com.au/) to the [Gravity Forms](http://www.gravityforms.com/) plugin.

* build online donation forms
* build online booking forms
* build simple Buy Now forms

== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit the eWAY payment gateway settings to set your eWAY Customer ID and options

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test payments as you like, without billing a real credit card.

== Frequently Asked Questions ==

= Can I use other eWAY gateways, outside of Australia? =

Not yet. Basically, I haven't even looked at the other eWAY gateways, so I have no idea what's involved in supporting them. I reckon I'll get around to them one day though, so check back in 2013 maybe.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

== Changelog ==

= 1.0.0 [2012-04-16] =
* final cleanup and refactor for public release
