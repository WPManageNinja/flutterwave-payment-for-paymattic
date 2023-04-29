<?php

namespace  FlutterwaveForPaymattic\Settings;

use \WPPayForm\Framework\Support\Arr;
use \WPPayForm\App\Services\AccessControl;
use \WPPayFormPro\GateWays\BasePaymentMethod;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class FlutterwaveSettings extends BasePaymentMethod
{
   /**
     * Automatically create global payment settings page
     * @param  String: key, title, routes_query, 'logo')
     */
    public function __construct()
    {
        parent::__construct(
            'flutterwave',
            'flutterwave',
            [],
            FLUTTERWAVE_FOR_PAYMATTIC_URL . 'assets/flutterwave.svg' // follow naming convention of logo with lowercase exactly as payment key to avoid logo rendering hassle
        );
    }

     /**
     * @function mapperSettings, To map key => value before store
     * @function validateSettings, To validate before save settings
     */

    public function init()
    {
        add_filter('wppayform_payment_method_settings_mapper_'.$this->key, array($this, 'mapperSettings'));
        add_filter('wppayform_payment_method_settings_validation_'.$this->key, array($this, 'validateSettings'), 10, 2);
    }

    public function mapperSettings ($settings)
    {
        return $this->mapper(
            static::settingsKeys(), 
            $settings, 
            false
        );
    }

    /**
     * @return Array of default fields
     */
    public static function settingsKeys()
    {
        return array(
            'payment_mode' => 'test',
            'live_pub_key' => '',
            'live_secret_key' => '',
            'test_pub_key' => '',
            'test_secret_key' => '',
        );
    }

    public static function getSettings () {
        $setting = get_option('wppayform_payment_settings_flutterwave', []);
        
        return wp_parse_args($setting, static::settingsKeys());
    }

    public function getPaymentSettings()
    {
        $settings = $this->mapper(
            $this->globalFields(), 
            static::getSettings()
        );
        return array(
            'settings' => $settings
        ); 
    }

    /**
     * @return Array of global fields
     */
    public function globalFields()
    {
        return array(
            'payment_mode' => array(
                'value' => 'test',
                'label' => __('Payment Mode', 'flutterwave-for-paymattic'),
                'options' => array(
                    'test' => __('Test Mode', 'flutterwave-for-paymattic'),
                    'live' => __('Live Mode', 'flutterwave-for-paymattic')
                ),
                'type' => 'payment_mode'
            ),
            'test_pub_key' => array(
                'value' => '',
                'label' => __('Test Public Key', 'flutterwave-for-paymattic'),
                'type' => 'test_pub_key',
                'placeholder' => __('Test Public Key', 'flutterwave-for-paymattic')
            ),
            'test_secret_key' => array(
                'value' => '',
                'label' => __('Test Secret Key', 'flutterwave-for-paymattic'),
                'type' => 'test_secret_key',
                'placeholder' => __('Test Secret Key', 'flutterwave-for-paymattic')
            ),
            'live_pub_key' => array(
                'value' => '',
                'label' => __('Live Public Key', 'flutterwave-for-paymattic'),
                'type' => 'live_pub_key',
                'placeholder' => __('Live Public Key', 'flutterwave-for-paymattic')
            ),
            'live_secret_key' => array(
                'value' => '',
                'label' => __('Live Secret Key', 'flutterwave-for-paymattic'),
                'type' => 'live_secret_key',
                'placeholder' => __('Live Secret Key', 'flutterwave-for-paymattic')
            ),
            'desc' => array(
                'value' => '<p>See our <a href="https://paymattic.com/docs/how-to-integrate-flutterwave-in-wordpress-with-paymattic/" target="_blank" rel="noopener">documentation</a> to get more information about flutterwave setup.</p>',
                'type' => 'html_attr',
                'placeholder' => __('Description', 'flutterwave-for-paymattic')
            ),
            'webhook_desc' => array(
                'value' => "<h3>Flutterwave Webhook </h3> <p>In order for Flutterwave to function completely for payments, you must configure your flutterwave webhooks. Visit your <a href='https://dashboard.flutterwave.co/settings/developers#callbacks' target='_blank' rel='noopener'>account dashboard</a> to configure them. Please add a webhook endpoint for the URL below. </p> <p><b>Webhook URL: </b><code> ". site_url('?wpf_flutterwave_listener=1') . "</code></p> <p>See <a href='https://paymattic.com/docs/how-to-configure-stripe-payment-gateway-in-wordpress-with-paymattic/' target='_blank' rel='noopener'>our documentation</a> for more information.</p> <div> <p><b>Please subscribe to these following Webhook events for this URL:</b></p> <ul> <li><code>invoice paid</code></li></ul> </div>",
                'label' => __('Webhook URL', 'wp-payment-form'),
                'type' => 'html_attr'
            ),
            'is_pro_item' => array(
                'value' => 'yes',
                'label' => __('PayPal', 'flutterwave-for-paymattic'),
            ),
        );
    }

    public function validateSettings($errors, $settings)
    {
        AccessControl::checkAndPresponseError('set_payment_settings', 'global');
        $mode = Arr::get($settings, 'payment_mode');

        if ($mode == 'test') {
            if (empty(Arr::get($settings, 'test_secret_key'))) {
                $errors['test_api_key'] = __('Please provide Test Secret Key', 'flutterwave-for-paymattic');
            }
        }

        if ($mode == 'live') {
            if (empty(Arr::get($settings, 'live_secret_key'))) {
                $errors['live_api_key'] = __('Please provide Live Secret Key', 'flutterwave-for-paymattic');
            }
        }
        return $errors;
    }

    public function isLive($formId = false)
    {
        $settings = $this->getSettings();
        return $settings['payment_mode'] == 'live';
    }

    public function getApiKeys($formId = false)
    {
        $isLive = static::isLive($formId);
        $settings = static::getSettings();

        if ($isLive) {
            return array(
                'api_key' => Arr::get($settings, 'live_pub_key'),
                'api_secret' => Arr::get($settings, 'live_secret_key')
            );
        }
        return array(
            'api_key' => Arr::get($settings, 'test_pub_key'),
            'api_secret' => Arr::get($settings, 'test_secret_key')
        );
    }
}
