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
        	$this->payment_action = get_option('payment_action_field');;

        	// The checkout function is released when the pay now button is clicked
        	$this->liveurl = 'https://checkout.razorpay.com/v1/checkout.js'; 

        	$this->msg['message'] = "";
            $this->msg['class'] = "";

            // Creates the settings page
            $settings = new RZP_Settings();

            // Creates a customizable tag for us to place our pay button anywhere using [RZP]
        	add_shortcode('RZP', array($this, 'wordpress_razorpay'));
            // check_razorpay_response is called when form data is sent to admin-post.php
            add_action( 'admin_post', array($this,'check_razorpay_response' ));
        }

		/**
		 * This method is used to generate the pay button using wordpress shortcode [RZP]
		**/
	    function wordpress_razorpay()
	    {
	    	// admin-post.php is a file that contains methods for us to process HTTP requests
	    	$redirect_url = esc_url( admin_url('admin-post.php') ); 
	 
	 		// Random order ID for now
	    	$order_id = mt_rand(); // have to generate an order ID that is unique to every order. Why not databases? 

	    	add_post_meta(get_the_ID(),'amount','5000'); // In the docs they need to add the amount in paise

	    	$amount = (int)(get_post_meta(get_the_ID(),'amount')[0]);

	    	delete_post_meta(get_the_ID(),'amount'); // Doing it now just to test

	    	$productinfo = "Order $order_id";

            $api = new Api($this->key_id, $this->key_secret);

            // Calls the helper function to create order data
            $data = $this->get_order_creation_data($order_id, $amount);
            
            $razorpay_order = $api->order->create($data);

            // Stores the data as a cached variable temporarily
            set_transient('razorpay_order_id', $razorpay_order['id']);

            // Have to figure this out for a general case
            $razorpay_args = array(
              'key' => $this->key_id,
              'name' => "Razorpay Test", // add to config - settings page
              'amount' => $amount,
              'currency' => 'INR',
              'description' => $productinfo,
              'order_id' => $razorpay_order['id']
            );

            $json = json_encode($razorpay_args);

			$html = $this->generate_order_form($redirect_url, $json, $order_id);

	    	return $html;
	    }

	    /**
         * Creates orders API data
        **/
        function get_order_creation_data($order_id, $amount)
        {
            switch($this->payment_action)
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
         * Generates the order form
        **/
        function generate_order_form($redirect_url, $json, $order_id)
        {
        	$html = <<<RZP
<div>
	<button id="btn-razorpay">Pay using Razorpay</button>
</div>
<script src="{$this->liveurl}"></script>
<script>
    var data = $json;
</script>
<form name='razorpayform' action="$redirect_url" method="POST">
    <input type="hidden" name="merchant_order_id" value="$order_id">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id"  >
    <input type="hidden" name="razorpay_signature"  id="razorpay_signature" >
</form>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

<script>

	data.handler = function(payment)
	{
		document.getElementById('razorpay_payment_id').value = payment.razorpay_payment_id;
		document.getElementById('razorpay_order_id').value = payment.razorpay_order_id;
		document.getElementById('razorpay_signature').value = payment.razorpay_signature;

		var form = new FormData();

		var form_data = $('form').serializeArray();
	    
	    $.each(form_data,function(key,input){
	        form.append(input.name,input.value);
	    });
	    
	    $.ajax({
	        url: "$redirect_url", // fix multipart
	        data: form,
	        contentType: false,
	        processData: false,
	        type: 'POST',
	        success: function(data){
	            alert(data);
	        }
	    });
    };

	var razorpayCheckout = new Razorpay(data);

	// global method
    function openCheckout() 
    {
      	razorpayCheckout.open();
    }

	document.getElementById("btn-razorpay").onclick = function()
	{
		openCheckout();
	}

</script>
RZP;
			return $html;
        }

        /**
         * This method is used to verify the signature given by Razorpay's Order's API
         **/
        function check_razorpay_response()
        {
        	// Transient variables can be used to store variables in cache 
        	$razorpay_order_id = get_transient('razorpay_order_id');	

        	if (!empty($_POST['razorpay_payment_id']))
            {
            	$razorpay_payment_id = $_POST['razorpay_payment_id'];

            	$key_id = $this->key_id;
                $key_secret = $this->key_secret;
                $amount = 5000;

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
                    $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon. \nOrder Id: $razorpay_order_id";
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