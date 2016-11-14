<?php

/**
 * Plugin Name: Razorpay Quick Payments
 * Plugin URI: https://github.com/razorpay/razorpay-quick-payments
 * Description: Quick Payments for Wordpress, by Razorpay.
 * Version: 1.0.0
 * Author: Team Razorpay
 * Author URI: https://razorpay.com/about/
 * License: GPL2
 */

require_once __DIR__.'/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

require_once __DIR__.'/includes/razorpay-settings.php';

add_action('plugins_loaded', 'wordpressRazorpayInit', 0); // not sure if this is the right hook

function wordpressRazorpayInit()
{
    // Adding constants 
    if (!defined('RZP_BASE_NAME'))
    {
        define('RZP_BASE_NAME', plugin_basename(__FILE__));
    }

    if (!defined('RZP_REDIRECT_URL'))
    {
        // admin-post.php is a file that contains methods for us to process HTTP requests
        define('RZP_REDIRECT_URL', esc_url( admin_url('admin-post.php') ));
    }

    // The main plug in class
    class WP_Razorpay
    {
        public function __construct()
        {
            $this->id = 'razorpay';
            $this->method = 'Razorpay';
            $this->icon = plugins_url('images/logo.png', __FILE__);
            $this->has_fields = false;

            // initializing our object with all the setting variables
            $this->title = get_option('title_field');
            $this->description = get_option('description_field');
            $this->keyID = get_option('key_id_field');
            $this->keySecret = get_option('key_secret_field');
            $this->paymentAction = get_option('payment_action_field');

            // The checkout function is released when the pay now button is clicked
            $this->liveurl = 'https://checkout.razorpay.com/v1/checkout.js';

            $this->message = "";

            // Creates the settings page
            $settings = new RZP_Settings();

            // Creates a customizable tag for us to place our pay button anywhere using [RZP]
            add_shortcode('RZP', array($this, 'wordpressRazorpay'));
            // Order is created before response is checked, and is done by giving a lower priority
            add_action('init', array($this, 'razorpayOrderCreationResponse'),9);
            // check_razorpay_response is called when form data is sent to admin-post.php
            add_action('init', array($this, 'wpCheckRazorpayResponse'),10);
            // Adding links on the plugin page for docs, support and settings
            add_filter('plugin_action_links_' . RZP_BASE_NAME, array($this, 'razorpayPluginLinks'));
        }

        /**
         * Creating the settings link from the plug ins page
        **/
        function razorpayPluginLinks($links)
        {
            $pluginLinks = array(
                            'settings' => '<a href="'. esc_url(admin_url('admin.php?page=razorpay')) .'">Settings</a>',
                            'docs' => '<a href="https://github.com/razorpay/razorpay-quick-payments">Docs</a>',
                            'support' => '<a href="https://razorpay.com/contact/">Support</a>'
                        );

            $links = array_merge($links, $pluginLinks);

            return $links;
        }

        /**
         * This method is used to generate the pay button using wordpress shortcode [RZP]
        **/
        function wordpressRazorpay()
        {
            $html = $this->generateRazorpayOrderForm();
            return $html;
        }

        /**
         * Generates the order form
        **/
        function generateRazorpayOrderForm()
        {
            $pageID = get_the_ID();

            $amount = (int)(get_post_meta($pageID, 'amount')[0])*100;

            if (isset($this->keyID) && isset($this->keySecret) && $amount!=null)
            {
                $buttonHtml = file_get_contents(__DIR__.'/frontend/checkout.phtml');

                // Replacing placeholders in the HTML with PHP variables for the form to be handled correctly
                $keys = array("#liveurl#", "#redirectUrl#", "#amount#", "#pageID#");
                $values = array($this->liveurl, RZP_REDIRECT_URL, $amount, $pageID);

                $html = str_replace($keys, $values, $buttonHtml);

                return $html;
            }

            return null;
        }


        function razorpayOrderCreationResponse()
        {
            if (!empty($_GET['page_id']))
            {
                // Random order ID
                $orderID = mt_rand(0, mt_getrandmax());

                // Create a custom field and call it 'amount', and assign the value in paise
                $pageID = $_GET['page_id'];
                $amount = (int)(get_post_meta($pageID, 'amount')[0])*100;

                $productinfo = $this->getProductDecription($pageID);

                $name = $this->getProductName($pageID);

                $api = new Api($this->keyID, $this->keySecret);

                // Calls the helper function to create order data
                $data = $this->getOrderCreationData($orderID, $amount);

                $razorpayOrder = $api->order->create($data);

                // Stores the data as a cached variable temporarily
                set_transient('razorpay_order_id', $razorpayOrder['id']);

                // Have to figure this out for a general case
                $razorpayArgs = array(
                  'key' => $this->keyID,
                  'name' => $name,
                  'amount' => $amount,
                  'currency' => 'INR',
                  'description' => $productinfo,
                  'order_id' => $razorpayOrder['id'] //-------> Add this to the json later
                );

                $json = json_encode($razorpayArgs);

                echo $json;
            }
        }


        function getProductName($pageID)
        {
            // Set custom field on page called 'name' to name of the product or whatever you like
            if (!is_null(get_post_meta($pageID, 'name')))
            {
                $name = get_post_meta($pageID, 'name')[0];
            }

            // If name isn't set, default is the title of the page
            else
            {
                $name = get_bloginfo('name');
            }

            return $name;
        }

        function getProductDecription($pageID)
        {
            // Set custom field on page called 'name' to name of the product or whatever you like
            if (!is_null(get_post_meta($pageID, 'description')))
            {
                $description = get_post_meta($pageID, 'description')[0];
            }

            // If name isn't set, default is the title of the page
            else
            {
                $description = get_the_title($pageID);
            }

            return $description;
        }

        /**
         * Creates orders API data
        **/
        function getOrderCreationData($orderID, $amount)
        {
            $data = $this->getDefaultOrderCreationData($orderID, $amount);

            // capture switch is dependent on the setting selected 
            $captureSwitch = [
                'authorize' =>  0,
                'capture'   =>  1
            ];

            // payment_capture is 1 if payment_action is set to capture, and 0 if it is set to authorize
            $data['payment_capture'] = $captureSwitch[$this->paymentAction];

            return $data;
        }

        protected function getDefaultOrderCreationData($orderID, $amount)
        {
            return array(
                'receipt' => $orderID,
                'amount' => $amount,
                'currency' => 'INR',
            );
        }

        /**
         * This method is used to verify the signature given by Razorpay's Order's API
         **/
        function wpCheckRazorpayResponse()
        {
            if (!empty($_POST['razorpay_payment_id']) && !empty($_POST['order_amount']))
            {
                // Transient variables can be used to store variables in cache
                $razorpayOrderID = get_transient('razorpay_order_id');

                $razorpayPaymentID = $_POST['razorpay_payment_id'];

                $keyID = $this->keyID;
                $keySecret = $this->keySecret;

                $amount = $_POST['order_amount']/100; // paise to rupees

                $success = false;
                $error = "";

                $api = new Api($keyID, $keySecret);
                $payment = $api->payment->fetch($razorpayPaymentID);

                try
                {
                    if ($this->paymentAction === 'authorize' && $amount === $payment['amount']/100)
                    {
                        $success = true;   
                    }
                    else
                    {
                        $razorpaySignature = $_POST['razorpay_signature'];

                        $signature = hash_hmac('sha256', $razorpayOrderID . '|' . $razorpayPaymentID, $keySecret);

                        if (hash_equals($signature , $razorpaySignature))
                        {
                            $success = true;
                        }

                        else
                        {
                            $success = false;
                            $error = "PAYMENT_ERROR: Payment failed";
                        }
                    }
                }
                catch (Exception $e)
                {
                    $success = false;
                    $error = 'WORDPRESS_ERROR: Request to Razorpay Failed';
                }

                if ($success === true)
                {
                    $this->message = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon."."<br><br>"."Transaction ID: $razorpayPaymentID"."<br><br>"."Order Amount: ₹$amount";
                }
                else
                {
                    $this->message = 'Thank you for shopping with us. However, the payment failed.';
                }

                echo ($this->message);
            }
        }

    }

    return new WP_Razorpay();
}
