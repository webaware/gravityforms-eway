=== Gravity Forms eWAY ===
Contributors: webaware
Plugin Name: Gravity Forms eWAY
Plugin URI: http://shop.webaware.com.au/downloads/gravity-forms-eway/
Author URI: http://webaware.com.au/
Donate link: http://shop.webaware.com.au/downloads/gravity-forms-eway/
Tags: gravityforms, gravity forms, gravity, eway, donation, donations, payment, recurring, ecommerce, credit cards, australia
Requires at least: 3.7
Tested up to: 4.2.1
Stable tag: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Gravity Forms with the eWAY credit card payment gateway

== Description ==

Gravity Forms eWAY adds a credit card payment gateway for [eWAY in Australia](http://www.eway.com.au/) to the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin, using eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html) or [Stored Payments API](http://www.eway.com.au/developers/api/stored-%28xml%29).

* build online donation forms
* build online booking forms
* build simple Buy Now forms
* accept recurring payments

> NB: this plugin extends [Gravity Forms](http://webaware.com.au/get-gravity-forms); you still need to install and activate Gravity Forms!

= Sponsorships =

* recurring payments generously sponsored by [Castle Design](http://castledesign.com.au/)

Thanks for sponsoring new features on Gravity Forms eWAY!

= Requirements: =
* you need to install the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin
* you need an SSL certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html) or [Stored Payments API](http://www.eway.com.au/developers/api/stored-%28xml%29), and does not support eWAY's hosted payment form

== Installation ==

1. Install and activate the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin
2. Upload the Gravity Forms eWAY plugin to your /wp-content/plugins/ directory.
3. Activate the Gravity Forms eWAY plugin through the 'Plugins' menu in WordPress.
4. Edit the eWAY payment gateway settings to set your eWAY Customer ID and options

Gravity Forms will now display the Credit Card and Recurring fields under Pricing Fields when you edit a form.

= Building a Form with Credit Card Payments =

* add one or more product fields or a total field, or a recurring field, so that there is something to be charged by credit card
* add a name field (with first name and last name) if you want to see the customer's name on the eWAY transaction; the first name field will be sent to eWAY
* add an email field and an address field if you want to see them on your eWAY transaction; the first email field and first address field on the form will be sent to eWAY
* add a credit card field; if you have a multi-page form, this must be on the last page so that all other form validations occur first
* add a confirmation message to the form indicating that payment was successful; the form will not complete if payment was not successful, and will display an error message in the credit card field

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test payments as you like, without billing a real credit card.

== Frequently Asked Questions ==

= What is eWAY? =

eWAY is a leading provider of online payments solutions for Australia, New Zealand and the UK. This plugin integrates with the Australian Direct Payments and Stored Payments gateways, so that your website can safely accept credit card payments.

= Will this plugin work without installing Gravity Forms? =

No. This plugin adds an eWAY payment gateway to Gravity Forms so that you can add online payments to your forms. You must purchase and install a copy of the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin too.

= Can I use other eWAY gateways, outside of Australia? =

Not yet. A premium plugin using eWAY's Rapid 3.1 API is coming sometime in 2014, so check back in a while.

= Can I use the eWAY hosted payment form with this plugin? =

No, this plugin only supports the [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html).

= What is Stored Payments? =

Like Direct Payments, the purchase information is sent to eWAY for processing, but with [Stored Payments](http://www.eway.com.au/how-it-works/payment-products#stored-payments) it isn't processed right away. The merchant needs to login to their eWAY Business Centre to complete each transaction. It's useful for shops that do drop-shipping and want to delay billing. Most websites should have this option set to No.

= What is Beagle? =

[Beagle](http://www.eway.com.au/how-it-works/payment-products#beagle-%28free%29) is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure [Beagle rules](http://www.eway.com.au/developers/resources/beagle-%28free%29-rules) in your MYeWAY console before enabling Beagle in this plugin.

**NB**: You will also need to add an Address field to your form, and make it required. Beagle works by comparing the country of the address with the country where the purchaser is using the Internet; if you don't set it to Required, then Beagle won't be used when submitting the form without a country selected.

= What Gravity Forms license do I need? =

Any Gravity Forms license will do. You can use this plugin with the Personal, Business or Developer licenses.

= Where do I find the eWAY transaction number? =

Successful transaction details including the eWAY transaction number and bank authcode are shown in the Info box when you view the details of a form entry in the WordPress admin. Recurring payments don't get a transaction number when the payment is established, however, so only the payment status and date are recorded.

= How do I add a confirmed payment amount and transaction number to my Gravity Forms notification emails? =

Browse to your Gravity Form, select [Notifications](http://www.gravityhelp.com/documentation/page/Notifications) and use the Insert Merge Tag dropdown (Payment Amount, Transaction Number and Auth Code will appear under Custom at the very bottom of the dropdown list).

= Why is the amount paid bigger than the form total when sandbox is enabled? =

When the sandbox is enabled, the payment amount is rounded up by default, because the [eWAY sandbox server returns different error codes when the amount has cents](http://www.eway.com.au/developers/sandbox/direct-payments.html). This can be a useful feature for testing how your website displays errors, but you normally don't want it when testing a payment form.

= Why do I get an error "This page is unsecured"? =

When your form has a credit card field, it accepts very sensitive details from your customers and these must be encrypted. You must have an SSL certificate installed on your website, and your page must be accessed via SSL (i.e. the page address must start with "https:"). You can force a page with a credit card form to be accessed via SSL by ticking Force SSL on the Credit Card Field advanced settings page; see [screenshots](https://wordpress.org/plugins/gravityforms-eway/screenshots/).

= Can I do recurring payments? =

Yes, thanks to the generous sponsorship of [Castle Design](http://castledesign.com.au/). If you use [conditional logic](http://www.gravityhelp.com/documentation/page/Enable_Conditional_Logic) to hide/show a product field and a recurring payment field, you can even let customers choose between a one-off payment and a recurring payment. Payments can be scheduled for weekly, fortnightly, monthly, quarterly, or yearly billing. Examples will be presented on [the plugin's homepage](http://snippets.webaware.com.au/wordpress-plugins/gravityforms-eway/) as time permits.

NB: when testing recurring payments in the Sandbox, you must use the special test customer ID 87654321. You can temporarily force this by enabling the setting "Force Test Customer ID in Sandbox".

= I get an SSL error when my form attempts to connect with eWAY =

This is a common problem in local testing environments. Please [read this post](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

== Screenshots ==

1. Options screen
2. A sample donation form
3. The sample donation form as it appears on a page
4. How a credit card validation error appears
5. A successful entry in Gravity Forms admin
6. Example with recurring payments
7. Forcing SSL on a page with a credit card form

== Filter hooks ==

Developers can use these filter hooks to modify some eWAY invoice properties. Each filter receives a string for the field value, and the Gravity Forms form array.

* `gfeway_form_is_eway` for telling Gravity Forms eWAY to ignore a form
* `gfeway_invoice_desc` for modifying the invoice description
* `gfeway_invoice_ref` for modifying the invoice reference
* `gfeway_invoice_trans_number` for modifying the invoice transaction reference
* `gfeway_invoice_option1` for setting the option1 field (one-off payments)
* `gfeway_invoice_option2` for setting the option2 field (one-off payments)
* `gfeway_invoice_option3` for setting the option3 field (one-off payments)
* `gfeway_invoice_cust_comments` for setting the customer comments field (recurring payments)
* `gfeway_recurring_periods` for filtering the available recurring periods (from 'weekly', 'fortnightly', 'monthly', 'quarterly', 'yearly')

== Contributions ==

* [Fork me on GitHub](https://github.com/webaware/gravityforms-eway/)

== Changelog ==

= 1.7.1, soon... =
* added: some more precautionary XSS prevention steps

= 1.7.0, 2014-11-08 =
* fixed: Gravity Forms 1.9 compatibility
* added: custom entry meta `authcode` and `payment_gateway` which can be added to listings
* changed: cache result of `isEwayForm()` (may affect some hookers intercepting filter `gfeway_form_is_eway`)
* changed: some code cleanup
* changed: minimum requirements now WordPress 3.7, Gravity Forms 1.7

= 1.6.3, 2014-08-25 =
* added: filter `gfeway_form_is_eway` for telling Gravity Forms eWAY to ignore a form

= 1.6.2, 2014-08-15 =
* added: basic support for Gravity Forms Logging Add-On, to assist support requests; credit card numbers are obfuscated

= 1.6.1, 2014-06-25 =
* fixed: Gravity Forms 1.8.9 Payment Details box on entry details

= 1.6.0, 2014-06-07 =
* fixed: hidden products are now correctly handled
* fixed: shipping is now correctly handled
* fixed: RGFormsModel::update_lead() is deprecated in Gravity Forms v1.8.8
* changed: move authcode, beagle score into Gravity Forms 1.8.8 Payment Details box on entry details
* changed: merge template for payment amount is now formatted as currency
* changed: some code refactoring

= 1.5.12, 2014-05-14 =
* fixed: products with separate quantity fields fail

= 1.5.11, 2014-04-01 =
* fixed: load datepicker styling for Gravity Forms 1.8.6 when no other datepicker fields are present

= 1.5.10, 2014-01-18 =
* fixed: recurring payments failed on Windows hosting, no function strptime()
* fixed: should not be able to duplicate recurring payments field in form editor
* added: custom merge field for payment status

= 1.5.9, 2014-01-03 =
* fixed: a datepicker was being added to the bottom of the page since WordPress 3.8 (jQuery-UI bug on set datepicker options late)
* fixed: clean up JSHint warnings

= 1.5.8, 2013-11-10 =
* fixed: settings wouldn't save in WordPress multisite installations
* fixed: doco / settings page didn't explain that Beagle requires an Address field
* fixed: Beagle IP address 127.0.0.1 for form submitted on server (substitutes with an Australian IP address)
* changed: eWAY settings page is now a Gravity Forms settings subpage, like other addons

= 1.5.7, 2013-08-23 =
* fixed: a datepicker was being added to the bottom of the page when initial and start/end dates were hidden on recurring payments

= 1.5.6, 2013-08-19 =
* fixed: Gravity Forms 1.7.7 **changed their datepicker script handle** and broke recurring fields (not all change is good!)
* fixed: can now set Show Start/End Dates without Show Initial Amount

= 1.5.5, 2013-07-21 =
* fixed: can select currency for Gravity Forms for forms not using credit card field, but still enforces AUD for forms with credit card field
* fixed: nonce (number once) handling in settings admin
* added: load unminified script if SCRIPT_DEBUG is defined / true

= 1.5.4, 2013-04-21 =
* fixed: recurring payments fields marked "required" were generating error "This field is required"
* added: quarterly recurring time period

= 1.5.3, 2013-04-07 =
* fixed: don't squabble with other plugins (e.g. DPS PxPay) for custom merge tags of same name
* added: recurring payments can set customer reference and invoice reference independently, through the filters `gfeway_invoice_ref` and `gfeway_invoice_trans_number` respectively

= 1.5.2, 2013-03-19 =
* added: filter `gfeway_invoice_trans_number` for setting the invoice transaction reference (NB: 16 character limit)

= 1.5.1, 2013-02-14 =
* fixed: merge tags work on new notification emails, not just on resends!
* added: filter `gfeway_recurring_periods` for removing recurring payment periods, from `array('weekly', 'fortnightly', 'monthly', 'yearly')`

= 1.5.0, 2013-01-26 =
* added: support for [Beagle (free)](http://www.eway.com.au/developers/resources/beagle-%28free%29-rules) anti-fraud using geo-IP (Direct Payments only)
* added: record authcode for transactions, and show on entry details screen
* added: merge tags for authcode and beagle_score, for notification emails
* changed: use WordPress function wp_remote_post() instead of directly calling curl functions

= 1.4.1, 2013-01-17 =
* added: when entry is stored as 'Pending' for Stored Payment, can edit and change to 'Approved'
* added: record payment gateway in lead properties
* changed: Stored Payments use the Direct Payments sandbox when sandbox is selected

= 1.4.0, 2013-01-17 =
* added: can now use eWAY Stored Payments, e.g. for merchants who do drop-shipping
* added: merge tags for transaction_id and payment_amount, for notification emails

= 1.3.0, 2012-10-22 =
* fixed: can't submit form multiple times and get multiple payments
* added: can now customise the eWAY credit card error messages

= 1.2.2, 2012-10-03 =
* fixed: error when recurring field is present on form, but hidden (thanks, [Simon Watson](http://moonbuggymedia.com/)!)
* fixed: some undefined index PHP errors

= 1.2.1, 2012-10-02 =
* fixed: address on one-off eWAY invoice was getting "0, " prepended when PHP < 5.3
* fixed: address line 2 combined with line 1 when provided

= 1.2.0, 2012-09-21 =
* added: option to disable whether remote SSL certificate must be verified (only disable if your website can't be correctly configured!)
* added: prevent XML injection attacks when loading eWAY response (security hardening)
* added: recurring payments (sponsored by [Castle Design](http://castledesign.com.au/) -- thanks!)
* added: if a name field is added to the form, it will be used for the eWAY customer name (NB: not cardholder name)
* added: filter hooks for invoice description and reference

= 1.1.0, 2012-06-17 =
* added: options for extending use of eWAY sandbox (testing) environment capabilities
* added: more documentation (thanks, [gymbaroo.net.au](http://gymbaroo.net.au/) and samwoods!)

= 1.0.3, 2012-05-24 =
* fixed: don't show settings link if Gravity Forms is not installed and activated
* added: readme file makes it clear that this plugin requires Gravity Forms to be installed and activated

= 1.0.2, 2012-05-13 =
* fixed: correctly handle quantity for singleproduct fields
* fixed: don't validate or process credit card if credit card field is hidden (e.g. other payment option selected)
* fixed: form ID recorded in eWAY invoice reference field
* added: cardholder's name recorded in eWAY last name field (for reference on eWAY email notification)
* added: remove spaces/dashes from credit card numbers so that "valid" numbers can be passed to eWAY with spaces removed

= 1.0.1, 2012-05-05 =
* fixed: optional fields for address, email are no longer required for eWAY payment

= 1.0.0, 2012-04-16 =
* final cleanup and refactor for public release

== Upgrade Notice ==

= 1.7.0 =
* changed: minimum requirements now WordPress 3.7, Gravity Forms 1.7

= 1.2.0 =
* After upgrading, if you get connection errors posting to eWAY, please change your settings for "Verify remote SSL certificate"; only disable if your website can't be correctly configured!
