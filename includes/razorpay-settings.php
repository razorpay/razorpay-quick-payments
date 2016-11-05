<?php

require_once __DIR__.'/../templates/razorpay-settings-templates.php';

class RZP_Settings
{ 
    public function __construct()
    {
        // Creates a new menu page for razorpay's settings
        add_action('admin_menu', array($this,'wordpress_razorpay_admin_setup'));
        // Initializes display options when admin page is initialized
        add_action('admin_init', array($this,'display_options'));
    }
    
    /**
     * Creating up the settings page for the plug-in on the menu page
    **/
    function wordpress_razorpay_admin_setup()
    {
    	add_menu_page('Razorpay Payment Gateway', 'Razorpay', 'manage_options', 'razorpay', array($this,'admin_options'));
    }

    /**
     * Generates admin page options using Settings API
    **/
    function admin_options()
    {
    	$template = new RZP_Templates();
        $template->admin_options();
    }

    /**
     * Uses Settings API to create fields
    **/
    function display_options()
    {
    	$template = new RZP_Templates();
        $template->display_options();
    }

    /**
     * Settings page header
    **/        
    function display_header()
    {
    	$template = new RZP_Templates();
        $template->display_header();
    }

    /**
     * Enable field of settings page
    **/
    function display_enable()
    {
    	$template = new RZP_Templates();
        $template->display_enable();

    }

    /**
     * Title field of settings page
    **/
    function display_title()
    {	
    	$template = new RZP_Templates();
        $template->display_title();
    }

    /**
     * Description field of settings page
    **/
    function display_description()
    {
    	$template = new RZP_Templates();
        $template->display_description();
    }

    /**
     * Key ID field of settings page
    **/
    function display_key_id()
    {
    	$template = new RZP_Templates();
        $template->display_key_id();
    }

    /**
     * Key secret field of settings page
    **/
    function display_key_secret()
    {
    	$template = new RZP_Templates();
        $template->display_key_secret();
    }

    /**
     * Payment action field of settings page
    **/
    function display_payment_action(){
    	$template = new RZP_Templates();
        $template->display_payment_action();
    }
}