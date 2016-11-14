<?php

require_once __DIR__.'/../templates/razorpay-settings-templates.php';

class RZP_Settings
{ 
    public function __construct()
    {
        // Creates a new menu page for razorpay's settings
        add_action('admin_menu', array($this, 'wordpressRazorpayAdminSetup'));
        // Initializes display options when admin page is initialized
        add_action('admin_init', array($this, 'displayOptions'));

        $this->template = new RZP_Templates();
    }

    /**
     * Creating up the settings page for the plug-in on the menu page
    **/
    function wordpressRazorpayAdminSetup()
    {
    	add_menu_page('Razorpay Payment Gateway', 'Razorpay', 'manage_options', 'razorpay', array($this, 'adminOptions'));
    }

    /**
     * Generates admin page options using Settings API
    **/
    function adminOptions()
    {
        $this->template->adminOptions();
    }

    /**
     * Uses Settings API to create fields
    **/
    function displayOptions()
    {
        $this->template->displayOptions();
    }

    /**
     * Settings page header
    **/        
    function displayHeader()
    {
        $this->template->displayHeader();
    }

    /**
     * Enable field of settings page
    **/
    function displayEnable()
    {
        $this->template->displayEnable();
    }

    /**
     * Title field of settings page
    **/
    function displayTitle()
    {	
        $this->template->displayTitle();
    }

    /**
     * Description field of settings page
    **/
    function displayDescription()
    {
        $this->template->displayDescription();
    }

    /**
     * Key ID field of settings page
    **/
    function displayKeyID()
    {
        $this->template->displayKeyID();
    }

    /**
     * Key secret field of settings page
    **/
    function displayKeySecret()
    {
        $this->template->displayKeySecret();
    }

    /**
     * Payment action field of settings page
    **/
    function displayPaymentAction()
    {
        $this->template->displayPaymentAction();
    }
}