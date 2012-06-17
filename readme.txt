=== Gravity Forms eWAY ===
Contributors: webaware
Plugin Name: Gravity Forms eWAY
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/gravityforms-eway/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8V9YCKATQHKEN
Tags: gravityforms, gravity forms, eway
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a credit card payment gateway for eWAY to the Gravity Forms plugin

== Description ==

Gravity Forms eWAY adds a credit card payment gateway for [eWAY in Australia](http://www.eway.com.au/) to the [Gravity Forms](http://www.gravityforms.com/) plugin, using eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html).

* build online donation forms
* build online booking forms
* build simple Buy Now forms

> NB: this plugin extends [Gravity Forms](http://www.gravityforms.com/), but you still need to install and activate Gravity Forms!

= Requirements: =
* you need to install the [Gravity Forms](http://www.gravityforms.com/) plugin
* you need an SSL certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html), and does not support eWAY's hosted payment form

== Installation ==

1. Install and activate the [Gravity Forms](http://www.gravityforms.com/) plugin
2. Upload the Gravity Forms eWAY plugin to your /wp-content/plugins/ directory.
3. Activate the Gravity Forms eWAY plugin through the 'Plugins' menu in WordPress.
4. Edit the eWAY payment gateway settings to set your eWAY Customer ID and options

Gravity Forms will now display the Credit Card field under Pricing Fields when you edit a form.

= Building a Form with Credit Card Payments =

* add one or more product fields or a total field, so that there is something to be charged by credit card
* add an email field and and address field if you want to see them on your eWAY transaction; the first email field and first address field on the form will be sent to eWAY
* add a credit card field; if you have a multi-page form, this must be the on the last page so that all other form validations occur first
* add a confirmation message to the form indicating that payment was successful; the form will not complete if payment was not successful, and will display an error message in the credit card field

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test payments as you like, without billing a real credit card.

== Frequently Asked Questions ==

= Can I use other eWAY gateways, outside of Australia? =

Not yet. Basically, I haven't even looked at the other eWAY gateways, so I have no idea what's involved in supporting them. I reckon I'll get around to them one day though, so check back in 2013 maybe.

= Can I use the eWAY hosted payment form with this plugin? =

No, this plugin only supports the [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html).

= What Gravity Forms license do I need? =

Any Gravity Forms license will do. You can use this plugin with the Personal, Business or Developer licenses.

= Where do I find the eWAY transaction number? =

Successful transaction details including the eWAY transaction number are shown in the Info box when you view the details of a form entry in the WordPress admin.

= Why is the amount paid bigger than the form total when test mode is enabled? =

When test mode is enabled, the payment amount is rounded up by default, because the [eWAY sandbox server returns different error codes when the amount has cents](http://www.eway.com.au/developers/sandbox/direct-payments.html). This can be a useful feature for testing how your website displays errors, but you normally don't want it when testing a payment form.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

= Will this plugin work without installing Gravity Forms? =

No. This plugin adds an eWAY payment gateway to Gravity Forms so that you can add online payments to your forms. It does not replace Gravity Forms.

== Screenshots ==

1. Options screen
2. A sample donation form
3. The sample donation form as it appears on a page
4. How a credit card validation error appears
5. A successful entry in Gravity Forms admin

== Changelog ==

= 1.1.0 [2012-06-17] =
* added: options for extending use of eWAY sandbox (testing) environment capabilities
* added: more documentation (thanks, gymbaroo.net.au and samwoods!)

= 1.0.3 [2012-05-24] =
* fixed: don't show settings link if Gravity Forms is not installed and activated
* added: readme file makes it clear that this plugin requires Gravity Forms to be installed and activated

= 1.0.2 [2012-05-13] =
* fixed: correctly handle quantity for singleproduct fields
* fixed: don't validate or process credit card if credit card field is hidden (e.g. other payment option selected)
* fixed: form ID recorded in eWAY invoice reference field
* added: cardholder's name recorded in eWAY last name field (for reference on eWAY email notification)
* added: remove spaces/dashes from credit card numbers so that "valid" numbers can be passed to eWAY with spaces removed

= 1.0.1 [2012-05-05] =
* fixed: optional fields for address, email are no longer required for eWAY payment

= 1.0.0 [2012-04-16] =
* final cleanup and refactor for public release
