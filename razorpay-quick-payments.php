<?php

/**
 * Plugin Name: Razorpay Quick Payments
 * Plugin URI: https://github.com/razorpay/razorpay-quick-payments
 * Description: Quick Payments for Wordpress, by Razorpay.
 * Version: 1.3.0
 * Author: Team Razorpay
 * Author URI: https://razorpay.com/about/
 * License: GPL2
 */

require_once __DIR__.'/razorpay-php/Razorpay.php';
use Razorpay\Api\Api;

require_once __DIR__.'/includes/razorpay-settings.php';
session_start();


add_action('plugins_loaded', 'wordpressRazorpayInit', 0); // not sure if this is the right hook
add_action('admin_post_create_order','dummy');// adding dummy action to create order so that it passes the has_action check
add_action('admin_post_nopriv_create_order','dummy');
function dummy()
{
    return 1;
}

function wordpressRazorpayInit()
{
    header('Set-Cookie: ' . session_name() . '=' . session_id() . '; HttpOnly; SameSite=None; Secure; HttpOnly;');
    add_action('admin_enqueue_scripts', 'enqueueScripts' );//admin-page
    add_action('wp_enqueue_scripts', 'enqueueScripts' );//front-end
    add_action('login_enqueue_scripts', 'enqueueScripts' );//login page

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
            $this->currencyAction = get_option('currency_action_field');

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

            $amount = (int) (number_format($metadata['amount'][0] * pow(10, (int)$this->getCurrencyObject()['exponent']), 0, ".", ""));

            if (isset($this->keyID) && isset($this->keySecret) && $amount!=null)
            {
                $buttonHtml = file_get_contents(__DIR__.'/frontend/checkout.phtml');

                // Replacing placeholders in the HTML with PHP variables for the form to be handled correctly
                $keys = array("#liveurl#", "#redirectUrl#", "#pageID#");
                $values = array($this->liveurl, RZP_REDIRECT_URL, $pageID);

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

                if (empty($metadata) === true)
                {
                    $razorpayArgs['error'] = 'Post with given page ID not found';
                }
                else
                {
                    $amount = (int) (number_format($metadata['amount'][0] * pow(10, (int)$this->getCurrencyObject()['exponent']), 0, ".", ""));

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
                        $razorpayArgs['error'] = 'Wordpress Error : ' . $e->getMessage();
                    }

                    if (isset($razorpayArgs['error']) === false)
                    {

                        // Stores the data as a cached variable temporarily
                        $_SESSION['rzp_QP_order_id'] = $razorpayOrder['id'];
                        $_SESSION['rzp_QP_amount'] = $amount;
                        if( ! function_exists('get_plugin_data') ){
                            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        }

                        $mod_version = get_plugin_data(plugin_dir_path(__FILE__) . 'razorpay-quick-payments.php')['Version'];

                        $wp_version = get_bloginfo( 'version' );

                        $razorpayArgs = array(
                            'key'         => $this->keyID,
                            'name'        => $name,
                            'amount'      => $amount,
                            'currency'    => $this->currencyAction,
                            'description' => $productInfo,
                            'order_id'    => $razorpayOrder['id'],
                            'notes'       => [
                                'quick_payment_order_id' => $orderID
                            ],
                            '_'           => [
                                'integration'                   => 'Quick Payment',
                                'integration_version'           => $mod_version,
                                'integration_parent_version'    => $wp_version,
                            ],
                        );
                    }
                }

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
                'currency'        => $this->currencyAction,
                'payment_capture' => ($this->paymentAction === 'authorize') ? 0 : 1
            );

            return $data;
        }

        /**
         * This method is used to verify the signature given by Razorpay's Order's API
         **/
        function wpCheckRazorpayResponse()
        {
            $attributes = $this->getPostAttributes();

            if (!empty($attributes))
            {
                $amount = $_SESSION['rzp_QP_amount'] / pow(10, (int)$this->getCurrencyObject()['exponent']); // paise to rupees

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
                    . "<br><br>" . "Transaction ID : " . esc_html($attributes['razorpay_payment_id']) . "<br><br>" . "Order Amount: " . get_option('currency_action_field') . " " . $amount;
                }
                else
                {
                    $this->message = 'Thank you for shopping with us. However, the payment failed.\n' . $error ;
                }

                // Appending script tags to handle if output is not returned to ajax response
                $this->message = "<script>var displayRzpModal = '" . $this->message . "';</script>";

                echo ($this->message);
            }
            session_write_close();
        }

        function getCurrencyObject()
        {
            $supported_currencies = json_decode(file_get_contents(__DIR__ . "/supported-currencies.json"), true)['supported-currencies'];

            $currency_code = get_option('currency_action_field');

            $currency_object = null;

            foreach($supported_currencies as $supported_currency)
            {
                if ($supported_currency['iso_code'] === $currency_code)
                {
                    $currency_object = $supported_currency;
                    break;
                }
            }
            
            return $currency_object;
        }

        protected function getPostAttributes()
        {
            if (isset($_REQUEST['rzp_QP_form_submit']) and isset($_REQUEST['razorpay_payment_id']))
            {
                return array(
                    'razorpay_payment_id' => sanitize_text_field($_POST['razorpay_payment_id']),
                    'razorpay_order_id'   => $_SESSION['rzp_QP_order_id'],
                    'razorpay_signature'  => sanitize_text_field($_POST['razorpay_signature'])
                );
            }

            return array();
        }
    }

    return new WP_Razorpay();
}
function enqueueScripts() {
    wp_enqueue_script('jquery');
}
