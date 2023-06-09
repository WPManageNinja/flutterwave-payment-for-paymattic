<?php

namespace FlutterwavePaymentForPaymattic\API;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use WPPayForm\Framework\Support\Arr;
use WPPayForm\App\Models\Submission;
use FlutterwavePaymentForPaymattic\Settings\FlutterwaveSettings;
use WPPayForm\App\Models\Transaction;


class IPN
{
    public function init()
    {
        $this->verifyIPN();
    }

    public function verifyIPN()
    {

        if (!isset($_REQUEST['wpf_flutterwave_listener'])) {
            return;
        }

        // Check the request method is POST
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'POST') {
            return;
        }

        // Set initial post data to empty string
        $post_data = '';

        // Fallback just in case post_max_size is lower than needed
        if (ini_get('allow_url_fopen')) {
            $post_data = file_get_contents('php://input');
        } else {
            // If allow_url_fopen is not enabled, then make sure that post_max_size is large enough
            ini_set('post_max_size', '12M');
        }

        $data =  json_decode($post_data);

        if (!property_exists($data, 'event')) {
            return;
        }

        $event = str_replace('.', '_', $data->event);

        if ($event == 'charge_completed') {
            $this->handlePaymentPaid($data->data);
        } else {
            $this->handleIpn($data->data);
        }

        $responseData = [
            'status' => 'success',
            'message' => 'Webhook request processed successfully'
        ];

        wp_send_json_success($responseData, 200);

        exit(200);
    }

    protected function handleIpn($data)
    {
        //handle specific events in the future
    }

    protected function handlePaymentPaid($data)
    {
        $transactionId = $data->id;

        if (!$data->status == 'successful') {
            return;
        }

        //get transaction from database
        $transaction = Transaction::where('charge_id', $transactionId)
            ->where('payment_method', 'flutterwave')
            ->first();

        if (!$transaction || $transaction->payment_method != 'flutterwave' || $transaction->status === 'paid') {
            return;
        }

        $submissionModel = new Submission();
        $submission = $submissionModel->getSubmission($transaction->submission_id);

        $payment = $this->makeApiCall('transactions/' . $transactionId . '/verify', [], $submission->form_id);

        if (!$payment || is_wp_error($payment)) {
            return;
        }

        do_action('wppayform/form_submission_activity_start', $transaction->form_id);

        $status = 'paid';

        $updateData = [
            'payment_note'     => maybe_serialize($data),
            'charge_id'        => sanitize_text_field($transactionId),
        ];

        $flutterwaveProcessor = new FlutterwaveProcessor();
        $flutterwaveProcessor->markAsPaid($status, $updateData, $transaction);
    }

    public function makeApiCall($path, $args, $formId, $method = 'GET')
    {
        $apiKeys = (new FlutterwaveSettings())->getApiKeys($formId);
        $secretKey = Arr::get($apiKeys, 'api_secret');

        $headers = [
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($method == 'POST') {
            $response = wp_remote_post('https://api.flutterwave.com/v3/' . $path, [
                'headers' => $headers,
                'body' => json_encode($args)
            ]);
        } else {
            $response = wp_remote_get('https://api.flutterwave.com/v3/' . $path, [
                'headers' => $headers,
            ]);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $responseData = json_decode($body, true);

        if (Arr::get($responseData, 'status') == 'error') {
            $message = Arr::get($responseData, 'message');

            if (!$message) {
                $message = 'Unknown flutterwave API request error';
            }

            return new \WP_Error(423, $message, $responseData);
        }

        return $responseData;
    }
}
