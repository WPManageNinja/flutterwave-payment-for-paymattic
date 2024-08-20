<?php

/**
 * @package flutterwave-payment-for-paymattic
 *
 *
 */

/**
 * Plugin Name: Flutterwave Payment for paymattic
 * Plugin URI: https://paymattic.com/
 * Description: Flutterwave payment gateway for paymattic. Flutterwave is the leading payment gateway in Nigeria and all of Africa.
 * Version: 1.0.1
 * Author: WPManageNinja LLC
 * Author URI: https://paymattic.com/
 * License: GPLv2 or later
 * Text Domain: flutterwave-payment-for-paymattic
 * Domain Path: /language
 */

if (!defined('ABSPATH')) {
    exit;
}

defined('ABSPATH') or die;

define('FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC', true);
define('FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC_DIR', __DIR__);
define('FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC_URL', plugin_dir_url(__FILE__));
define('FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC_VERSION', '1.0.1');


if (!class_exists('FlutterwavePaymentForPaymattic')) {
    class FlutterwavePaymentForPaymattic
    {
        public function boot()
        {
            if (!class_exists('FlutterwavePaymentForPaymattic\API\FlutterwaveProcessor')) {
                $this->init();
            };
        }

        public function init()
        {
            require_once FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC_DIR . '/API/FlutterwaveProcessor.php';
            (new FlutterwavePaymentForPaymattic\API\FlutterwaveProcessor())->init();

            $this->loadTextDomain();
        }

        public function loadTextDomain()
        {
            load_plugin_textdomain('flutterwave-payment-for-paymattic', false, dirname(plugin_basename(__FILE__)) . '/language');
        }

        public function hasPro()
        {
            return defined('WPPAYFORMPRO_DIR_PATH') || defined('WPPAYFORMPRO_VERSION');
        }

        public function hasFree()
        {

            return defined('WPPAYFORM_VERSION');
        }

        public function versionCheck()
        {
            $currentFreeVersion = WPPAYFORM_VERSION;
            $currentProVersion = WPPAYFORMPRO_VERSION;

            return version_compare($currentFreeVersion, '4.3.2', '>=') && version_compare($currentProVersion, '4.3.2', '>=');
        }

        public function renderNotice()
        {
            add_action('admin_notices', function () {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p>';
                    echo __('Please install & Activate Paymattic and Paymattic Pro to use flutterwave-payment-for-paymattic plugin.', 'flutterwave-payment-for-paymattic');
                    echo '</p></div>';
                }
            });
        }

        public function updateVersionNotice()
        {
            add_action('admin_notices', function () {
                if (current_user_can('activate_plugins')) {
                    echo '<div class="notice notice-error"><p>';
                    echo __('Please update Paymattic and Paymattic Pro to use flutterwave-payment-for-paymattic plugin!', 'flutterwave-payment-for-paymattic');
                    echo '</p></div>';
                }
            });
        }
    }


    add_action('init', function () {

        $flutterwave = (new FlutterwavePaymentForPaymattic);

        if (!$flutterwave->hasFree() || !$flutterwave->hasPro()) {
            $flutterwave->renderNotice();
        } else if (!$flutterwave->versionCheck()) {
            $flutterwave->updateVersionNotice();
        } else {
            $flutterwave->boot();
        }
    });
}
