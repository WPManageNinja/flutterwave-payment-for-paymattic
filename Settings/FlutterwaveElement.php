<?php

namespace FlutterwavePaymentForPaymattic\Settings;

use WPPayForm\App\Modules\FormComponents\BaseComponent;
use WPPayForm\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class FlutterwaveElement extends BaseComponent
{
    public $gateWayName = 'flutterwave';

    public function __construct()
    {
        parent::__construct('flutterwave_gateway_element', 8);

        add_action('wppayform/validate_gateway_api_' . $this->gateWayName, array($this, 'validateApi'));
        add_filter('wppayform/validate_gateway_api_' . $this->gateWayName, function ($data, $form) {
            return $this->validateApi();
        }, 2, 10);
        add_action('wppayform/payment_method_choose_element_render_flutterwave', array($this, 'renderForMultiple'), 10, 3);
        add_filter('wppayform/available_payment_methods', array($this, 'pushPaymentMethod'), 2, 1);
    }

    public function pushPaymentMethod($methods)
    {
        $methods['flutterwave'] = array(
            'label' => 'flutterwave',
            'isActive' => true,
            'logo' => FLUTTERWAVE_PAYMENT_FOR_PAYMATTIC_URL . 'assets/flutterwave.svg',
            'editor_elements' => array(
                'label' => array(
                    'label' => 'Payment Option Label',
                    'type' => 'text',
                    'default' => 'Pay with flutterwave'
                )
            )
        );
        return $methods;
    }


    public function component()
    {
        return array(
            'type' => 'flutterwave_gateway_element',
            'editor_title' => 'Flutterwave Payment',
            'editor_icon' => '',
            'conditional_hide' => true,
            'group' => 'payment_method_element',
            'method_handler' => $this->gateWayName,
            'postion_group' => 'payment_method',
            'single_only' => true,
            'editor_elements' => array(
                'label' => array(
                    'label' => 'Field Label',
                    'type' => 'text'
                )
            ),
            'field_options' => array(
                'label' => __('Flutterwave Payment Gateway', 'flutterwave-payment-for-paymattic')
            )
        );
    }

    public function validateApi()
    {
        $flutterwave = new FlutterwaveSettings();
        return $flutterwave->getApiKeys();
    }

    public function render($element, $form, $elements)
    {
        if (!$this->validateApi()) { ?>
            <p style="color: red">You did not configure Flutterwave payment gateway. Please configure flutterwave payment
                gateway from <b>Paymattic->Payment Gateway->Flutterwave Settings</b> to start accepting payments</p>
<?php return;
        }

        echo '<input data-wpf_payment_method="flutterwave" type="hidden" name="__flutterwave_payment_gateway" value="flutterwave" />';
    }

    public function renderForMultiple($paymentSettings, $form, $elements)
    {
        $component = $this->component();
        $component['id'] = 'flutterwave_gateway_element';
        $component['field_options'] = $paymentSettings;
        $this->render($component, $form, $elements);
    }
}
