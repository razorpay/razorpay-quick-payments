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

require_once __DIR__.'/razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

require_once __DIR__.'/includes/razorpay-settings.php';

session_start();

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
            add_shortcode('RZP', array($this, 'checkout'));
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
                            'docs'     => '<a href="https://github.com/razorpay/razorpay-quick-payments">Docs</a>',
                            'support'  => '<a href="https://razorpay.com/contact/">Support</a>'
                        );

            $links = array_merge($links, $pluginLinks);

            return $links;
        }

        /**
         * This method is used to generate the pay button using wordpress shortcode [RZP]
        **/
        function checkout()
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

            $metadata = get_post_meta($pageID);

            $amount = (int) ($metadata['amount'][0]) * 100;

            if (isset($this->keyID) && isset($this->keySecret) && $amount!=null)
            {
                $buttonHtml = file_get_contents(__DIR__.'/frontend/checkout.phtml');

                // Replacing placeholders in the HTML with PHP variables for the form to be handled correctly
                $keys = array("#liveurl#", "#redirectUrl#", "#pageID#");
                $values = array($this->liveurl, RZP_REDIRECT_URL, $pageID);

                $_SESSION['amount'] = $amount;

                $html = str_replace($keys, $values, $buttonHtml);

                return $html;
            }

            return null;
        }


        function razorpayOrderCreationResponse()
        {
            if (empty($_GET['page_id']) === false)
            {
                // Random order ID
                $orderID = mt_rand(0, mt_getrandmax());

                // Create a custom field and call it 'amount', and assign the value in paise
                $pageID = $_GET['page_id'];

                $metadata = get_post_meta($pageID);

                $amount = (int) ($metadata['amount'][0]) * 100;

                $productInfo = $this->getProductDecription($metadata, $pageID);

                $name = $this->getProductName($metadata);

                $api = new Api($this->keyID, $this->keySecret);

                // Calls the helper function to create order data
                $data = $this->getOrderCreationData($orderID, $amount);

                try
                {
                    $razorpayOrder = $api->order->create($data);
                }
                catch (Exception $e)
                {
                    echo 'Wordpress Error : ' . $e->getMessage();
                }

                // Stores the data as a cached variable temporarily
                $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];

                $razorpayArgs = array(
                    'key'         => $this->keyID,
                    'name'        => $name,
                    'amount'      => $amount,
                    'currency'    => 'INR',
                    'description' => $productInfo,
                    'order_id'    => $razorpayOrder['id']
                );

                $json = json_encode($razorpayArgs);

                header('Content-Type: application/json');

                echo $json;
            }
        }

        function getProductName($metadata)
        {
            // Set custom field on page called 'name' to name of the product or whatever you like
            if (isset($metadata['name'][0]) and !empty($metadata['name'][0]))
            {
                $name = $metadata['name'][0];
            }

            // If name isn't set, default is the title of the page
            else
            {
                $name = get_bloginfo('name');
            }

            return $name;
        }

        function getProductDecription($metadata, $pageId)
        {
            // Set custom field on page called 'name' to name of the product or whatever you like
            if (isset($metadata['description'][0]) and !empty($metadata['description'][0]))
            {
                $description = $metadata['description'][0];
            }

            // If name isn't set, default is the title of the page
            else
            {
                $description = get_the_title($pageId);
            }

            return $description;
        }

        /**
         * Creates orders API data
         **/
        function getOrderCreationData($orderID, $amount)
        {
            $data = array(
                'receipt'         => $orderID,
                'amount'          => $amount,
                'currency'        => 'INR',
                'payment_capture' => ($this->paymentAction === 'authorize') ? 0 : 1
            );

            return $data;
        }

        /**
         * This method is used to verify the signature given by Razorpay's Order's API
         **/
        function wpCheckRazorpayResponse()
        {
            if (empty($_POST['razorpay_payment_id']) === false)
            {
                $attributes = array(
                    'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                    'razorpay_order_id'   => $_SESSION['razorpay_order_id'],
                    'razorpay_signature'  => $_POST['razorpay_signature']
                );

                $amount = $_SESSION['amount'] / 100; // paise to rupees

                $api = new Api($this->keyID, $this->keySecret);

                $success = true;

                try
                {
                    $api->utility->verifyPaymentSignature($attributes);
                }
                catch(Exception $e)
                {
                    $success = false;
                    $error = 'Wordpress Error: ' . $e->getMessage();
                }

                if ($success === true)
                {
                    $this->message = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon."
                    . "<br><br>" . "Transaction ID:" . $attributes['razorpay_payment_id'] . "<br><br>" . "Order Amount: ₹$amount";
                }
                else
                {
                    $this->message = 'Thank you for shopping with us. However, the payment failed.\n' . $error ;
                }

                echo ($this->message);
            }
        }

    }

    return new WP_Razorpay();
}
