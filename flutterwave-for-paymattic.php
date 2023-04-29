<?php

/**
 * @package flutterwave-for-paymattic
 * 
 * 
 * 
 */

/** 
 * Plugin Name: Flutterwave for paymattic
 * Plugin URI: https://paymattic.com/
 * Description: Flutterwave payment gateway for paymattic. Flutterwave is the leading payment gateway in Nigeria and all of Africa.
 * Version: 1.0.0
 * Author: WPManageNinja LLC
 * Author URI: https://paymattic.com/
 * License: GPLv2 or later
 * Text Domain: flutterwave-for-paymattic
 * Domain Path: /language
*/

if (!defined('ABSPATH')) {
    exit;
}

defined('ABSPATH') or die;

define('FLUTTERWAVE_FOR_PAYMATTIC', true);
define('FLUTTERWAVE_FOR_PAYMATTIC_DIR', __DIR__);
define('FLUTTERWAVE_FOR_PAYMATTIC_URL', plugin_dir_url(__FILE__));
define('FLUTTERWAVE_FOR_PAYMATTIC_VERSION', '1.0.0');


add_action('wppayform_loaded', function () {

   if (!defined('WPPAYFORMPRO_DIR_PATH') || !defined('WPPAYFORM_VERSION')) { 
         add_action('admin_notices', function () {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p>';
                echo __('Please install & Activate Paymattic Pro to use flutterwave-payment-for-paymattic plugin.', 'flutterwave-payment-for-paymattic');
                echo '</p></div>';
            }
        });
    } else {
        $currentVersion = WPPAYFORM_VERSION;
        if (version_compare($currentVersion, '4.3.2', '>=')) {
            if (!class_exists('FlutterwaveForPaymattic\FlutterwaveProcessor')) {
                require_once FLUTTERWAVE_FOR_PAYMATTIC_DIR . '/API/FlutterwaveProcessor.php';
                (new FlutterwaveForPaymattic\API\FlutterwaveProcessor())->init();
                add_action('init', function() {
                    load_plugin_textdomain('wp-payment-form-pro', false, dirname(plugin_basename(__FILE__)) . '/language');
                });
            };
        } else {
            add_action('admin_notices', function () {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p>';
                    echo __('Please update Paymattic and Paymattic Pro to use flutterwave-payment-for-paymattic plugin!', 'flutterwave-payment-for-paymattic');
                    echo '</p></div>';
                }
            });
        }
    }
});