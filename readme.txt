=== Gravity Forms eWAY ===
Contributors: webaware
Plugin Name: Gravity Forms eWAY
Plugin URI: http://shop.webaware.com.au/downloads/gravity-forms-eway/
Author URI: http://webaware.com.au/
Donate link: http://shop.webaware.com.au/donations/?donation_for=Gravity+Forms+eWAY
Tags: gravityforms, gravity forms, gravity, eway, donation, donations, payment, recurring, ecommerce, credit cards, australia, new zealand, uk, singapore, malaysia, hong kong
Requires at least: 4.2
Tested up to: 4.4.2
Stable tag: 2.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate Gravity Forms with eWAY payment gateway, enabling end users to purchase goods and services through Gravity Forms.

== Description ==

Gravity Forms eWAY adds a credit card payment gateway for [eWAY](https://eway.io/) to the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin, using eWAY's [Rapid API Direct Payments](https://eway.io/features/api-rapid-api) and [Recurring Payments XML API](https://www.eway.com.au/features/payments-recurring-payments).

* build online donation forms
* build online booking forms
* build simple Buy Now forms
* accept recurring payments (Australian merchants only; see [FAQ](https://wordpress.org/plugins/gravityforms-eway/faq/))

> NB: this plugin extends [Gravity Forms](http://webaware.com.au/get-gravity-forms); you still need to install and activate Gravity Forms!

= Sponsorships =

* recurring payments generously sponsored by [Castle Design](http://castledesign.com.au/)

Thanks for sponsoring new features on Gravity Forms eWAY!

= Translations =

If you'd like to help out by translating this plugin, please [sign up for an account and dig in](https://translate.wordpress.org/projects/wp-plugins/gravityforms-eway).

= Requirements =

* you need to install the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin
* you need an SSL/TLS certificate for your hosting account
* you need an account with eWAY
* this plugin uses eWAY's [Rapid API Direct Payments](https://eway.io/features/api-rapid-api) and [Recurring Payments XML API](https://www.eway.com.au/features/payments-recurring-payments), and does not support eWAY's hosted payment form

== Installation ==

1. Either install automatically through the WordPress admin, or download the .zip file, unzip to a folder, and upload the folder to your /wp-content/plugins/ directory. Read [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) in the WordPress Codex for details.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Install and activate the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin.
4. Edit the eWAY Payments settings to set your eWAY API key, API password, Customer ID, Client-Side Encryption Key, and options.

Gravity Forms will now display the Credit Card and Recurring fields under Pricing Fields when you edit a form.

= Building a Form with Credit Card Payments =

* add one or more Product fields or a Total field, or a Recurring field, so that there is something to be charged by credit card
* add a Name field (with first name and last name) if you want to see the customer's name on the eWAY transaction; the first name field will be sent to eWAY
* add an Email field and an Address field if you want to see them on your eWAY transaction; the first Email field and first Address field on the form will be sent to eWAY
* add a Credit Card field; if you have a multi-page form, this must be on the last page so that all other form validations occur first
* add a confirmation message to the form indicating that payment was successful; the form will not complete if payment was not successful, and will display an error message in the Credit Card field

**NB**: you should always test your gateway first by using eWAY's test server. To do this, select Use Sandbox in the eWAY Payments settings. When you go to pay, use the special test card number 4444333322221111. This allows you to make as many test payments as you like, without billing a real credit card.

== Frequently Asked Questions ==

= What is eWAY? =

eWAY is a leading provider of online payments solutions with a presence in Australia, New Zealand, the UK, and Asia. This plugin integrates with eWAY so that your website can safely accept credit card payments.

= Will this plugin work without installing Gravity Forms? =

No. This plugin integrates eWAY with Gravity Forms so that you can add online payments to your forms. You must purchase and install a copy of the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin too.

= Can I use eWAY outside of Australia? =

Yes, for standard card payments. See the [eWAY website](https://eway.io/) for details.

Recurring Payments is only available for Australian merchants. PreAuth is only available for Australian, Singapore, Malaysian & Hong Kong merchants.

= Do I need an SSL/TLS certificate for my website? =

Yes. This plugin uses the Direction Connection method to process transactions, so you must have HTTPS encryption for your website.

= What's the difference between the Capture and Authorize payment methods? =

Capture charges the customer's credit card immediately. This is the default payment method, and is the method most websites will use for credit card payments.

Authorize checks to see that the transaction would be approved, but does not process it. eWAY calls this method [PreAuth](https://eway.io/features/payments-pre-auth) (or Stored Payments in the old XML API). Once the transaction has been authorized, you can complete it manually in your MYeWAY console. You cannot complete PreAuth transactions from WordPress/Gravity Forms.

You need to add your eWAY API key and password to see PreAuth transactions in the sandbox, so that the Rapid API is used. The old Stored Payments XML API does not have a sandbox.

NB: PreAuth is currently only available for Australian, Singapore, Malaysian & Hong Kong merchants.

= Do I need to set the Client-Side Encryption Key? =

Client-Side Encryption is required for websites that are not certified PCI compliant. It encrypts sensitive credit card details in the browser, so that only eWAY can see them. All websites are encouraged to set the Client-Side Encryption Key for improved security of credit card details.

If you get the following error, you *must* add your Client-Side Encryption key:

> V6111: Unauthorised API Access, Account Not PCI Certified

You will find your Client-Side Encryption key in MYeWAY where you created your API key and password. Copy it from MYeWAY and paste into the eWAY Payments settings page.

= Where has the credit card type gone? =

Gravity Forms normally logs the card type with a partial card number when you have a credit card form. With Client-Side Encryption, Gravity Forms no longer sees the credit card number so it cannot detect the card type. When that happens, the card type is listed simply as "Card".

You can still see the card type and partial card number in MYeWAY transaction details.

= What is Beagle? =

[Beagle](https://eway.io/features/antifraud-beagle-lite) is a service from eWAY that provides fraud protection for your transactions. It uses information about the purchaser to suggest whether there is a risk of fraud. You must configure Beagle rules in your MYeWAY console before enabling Beagle in this plugin.

**NB**: Beagle fraud detection requires an address for each transaction. Be sure to add an Address field to your forms, and make it a required field.

= What Gravity Forms license do I need? =

Any Gravity Forms license will do. You can use this plugin with a Personal, Business or Developer license.

= Where do I find the eWAY transaction number? =

Successful transaction details including the eWAY transaction number and bank authcode are shown in the Info box when you view the details of a form entry in the WordPress admin.

Recurring payments don't get a transaction number when the payment is established, so only the payment status and date are recorded.

= How do I add a confirmed payment amount and transaction number to my Gravity Forms notification emails? =

Browse to your Gravity Form, select [Notifications](https://www.gravityhelp.com/documentation/article/configuring-notifications-in-gravity-forms/) and use the Insert Merge Tag dropdown (Payment Amount, Transaction Number and AuthCode will appear under Custom at the very bottom of the dropdown list).

= Why is the amount paid bigger than the form total when sandbox is enabled? =

When the sandbox is enabled, the payment amount is rounded up by default, because the eWAY sandbox server can return different error codes when the amount has cents. This can be a useful feature for testing how your website displays errors, but you normally don't want it when testing a payment form.

= Why do I get an error "This page is unsecured"? =

When your form has a Credit Card field, it accepts very sensitive details from your customers and these must be encrypted. You must have an SSL/TLS certificate installed on your website, and your page must be accessed via HTTPS (i.e. the page address must start with "https:"). You can force a page with a credit card form to be accessed via HTTPS by ticking Force SSL on the [Credit Card field advanced settings page](https://www.gravityhelp.com/documentation/article/credit-card-field/#advanced); see [screenshots](https://wordpress.org/plugins/gravityforms-eway/screenshots/).

= Can I do recurring payments? =

Recurring Payments is only available for Australian merchants. This feature is available thanks to the generous sponsorship of [Castle Design](http://castledesign.com.au/).

If you use [conditional logic](https://www.gravityhelp.com/documentation/article/enable-conditional-logic/) to hide/show a Product field and a Recurring Payment field, you can even let customers choose between a one-off payment and a recurring payment. Payments can be scheduled for weekly, fortnightly, monthly, quarterly, or yearly billing.

**NB**: some banks do not accept recurring payments via the eWAY Recurring Payments API. I've heard that Bendigo Bank is one that does not. Please check with eWAY and your bank for more information.

= I get an SSL error when my form attempts to connect with eWAY =

This is a common problem in local testing environments. Read how to [fix your website SSL configuration](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/).

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). All are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* libxml
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
* `gfeway_code_description` for modifying the eWAY Rapid API error messages
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

== Upgrade Notice ==

= 2.1.2 =

Fixed a bug showing a security message when trying to save a form confirmation. Version 2 is a major upgrade, requiring WordPress 4.2+, Gravity Forms 1.9+. After upgrading, please check your forms to ensure that the correct credit cards are enabled.

== Changelog ==

The full changelog can be found [on GitHub](https://github.com/webaware/gravityforms-eway/blob/master/changelog.md). Recent entries:

### 2.1.2, 2016-02-05

* fixed: "Are you sure you want to do this?" security message when trying to save a form confirmation

### 2.1.1, 2016-02-02

* added: partner ID sent to eWAY with payment requests (improved traceability)
* changed: some words on upgrade notices

### 2.1.0, 2016-02-01

* added: [support for eWAY Client Side Encryption](http://shop.webaware.com.au/gravity-forms-eway-client-side-encryption), allowing sites without PCI compliance to use Rapid 3.1 API

### 2.0.0, 2016-01-27

* changed: uses eWAY Rapid API if API key and password are set (not applicable for Recurring Payments)
* changed: minimum Gravity Forms version is now 1.9
* changed: currency is no longer limited to AUD
* changed: don't restrict credit cards, let user select; please review your forms after upgrading, and ensure that the correct credit cards are enabled in your forms
* changed: use WordPress post date format for recurring payments reported dates
* fixed: don't attempt to use real Customer ID for Recurring Payments sandbox (only 87654321 works)
* fixed: only need `gravityforms_edit_settings` to save eWAY settings
* added: strings are localised and ready for [translation](https://translate.wordpress.org/projects/wp-plugins/gravityforms-eway)!
