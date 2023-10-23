=== Razorpay Quick Payments ===
Contributors: razorpay
Tags: razorpay, payments, india, quick, simple
Requires at least: 3.0.1
Tested up to: 6.1.1
Stable tag: 1.2.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to easily sell things using Razorpay on your WordPress website.

== Description ==

This is the official "Quick Payments" plugin for Razorpay merchants. This allows
you to do the following:

1. Add a few custom variables and some markup to a page.
2. Specify the amount, name, description and other custom details as page metadata.
3. Write [RZP] wherever you want on the post and the button to show up.
4. The plugin takes over and completes the payment.

This makes use of the Razorpay Orders API, and the flow is the follows:

1. The plugin parses the page before it is rendered
2. Inserts its javascript/css/html if it finds the relevant data and markup
3. A click on the button creates an "order" using Ajax/Razorpay API
4. The order ID is passed to checkout with auto-capture enabled
5. The payment is completed there itself and the customer is informed

== Customization ==

For this plugin to work correctly, please mention the following items as page metadata (using Screen Options for >4.8) -> Custom Fields:

1. 'name' of the product.
2. 'description' of the product.
3. 'amount' with a minimum of 1 rupee.

== Changelog ==

= 1.2.9 =
* bug fix for non logged-in user

= 1.2.8 =
* Updated Razorpay SDK

= 1.2.7 =
* Tested up to WordPress 6.1.1

= 1.2.6 =
* Bug fix for script.

= 1.2.5 =
* Bug fix for payment pop up in Wordpress 6.0.

= 1.2.4 =
* Bug fix response data.

= 1.2.2 =
* Bug fix

= 1.2.1 =
* Bug fix
* Tested upto WordPress 5.5

= 1.2.0 =
* Added Callback Url support for in app browser
* Tested upto WordPress 5.4.2

= 1.1.0 =
* Added metadata information.
* Updates Razorpay SDK
* Tested upto WordPress 5.3.2

