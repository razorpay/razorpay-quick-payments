<?php

class RZP_Templates
{
	/**
     * Generates admin page options using Settings API
    **/
	function adminOptions()
    {
        echo
            '<div class="wrap">
                <h2>Razorpay Payment Gateway</h2>
                <form action="options.php" method="POST">';

                    settings_fields('razorpay_fields');
                    do_settings_sections('razorpay_sections');
                    submit_button();

        echo
                '</form>
            </div>';
    }

    /**
     * Uses Settings API to create fields
    **/
    function displayOptions()
    {
    	add_settings_section('razorpay_fields', 'Edit Settings', array($this, 'displayHeader'), 'razorpay_sections');

    	// Enabled/Disabled Field
    	add_settings_field('enabled_field', 'Enabled/Disabled', array($this, 'displayEnable'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'enabled_field');

    	add_settings_field('title_field', 'Title', array($this, 'displayTitle'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'title_field');

    	add_settings_field('description_field', 'Description', array($this, 'displayDescription'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'description_field');

    	add_settings_field('key_id_field', 'Key_id', array($this, 'displayKeyID'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'key_id_field');

    	add_settings_field('key_secret_field', 'Key_secret', array($this, 'displayKeySecret'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'key_secret_field');

    	add_settings_field('payment_action_field', 'Payment_action', array($this, 'displayPaymentAction'), 'razorpay_sections', 'razorpay_fields');
    	register_setting('razorpay_fields', 'payment_action_field');
    }

    /**
     * Settings page header
    **/
    function displayHeader()
    {
        $header = '<p>Razorpay is an online payment gateway for India with transparent pricing, seamless integration and great support</p>';

        echo $header;
    }

    /**
     * Enable field of settings page
    **/
    function displayEnable()
    {
        $default = get_option('enabled_field');

        $enable = <<<RZP
<input type="checkbox" name="enabled_field" id="enable" value="{$default}" checked/>
<label for ="enable">Enable Razorpay Payment Module.</label>
RZP;

        echo $enable;
    }

    /**
     * Title field of settings page
    **/
    function displayTitle()
    {
        $default = get_option('title_field', "Credit Card/Debit Card/NetBanking");

        $title = <<<RZP
<input type="text" name="title_field" id="title" size="35" value="{$default}" /><br>
<label for ="title">This controls the title which the user sees during checkout.</label>
RZP;

        echo $title;
    }

    /**
     * Description field of settings page
    **/
    function displayDescription()
    {
        $default = get_option('description_field', "Pay securely by Credit or Debit card or internet banking through Razorpay");

        $description = <<<RZP
<input type="text" name="description_field" id="description" size="35" value="{$default}" /><br>
<label for ="description">This controls the display which the user sees during checkout.</label>
RZP;

        echo $description;
    }

    /**
     * Key ID field of settings page
    **/
    function displayKeyID()
    {
        $default = get_option('key_id_field');

        $keyID = <<<RZP
<input type="text" name="key_id_field" id="key_id" size="35" value="{$default}" /><br>
<label for ="key_id">The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test or live for test or live mode.</label>
RZP;

        echo $keyID;
    }

    /**
     * Key secret field of settings page
    **/
    function displayKeySecret()
    {
        $default = get_option('key_secret_field');

        $keySecret = <<<RZP
<input type="text" name="key_secret_field" id="key_secret" size="35" value="{$default}" /><br>
<label for ="key_id">The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test or live for test or live mode.</label>
RZP;

        echo $keySecret;
    }

    /**
     * Payment action field of settings page
    **/
    function displayPaymentAction()
    {
        $default = get_option('payment_action_field');

        $paymentAction = <<<RZP
<select name="payment_action_field" id="payment_action" value="{$default}" />
    <option value="capture">Authorize and Capture</option>
    <option value="authorize">Authorize</option>
</select>
<br>
<label for ="payment_action">Payment action when order is compelete.</label>
RZP;

        echo $paymentAction;
    }
}
