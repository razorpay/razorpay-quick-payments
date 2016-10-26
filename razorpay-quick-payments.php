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

            // check_razorpay_response is called when form data is sent to admin-post.php
            add_action( 'admin_post', array($this,'check_razorpay_response' ));
            // Creates a customizable tag for us to place our pay button anywhere using [RZP]
        	add_shortcode('RZP', array($this, 'wordpress_razorpay'));
        	// Creates a new menu page for razorpay's settings
        	add_action('admin_menu', array($this, 'wordpress_razorpay_admin_setup'));
        	// Initializes display options when admin page is initialized
        	add_action('admin_init', array($this, 'display_options'));
        }

        /**
		 * Creating up the settings page for the plug-in on the menu page
		**/
		function wordpress_razorpay_admin_setup()
		{
			add_menu_page('Razorpay Payment Gateway', 'Razorpay', 'manage_options', 'razorpay', array($this,'admin_options'));
			//add_menu_page('razorpay', 'Razorpay Orders', 'Orders', 'manage_options', 'orders', array($this,'orders_options'));
		}

        /**
		 * Generates admin page options using Settings API
		**/
        public function admin_options()
        {
        	?>
            	<div class="wrap">
        			<h2><?php print $GLOBALS['title']; ?></h2>
        			<form action="options.php" method="POST">
			            <?php 
			            	settings_fields('razorpay_fields');
			            	do_settings_sections('razorpay_sections'); 
			            	submit_button(); 
			            ?>
			        </form>
			    </div>
            <?php
        }

        /**
		 * Generates orders page for Razorpay plug in - Gotta change this
		**/
        public function orders_options()
        {
        	?>
            	<div class="wrap">
        			<h2><?php print $GLOBALS['title']; ?></h2>
        			<form action="options.php" method="POST">
			            <?php 
			            	settings_fields('razorpay_fields');
			            	do_settings_sections('razorpay_sections'); 
			            	submit_button(); 
			            ?>
			        </form>
			    </div>
            <?php
        }

        /**
		 * Uses Settings API to create fields
		**/
        function display_options()
        {
        	add_settings_section('razorpay_fields','Edit Settings', array($this,'display_header'), 'razorpay_sections');

        	// Enabled/Disabled Field
        	add_settings_field('enabled_field','Enabled/Disabled', array($this,'display_enable'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields','enabled_field');

        	add_settings_field('title_field','Title', array($this,'display_title'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields','title_field');

        	add_settings_field('description_field','Description', array($this,'display_description'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields', 'description_field');

        	add_settings_field('key_id_field','Key_id', array($this,'display_key_id'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields', 'key_id_field');

        	add_settings_field('key_secret_field','Key_secret', array($this,'display_key_secret'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields', 'key_secret_field');

        	add_settings_field('payment_action_field','Payment_action', array($this,'display_payment_action'),'razorpay_sections','razorpay_fields');
        	register_setting('razorpay_fields','payment_action_field');
        }

        /**
		 * Settings page header
		**/        
        function display_header()
        {
        	echo '<p>' . 'Razorpay is an online payment gateway for India with transparent pricing, seamless integration and great support' . '</p>';
        }

        /**
		 * Enable field of settings page
		**/
        function display_enable(){
        	?>
        		<input type="checkbox" name="enabled_field" id="enable" value="<?php echo get_option('enabled_field'); ?>" checked/>
        		<label for ="enable">Enable Razorpay Payment Module.</label>
        	<?php
        }

        /**
		 * Title field of settings page
		**/
        function display_title(){
        	
        	$default = get_option('title_field'); 
						
			if ($default == "")
			{
				$default = "Credit Card/Debit Card/NetBanking";
			}

        	?>
        		<input type="text" name="title_field" id="title" size="35" value="<?php echo $default;?>" /><br>
        		<label for ="title">This controls the title which the user sees during checkout.</label>
        	<?php
        }

        /**
		 * Description field of settings page
		**/
        function display_description(){

        	$default = get_option('description_field'); 
						
			if ($default == "")
			{
				$default = "Pay securely by Credit or Debit card or internet banking through Razorpay";
			}

        	?>
        		<input type="text" name="description_field" id="description" size="35" value="<?php echo $default; ?>" /><br>
        		<label for ="description">This controls the display which the user sees during checkout.</label>
        	<?php
        }

        /**
		 * Key ID field of settings page
		**/
        function display_key_id(){
        	?>
        		<input type="text" name="key_id_field" id="key_id" size="35" value="<?php echo get_option('key_id_field'); ?>" /><br>
        		<label for ="key_id">The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test or live for test or live mode.</label>
        	<?php
        }

        /**
		 * Key secret field of settings page
		**/
        function display_key_secret(){
        	?>
        		<input type="text" name="key_secret_field" id="key_secret" size="35" value="<?php echo get_option('key_secret_field'); ?>" /><br>
        		<label for ="key_secret">The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test or live for test or live mode.</label>
        	<?php
        }

        /**
		 * Payment action field of settings page
		**/
        function display_payment_action(){
        	?>
        		<select name="payment_action_field" id="payment_action" value="<?php echo get_option('payment_action_field'); ?>" />
        			<option>Authorize and Capture</option>
        			<option>Authorize</option>
        		</select>
        		<br>
        		<label for ="payment_action">Payment action when order is compelete.</label>
        	<?php
        }

		/**
		 * This method is used to generate the pay button using wordpress shortcode [RZP]
		**/
	    function wordpress_razorpay()
	    {
	    	// admin-post.php is a file that contains methods for us to process HTTP requests
	    	$redirect_url = esc_url( admin_url('admin-post.php') ); 
	 
	    	$order_id = 39;

	    	$productinfo = "Order $order_id";

            $api = new Api($this->key_id, $this->key_secret);

            // Calls the helper function to create order data
            $data = $this->get_order_creation_data($order_id);
            
            $razorpay_order = $api->order->create($data);

            // Have to figure this out for a general case
            $razorpay_args = array(
              'key' => $this->key_id,
              'name' => "Razorpay Test",
              'amount' => 5000,
              'currency' => 'INR',
              'description' => $productinfo,
              'prefill' => array(
                'name' => "Mayank"." "."Amencherla",
                'email' => "mayank.amencherla@razorpay.com",
                'contact' => "+917676998014"
              ),
              'order_id' => $razorpay_order['id']
            );

            $json = json_encode($razorpay_args);

			$html = $this->generate_order_form($redirect_url, $json, $order_id);

			$_SESSION['razorpay_order_id'] = $razorpay_order['id']; // session already sent by this time

	    	return $html;
	    }

	    /**
         * Creates orders API data
        **/
        function get_order_creation_data($order_id)
        {
            switch($this->payment_action)
            {
                case 'authorize':
                    $data = array(
                      'receipt' => $order_id,
                      'amount' => 5000,
              		  'currency' => 'INR',
                      'payment_capture' => 0
                    );    
                    break;

                default:
                    $data = array(
                      'receipt' => $order_id,
                      'amount' => 5000,
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
	            console.log(data);
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
         * Check for valid razorpay server callback, also need to call this exactly after payment is complete from the previous page
         * Basically when razorpayform is submitted. Currently not getting called at the right time. 
         **/
        function check_razorpay_response()
        {
        	// Getting variables from wordpress sessions - but this doesn't work. Have to store this differently
        	$razorpay_order_id = $_SESSION['razorpay_order_id'];	

        	var_dump($razorpay_order_id);
        	var_dump($_POST);

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

                var_dump($success);

                if ($success === true)
                {
                    $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be processing your order soon. Order Id: $order_id";
                    $this->msg['class'] = 'success';
                }
                else
                {
                    $this->msg['class'] = 'error';
                    $this->msg['message'] = 'Thank you for shopping with us. However, the payment failed.';
                }

                var_dump($this->msg['message']);
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