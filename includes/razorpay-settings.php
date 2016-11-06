<?php

require_once __DIR__.'/../templates/razorpay-settings-templates.php';

class RZP_Settings
{ 
    public function __construct()
    {
        // Creates a new menu page for razorpay's settings
        add_action('admin_menu', array($this,'wordpressRazorpayAdminSetup'));
        // Initializes display options when admin page is initialized
        add_action('admin_init', array($this,'displayOptions'));
    }
    
    /**
     * Creating up the settings page for the plug-in on the menu page
    **/
    function wordpress_razorpay_admin_setup()
    {
    	add_menu_page('Razorpay Payment Gateway', 'Razorpay', 'manage_options', 'razorpay', array($this,'adminOptions'));
    }

    /**
     * Generates admin page options using Settings API
    **/
    function adminOptions()
    {
        $template = new RZP_Templates();
        $template->adminOptions();
    }

    /**
     * Uses Settings API to create fields
    **/
    function displayOptions()
    {
        $template = new RZP_Templates();
        $template->displayOptions();
    }

    /**
     * Settings page header
    **/        
    function displayHeader()
    {
        $template = new RZP_Templates();
        $template->displayHeader();
    }

    /**
     * Enable field of settings page
    **/
    function displayEnable()
    {
        $template = new RZP_Templates();
        $template->displayEnable();
    }

    /**
     * Title field of settings page
    **/
    function displayTitle()
    {	
        $template = new RZP_Templates();
        $template->displayTitle();
    }

    /**
     * Description field of settings page
    **/
    function displayDescription()
    {
        $template = new RZP_Templates();
        $template->displayDescription();
    }

    /**
     * Key ID field of settings page
    **/
    function displayKeyID()
    {
        $template = new RZP_Templates();
        $template->displayKeyID();
    }

    /**
     * Key secret field of settings page
    **/
    function displayKeySecret()
    {
        $template = new RZP_Templates();
        $template->displayKeySecret();
    }

    /**
     * Payment action field of settings page
    **/
    function displayPaymentAction(){
        $template = new RZP_Templates();
        $template->displayPaymentAction();
    }
}