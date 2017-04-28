=== Razorpay Quick Payments ===
Contributors: razorpay
Tags: razorpay, payments, india, quick, simple
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to easily sell things using Razorpay on your WordPress website.

=== Description ===
```
This is the official "Quick Payments" plugin for Razorpay merchants. This allows
you to do the following:

1. Add a few custom variables and some markup to a page.
2. Specify the amount, name, description and other custom details as page metadata.
3. Write `[RZP]` wherever you want on the post and the button to show up.
4. The plugin takes over and completes the payment.

This makes use of the Razorpay Orders API, and the flow is the follows:

1. The plugin parses the page before it is rendered
2. Inserts its javascript/css/html if it finds the relevant data and markup
3. A click on the button creates an "order" using Ajax/Razorpay API
4. The order ID is passed to checkout with auto-capture enabled
5. The payment is completed there itself and the customer is informed
```

=== Instructions ===
```
Instructions on setting the plugin up: 

1. Download the plugin using git clone https://github.com/razorpay/razorpay-quick-payments.git
2. Copy over the downloaded directory into the /wp-content/plugins directory inside your root wordpress directory.
3. Go to your admin page, scroll to the top and enable custom fields using Screen Options -> Custom Fields.
4. Scroll to below the edit page section and add the following fields as custom metadata: 
    1. `name` of the product.
    2. `description` of the product.
    3. `amount` in paise, with a minimum of 1 rupee.
5. In the edit page section, type in [RZP] in any location where you'd like to add the `Pay with Razorpay` button and save the page. 
```