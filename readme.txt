# Gravity Forms Eway
Contributors: webaware
Plugin Name: Gravity Forms Eway
Plugin URI: https://shop.webaware.com.au/downloads/gravity-forms-eway/
Author URI: https://shop.webaware.com.au/
Donate link: https://shop.webaware.com.au/donations/?donation_for=Gravity+Forms+Eway
Tags: gravity forms, eway, donations, payment gateway, ecommerce
Requires at least: 4.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily create online payment forms with Gravity Forms and Eway.

## Description

Gravity Forms Eway integrates the [Eway credit card payment gateway](https://eway.io/) with [Gravity Forms](https://webaware.com.au/get-gravity-forms) advanced form builder, using Eway's [Rapid API Direct Payments](https://eway.io/features/api-rapid-api) and [Recurring Payments XML API](https://www.eway.com.au/features/payments-recurring-payments).

* build online donation forms
* build online booking forms
* build simple Buy Now forms
* accept recurring payments (Australian merchants only; see [FAQ](https://wordpress.org/plugins/gravityforms-eway/faq/))

> NB: this plugin extends [Gravity Forms](https://webaware.com.au/get-gravity-forms); you still need to install and activate Gravity Forms!

[Go Pro](https://gfeway.webaware.net.au/) and access these additional features:

* record entry even when transaction fails
* use Responsive Shared Page, no need for SSL certificate on standard payments
* create complex forms with feeds mapping fields to Eway
* create token payment customers
* remember customer cards using tokens
* send shipping addresses to Eway
* use sophisticated conditional logic
* mix multiple currencies on one website
* mix multiple Eway accounts on one website

### Sponsorships

* recurring payments generously sponsored by [Castle Design](https://www.castledesign.com.au/)

Thanks for sponsoring new features on Gravity Forms Eway!

### Translations

If you'd like to help out by translating this plugin, please [sign up for an account and dig in](https://translate.wordpress.org/projects/wp-plugins/gravityforms-eway).

### Requirements

* you need to install the [Gravity Forms](https://webaware.com.au/get-gravity-forms) plugin
* you need an SSL/TLS certificate for your hosting account
* you need an account with Eway
* this plugin uses Eway's [Rapid API Direct Payments](https://go.eway.io/s/article/Direct-Payments-API-Snippets) and [Recurring Payments XML API](https://go.eway.io/s/article/How-do-I-setup-a-Recurring-Payment), and does not support Eway's Responsive Shared Page (available with [Pro](https://gfeway.webaware.net.au/))

### Privacy

Information gathered for processing a credit card transaction is transmitted to Eway for processing, and in turn, Eway passes that information on to your bank. Please review [Eway's Privacy Policy](https://www.eway.com.au/privacy-policy/) for information about how that affects your website's privacy policy. By using this plugin, you are agreeing to the terms of use for Eway.

## Installation

1. Either install automatically through the WordPress admin, or download the .zip file, unzip to a folder, and upload the folder to your /wp-content/plugins/ directory. Read [Installing Plugins](https://wordpress.org/documentation/article/manage-plugins/) in the WordPress documentation for details.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Install and activate the [Gravity Forms](https://webaware.com.au/get-gravity-forms) plugin.
4. Edit the Eway Payments settings to set your Eway API key, API password, Customer ID, Client-Side Encryption Key, and options.

Gravity Forms will now display the Credit Card and Recurring fields under Pricing Fields when you edit a form.

### Building a Form with Credit Card Payments

* add one or more Product fields or a Total field, or a Recurring field, so that there is something to be charged by credit card
* add a Name field (with first name and last name) if you want to see the customer's name on the Eway transaction; the first name field will be sent to Eway
* add an Email field and an Address field if you want to see them on your Eway transaction; the first Email field and first Address field on the form will be sent to Eway
* add a Credit Card field; if you have a multi-page form, this must be on the last page so that all other form validations occur first
* add a confirmation message to the form indicating that payment was successful; the form will not complete if payment was not successful, and will display an error message in the Credit Card field

**NB**: you should always test your gateway first by using Eway's test server. To do this, select Use Sandbox in the Eway Payments settings. When you go to pay, use the special test card number 4444333322221111. This allows you to make as many test payments as you like, without billing a real credit card.

## Frequently Asked Questions

### What is Eway?

Eway is a leading provider of online payments solutions with a presence in Australia, New Zealand, and Asia. This plugin integrates with Eway so that your website can safely accept credit card payments.

### Will this plugin work without installing Gravity Forms?

No. This plugin integrates Eway with Gravity Forms so that you can add online payments to your forms. You must purchase and install a copy of the [Gravity Forms](https://webaware.com.au/get-gravity-forms) plugin too.

### Can I use Eway outside of Australia?

Yes, for standard card payments. See the [Eway website](https://eway.io/) for details.

Recurring Payments is only available for Australian merchants. PreAuth is only available for Australian, Singapore, Malaysian, & Hong Kong merchants.

### Do I need an SSL/TLS certificate for my website?

Yes. This plugin uses the Direction Connection method to process transactions, so you must have HTTPS encryption for your website.

[Go Pro](https://gfeway.webaware.net.au/) to use Eway's Responsive Shared Page without requiring an SSL/TLS certificate on your website with standard payments. Recurring payments requires an SSL/TLS certificate with the Free and the Pro add-ons.

### What's the difference between the Capture and Authorize payment methods?

Capture charges the customer's credit card immediately. This is the default payment method, and is the method most websites will use for credit card payments.

Authorize checks to see that the transaction would be approved, but does not process it. Eway calls this method PreAuth (or Stored Payments in the old XML API). Once the transaction has been authorized, you can complete it manually in your MyEway console. You cannot complete PreAuth transactions from WordPress/Gravity Forms.

You need to add your Eway API key and password to see PreAuth transactions in the sandbox, so that the Rapid API is used. The old Stored Payments XML API does not have a sandbox.

**NB: PreAuth is currently only available for Australian, Singapore, Malaysian, & Hong Kong merchants. Do not select Authorize if you are a New Zealand merchant!**

### Do I need to set the Client-Side Encryption Key?

Client-Side Encryption is required for websites that are not PCI certified. It encrypts sensitive credit card details in the browser, so that only Eway can see them. All websites are encouraged to set the Client-Side Encryption Key for improved security of credit card details.

If you get the following error, you *must* add your Client-Side Encryption key:

> V6111: Unauthorized API Access, Account Not PCI Certified

You will find your Client-Side Encryption key in MyEway where you created your API key and password. Copy it from MyEway and paste into the Eway Payments settings page.

### Why do I get an "Invalid TransactionType" error?

> V6010: Invalid TransactionType, account not certified for eCome only MOTO or Recurring available

It probably means you need to set your Client-Side Encryption key; see above. It can also indicate that your website has JavaScript errors, which can prevent Client-Side Encryption from working. Check for errors in your browser's developer console.

If your website is PCI Certified and you don't want to use Client-Side Encryption for some reason, then you will still get this error in the sandbox until you enable PCI for Direct Connections. See [screenshots](https://wordpress.org/plugins/gravityforms-eway/screenshots/)

Settings > Sandbox > Direction Connection > PCI

### Where has the credit card type gone?

Gravity Forms normally logs the card type with a partial card number when you have a credit card form. With Client-Side Encryption, Gravity Forms no longer sees the credit card number so it cannot detect the card type. When that happens, the card type is listed simply as "Card".

You can still see the card type and partial card number in MyEway transaction details.

### What is Beagle Lite?

[Beagle Lite](https://eway.io/features/antifraud-beagle-lite) is a service from Eway that provides fraud protection for your transactions. It uses information about the purchaser to suggest whether there is a risk of fraud. Configure Beagle Lite rules in your MyEway console.

**NB**: Beagle Lite fraud detection requires an address for each transaction. Be sure to add an Address field to your forms, and make it a required field. The minimum address part required is the Country, so you can just enable that subfield if you don't need a full address.

### What Gravity Forms license do I need?

Any Gravity Forms license will do. You can use this plugin with a Personal, Business or Developer license.

### Where do I find the Eway transaction number?

Successful transaction details including the Eway transaction number and bank authcode are shown in the Info box when you view the details of a form entry in the WordPress admin.

Recurring payments don't get a transaction number when the payment is established, so only the payment status and date are recorded.

### How do I add a confirmed payment amount and transaction number to my Gravity Forms notification emails?

Browse to your Gravity Form, select [Notifications](https://docs.gravityforms.com/configuring-notifications-in-gravity-forms/) and use the Insert Merge Tag dropdown (Payment Amount, Transaction Number and AuthCode will appear under Custom at the very bottom of the dropdown list).

### Why is the amount paid bigger than the form total when sandbox is enabled?

When the sandbox is enabled, the payment amount is rounded up by default, because the Eway sandbox server can return different error codes when the amount has cents. This can be a useful feature for testing how your website displays errors, but you normally don't want it when testing a payment form.

### Why do I get a "This page is unsecured" error?

When your form has a Credit Card field, it accepts very sensitive details from your customers and these must be encrypted. You must have an SSL/TLS certificate installed on your website, and your page must be accessed via HTTPS (i.e. the page address must start with "https:"). You can force a page with a credit card form to be accessed via HTTPS by ticking Force SSL on the [Credit Card field advanced settings page](https://docs.gravityforms.com/credit-card-field/); see [screenshots](https://wordpress.org/plugins/gravityforms-eway/screenshots/).

### Can I do recurring payments?

Recurring Payments is only available for Australian merchants. This feature is available thanks to the generous sponsorship of [Castle Design](http://castledesign.com.au/).

If you use [conditional logic](https://docs.gravityforms.com/enable-conditional-logic/) to hide/show a Product field and a Recurring Payment field, you can even let customers choose between a one-off payment and a recurring payment. Payments can be scheduled for weekly, fortnightly, monthly, quarterly, or yearly billing.

**NB**: some banks do not accept recurring payments via the Eway Recurring Payments API. I've heard that Bendigo Bank is one that does not. Please check with Eway and your bank for more information.

### I get an SSL error when my form attempts to connect with Eway

This is a common problem in local testing environments. Read how to [fix your website SSL configuration](https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/).

### Are there any filter hooks?

Developers can use these [filter hooks to modify some Eway invoice properties](https://gfeway.webaware.net.au/faq/what-filters-does-the-plugin-have/).

## Screenshots

1. Options screen
2. A sample donation form
3. The sample donation form as it appears on a page
4. How a credit card validation error appears
5. A successful entry in Gravity Forms admin
6. Example with recurring payments
7. Forcing SSL on a page with a credit card form
8. Enabling PCI for Direct Connections in the Sandbox

## Upgrade Notice

### 2.6.1

fixed a missing template that lists any missing required PHP modules

## Changelog

> Get [Gravity Forms Eway Pro](https://gfeway.webaware.net.au/) for extra features, greater flexibility

The full changelog for Gravity Forms Eway can be found [on GitHub](https://github.com/webaware/gravityforms-eway/blob/master/changelog.md). Recent entries:

### 2.6.0

Released 2024-07-14

* fixes: PHP notice on deprecated creation of dynamic properties in PHP 8.1+
* changed: requires minimum PHP version 7.4 (recommend version 8.2 or higher)

### 2.6.1, 2024-10-15

* fixed: missing a template that lists any missing required PHP modules
