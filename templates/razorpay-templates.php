<?php

class RZP_Templates
{
	/**
     * Generates admin page options using Settings API
    **/
	function admin_options()
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
    function display_enable()
    {
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
}