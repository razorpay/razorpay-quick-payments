<?php

/**
 * Plugin Name: Razorpay Quick Payments
 * Plugin URI: https://github.com/razorpay/razorpay-quick-payments
 * Description: Quick Payments for Wordpress, by Razorpay
 * Version: 1.0.0
 * Author: Team Razorpay
 * Author URI: https://razorpay.com/about/
 * License: GPL2
 */

require_once __DIR__.'/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

require_once __DIR__.'/includes/razorpay-settings.php';

add_action('plugins_loaded', 'wordpress_razorpay_init', 0); // not sure if this is the right hook

function wordpress_razorpay_init()
{
    // Add a check to see if the class already exists. Good practice. 
	class WP_Razorpay
	{
		const BASE_URL = 'https://api.razorpay.com/';

        const API_VERSION = 'v1';

        const SESSION_KEY = 'razorpay_wc_order_id';

        public function __construct()
        {
        	$this->id = 'razorpay';
        	$this->method = 'Razorpay';
        	$this->icon = plugins_url('images/logo.png',__FILE__);
        	$this->has_fields = false;

        	// initializing our object with all the setting variables
        	$this->title = get_option('title_field');
        	$this->description = get_option('description_field');
        	$this->key_id = get_option('key_id_field');
        	$this->key_secret = get_option('key_secret_field');
        	$this->payment_action = get_option('payment_action_field');

        	// The checkout function is released when the pay now button is clicked
        	$this->liveurl = 'https://checkout.razorpay.com/v1/checkout.js'; 

        	$this->msg['message'] = "";
            $this->msg['class'] = "";

            // Creates the settings page
            $settings = new RZP_Settings();

            // Creates a customizable tag for us to place our pay button anywhere using [RZP]
        	add_shortcode('RZP', array($this, 'wordpress_razorpay'));
            // check_razorpay_response is called when form data is sent to admin-post.php
            add_action( 'init', array($this,'order_creation_response'),9);
            add_action( 'init', array($this,'wp_check_razorpay_response'));
        }

		/**
		 * This method is used to generate the pay button using wordpress shortcode [RZP]
		**/
	    function wordpress_razorpay()
	    {
			$html = $this->generate_order_form();

	    	return $html;
	    }

        /**
         * Generates the order form
        **/
        function generate_order_form()
        {
            // admin-post.php is a file that contains methods for us to process HTTP requests
            $redirect_url = esc_url( admin_url('admin-post.php') ); 

            $pageID = get_the_ID();

            $amount = (int)(get_post_meta($pageID,'amount')[0]);

        	$html = <<<RZP
<div>
	<button id="btn-razorpay">Pay with Razorpay</button>
</div>
<div class="modal-container">
    <div class="modal">
        <div class="close">×</div>
        <div id='response'></div>
    </div>
</div>
<style>
   .modal-container {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 99999;
        opacity: 0;
        white-space: nowrap;
        background: rgba(0, 0, 0, 0.4);
        transition: 0.25s opacity;
        -webkit-transition: 0.25s opacity;
        text-align: center;
        font-family: sans-serif;
    }
    .modal-container.shown {
        opacity: 1;
    }
    .modal-container.shown .modal {
        transform: none;
        -webkit-transform: none;
        -moz-transform: none;
        transition: 0.3s cubic-bezier(.3, 1.5, .7,1) transform, 0.25s opacity;
    }
    .modal-container:after {
        content: '';
        display: inline-block;
        vertical-align: middle;
        height: 100%;
        vertical-align: middle;
        display: inline-block;
    }
    .modal {
        text-align: left;
        background: #fff;
        padding: 30px 20px;
        white-space: normal;
        transform: translateY(30px) scale(0.9);
        transition: 0.25s ease-in;
        vertical-align: middle;
        display: inline-block;
        max-width: 500px;
        position: relative;
        border-radius: 4px;
    }
    .close {
        cursor: pointer;
        position: absolute;
        right: 0px;
        top: -4px;
        width: 36px;
        text-align: center;
        line-height: 36px;
        color: rgba(0, 0, 0, 0.6);
        border-radius: 0 4px 4px 0;
        font-size: 24px;
        transition: 0.2s;
    }
    .close:hover {
        color: #333;
    }
</style>
<script src="{$this->liveurl}"></script>

<form name='razorpayform' id="paymentform" action="$redirect_url" method="POST">
    <input type="hidden" name="merchant_order_id" value="$order_id">
    <input type="hidden" name="order_amount" value="$amount">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id"  >
    <input type="hidden" name="razorpay_signature"  id="razorpay_signature" >
</form>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<script>
    function showModal(response) {
        $('#response').html(response);
        $('html, body').css('overflow', 'hidden');
        $('.modal-container').show().prop('offsetHeight');
        $('.modal-container').addClass('shown');
    }
    function hideModal() {
        $('html, body').css('overflow', '');
        $('.modal-container').removeClass('shown');
        setTimeout(function() {
            $('.modal-container').hide();
        }, 300)
    }
    $('.close').click(hideModal);

    // global method
    function createOrder()
    {
        $.ajax({
            url: "$redirect_url?action=create_order&page_id=$pageID",
            type: 'GET',
            success: function(order) 
            {  
                rzp_order = JSON.parse(order);
                
                rzp_order.handler = function(payment)
                {
                    document.getElementById('razorpay_payment_id').value = payment.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = payment.razorpay_order_id;
                    document.getElementById('razorpay_signature').value = payment.razorpay_signature;

                    var form_data = $('form').serializeArray();
                    
                    $.ajax({
                        url: "$redirect_url", 
                        data: form_data,
                        type: 'POST',
                        success: function(response){
                            showModal(response);
                        }
                    });
                };

                // After order is created, open checkout
                openCheckout(rzp_order);
            }
        })
    }

	// global method
    function openCheckout(rzp_order) 
    {
        var razorpayCheckout = new Razorpay(rzp_order);
        razorpayCheckout.open();
    }

	document.getElementById("btn-razorpay").onclick = function()
	{
        createOrder();
	}

</script>
RZP;
			return $html;
        }


        function order_creation_response()
        {
            if (!empty($_GET['page_id']))
            {
                // Random order ID 
                $order_id = mt_rand(0,mt_getrandmax()); 

                // Create a custom field and call it 'amount', and assign the value in paise
                $pageID = $_GET['page_id'];
                $amount = (int)(get_post_meta($pageID,'amount')[0]);

                $productinfo = "Order $order_id";

                $api = new Api($this->key_id, $this->key_secret);

                $name = $this->get_product_name($pageID);

                // Calls the helper function to create order data
                $data = $this->get_order_creation_data($order_id, $amount);
                
                $razorpay_order = $api->order->create($data);

                // Stores the data as a cached variable temporarily
                set_transient('razorpay_order_id', $razorpay_order['id']);

                // Have to figure this out for a general case
                $razorpay_args = array(
                  'key' => $this->key_id,
                  'name' => $name, 
                  'amount' => $amount,
                  'currency' => 'INR',
                  'description' => $productinfo,
                  'order_id' => $razorpay_order['id'] //-------> Add this to the json later
                );

                $json = json_encode($razorpay_args);

                print_r($json);
            }
        }

        
        function get_product_name($pageID)
        {
            // Set custom field on page called 'name' to name of the product or whatever you like
            switch (!is_null(get_post_meta($pageID,'name')))
            {
                case true:
                    $name = get_post_meta($pageID,'name')[0];
                    break;          
                
                // If name isn't set, default is the title of the page
                default: 
                    $name = get_the_title($pageID);
                    break;
            }
            
            return $name;
        }

        /**
         * Creates orders API data
        **/
        function get_order_creation_data($order_id, $amount)
        {
            switch ($this->payment_action)
            {
                case 'authorize':
                    $data = array(
                      'receipt' => $order_id,
                      'amount' => $amount,
                      'currency' => 'INR',
                      'payment_capture' => 0
                    );    
                    break;

                default:
                    $data = array(
                      'receipt' => $order_id,
                      'amount' => $amount,
                      'currency' => 'INR',
                      'payment_capture' => 1
                    );
                    break;
            }

            return $data;
        }

        /**
         * This method is used to verify the signature given by Razorpay's Order's API
         **/
        function wp_check_razorpay_response()
        {	
        	if (!empty($_POST['razorpay_payment_id']))
            {
                // Transient variables can be used to store variables in cache 
                $razorpay_order_id = get_transient('razorpay_order_id');

            	$razorpay_payment_id = $_POST['razorpay_payment_id'];

            	$key_id = $this->key_id;
                $key_secret = $this->key_secret;
                $amount = $_POST['order_amount'];

                $success = false;
                $error = "";
                $captured = false;

                $api = new Api($key_id, $key_secret);

                try
                {
                    if ($this->payment_action === 'authorize')
                    {   
                        $payment = $api->payment->fetch($razorpay_payment_id);
                    }
                    else
                    {
                        $razorpay_signature = $_POST['razorpay_signature'];
                        
                        $signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);

                        if (hash_equals($signature , $razorpay_signature))
                        {
                            $captured = true;;
                        }
                    }
    
                    //Check success response
                    if ($captured)
                    {
                        $success = true;
                    }

                    else{
                        $success = false;

                        $error = "PAYMENT_ERROR = Payment failed";
                    }
                }
                catch (Exception $e)
                {
                    $success = false;
                    $error = 'WORDPRESS_ERROR: Request to Razorpay Failed';
                }

                if ($success === true)
                {
                    $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon."."<br><br>"."Order Id: $razorpay_order_id"."<br><br>"."Order Amount: $amount";
                    $this->msg['class'] = 'success';
                }
                else
                {
                    $this->msg['class'] = 'error';
                    $this->msg['message'] = 'Thank you for shopping with us. However, the payment failed.';
                }

                print_r($this->msg['message']);
            }
        }

	}

	/**
	 * Creating a new WP_Razorpay object and storing it as a $GLOBAL variable
	**/
    function create_razorpay_payment_gateway()
    {
    	return new WP_Razorpay();
    }

    $GLOBALS['razorpay'] = create_razorpay_payment_gateway();

}