<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Paylis_Gateway extends WC_Payment_Gateway
{

    public function __construct()
    {
        $config = include plugin_dir_path(__FILE__) . 'config.php';
        $this->base_url = isset($config['PAYLIS_API_BASE_URL']) ? rtrim($config['PAYLIS_API_BASE_URL'], '/') : '';
        $this->id = 'paylis';
        $this->icon = 'https://paylis.netlify.app/favicon.svg';
        $this->has_fields = false;
        $this->method_title = 'Paylis Payment Gateway';
        $this->method_description = 'Accept stablecoin payments through Paylis payment gateway';
        $this->supports = array('products');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->wallet_address = $this->get_option('wallet_address');
        $this->api_key = $this->get_option('api_key');
        $this->enabled = $this->get_option('enabled');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_paylis_callback', array($this, 'handle_callback'));
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable Paylis Payment Gateway',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Pay with Stablecoin IDRX (Paylis)',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Pay securely with stablecoin through Paylis payment gateway.',
                'desc_tip' => true,
            ),
            'register_merchant' => array(
                'title' => '',
                'type' => 'title',
                'description' => $this->base_url
                    ? '<a href="' . esc_url($this->base_url . '/dashboard/merchant') . '" target="_blank" class="button button-primary">Register as a Merchant</a>'
                    : '<span style="color:red;">Please set PAYLIS_API_BASE_URL in config.php</span>',
            ),
            'wallet_address' => array(
                'title' => 'Merchant Wallet Address',
                'type' => 'text',
                'description' => 'Enter your wallet address for receiving payments.',
                'default' => '',
                'desc_tip' => true,
            ),
            'api_key' => array(
                'title' => 'API Key',
                'type' => 'password',
                'description' => 'Enter your Paylis API key.',
                'default' => '',
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Process the payment and return the result
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Prepare payment data
        $payment_data = array(
            'amount' => $order->get_total(),
            'order_id' => $order_id,
            'address' => $this->wallet_address,
        );

        // Call Paylis API
        $response = $this->call_paylis_api($payment_data);

        if ($response && isset($response['payment_url'])) {
            // Mark order as pending payment
            $order->update_status('pending', 'Awaiting Paylis payment');

            // Store payment ID and transaction ID for later reference
            if (isset($response['payment_id'])) {
                $order->add_meta_data('_paylis_payment_id', $response['payment_id']);
            }
            if (isset($response['transaction_id'])) {
                $order->add_meta_data('_paylis_transaction_id', $response['transaction_id']);
            }
            $order->save();

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            // Return success and redirect to payment URL
            return array(
                'result' => 'success',
                'redirect' => $response['payment_url']
            );
        } else {
            // Payment failed
            wc_add_notice('Payment failed. Please try again.', 'error');
            return array(
                'result' => 'fail'
            );
        }
    }

    /**
     * Call Paylis API to create payment
     */
    /**
     * Call Paylis API to create payment
     */
    private function call_paylis_api($payment_data)
    {
        // Fix: Use array access, not object property
        $api_url = $this->base_url . '/api/checkout?orderId=' . $payment_data['order_id'];

        $args = array(
            'body' => json_encode($payment_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'method' => 'POST',
            'timeout' => 30,
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            error_log('Paylis API Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Log the response for debugging
        error_log('Paylis API Response Code: ' . $response_code);
        error_log('Paylis API Response Body: ' . $body);

        if ($response_code !== 200) {
            error_log('Paylis API Error: HTTP ' . $response_code . ' - ' . $body);
            return false;
        }

        $data = json_decode($body, true);

        // Check if JSON decode was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Paylis API Error: Invalid JSON response - ' . json_last_error_msg());
            return false;
        }

        return $data;
    }

    /**
     * Handle callback from Paylis with signature verification
     */
    public function handle_callback()
    {
        // Get raw payload for signature verification
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        // Log the callback for debugging
        error_log('Paylis callback received: ' . $payload);

        // Verify signature first
        if (!$this->verify_signature($payload)) {
            error_log('Paylis callback signature verification failed');
            wp_die('Unauthorized', 'Paylis Callback', array('response' => 401));
        }

        // Validate required fields
        if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
            error_log('Paylis callback missing required fields');
            wp_die('Invalid callback data', 'Paylis Callback', array('response' => 400));
        }

        $order_id = intval($data['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            error_log('Paylis callback: Order not found - ' . $order_id);
            wp_die('Order not found', 'Paylis Callback', array('response' => 404));
        }

        // Verify this is a Paylis order
        if ($order->get_payment_method() !== $this->id) {
            error_log('Paylis callback: Wrong payment method for order ' . $order_id);
            wp_die('Wrong payment method', 'Paylis Callback', array('response' => 400));
        }

        // Verify payment ID if provided
        $stored_payment_id = $order->get_meta('_paylis_payment_id');
        if (isset($data['payment_id']) && $stored_payment_id && $stored_payment_id !== $data['payment_id']) {
            error_log('Paylis callback: Payment ID mismatch for order ' . $order_id);
            wp_die('Payment ID mismatch', 'Paylis Callback', array('response' => 400));
        }

        // Process based on payment status
        switch ($data['status']) {
            case 'completed':
            case 'success':
            case 'confirmed':
                if (!$order->is_paid()) {
                    $transaction_id = isset($data['tx_hash']) ? $data['tx_hash'] : (isset($data['transaction_id']) ? $data['transaction_id'] : '');

                    $order->payment_complete($transaction_id);
                    $order->add_order_note(sprintf(
                        'Payment completed via Paylis. Transaction Hash: %s',
                        $transaction_id ?: 'N/A'
                    ));

                    // Store transaction details
                    if (isset($data['tx_hash'])) {
                        $order->add_meta_data('_paylis_tx_hash', $data['tx_hash']);
                    }
                    if (isset($data['amount'])) {
                        $order->add_meta_data('_paylis_amount', $data['amount']);
                    }
                    if (isset($data['currency'])) {
                        $order->add_meta_data('_paylis_currency', $data['currency']);
                    }
                    $order->save();

                    // Send customer processing email
                    WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);

                    error_log('Paylis: Order ' . $order_id . ' marked as paid');
                }
                break;

            case 'failed':
                $order->update_status('failed', 'Payment failed via Paylis');
                error_log('Paylis: Order ' . $order_id . ' marked as failed');
                break;

            case 'cancelled':
                $order->update_status('cancelled', 'Payment cancelled via Paylis');
                error_log('Paylis: Order ' . $order_id . ' marked as cancelled');
                break;

            case 'pending':
                $order->update_status('on-hold', 'Payment pending confirmation via Paylis');
                error_log('Paylis: Order ' . $order_id . ' marked as pending');
                break;

            default:
                error_log('Paylis: Unknown status ' . $data['status'] . ' for order ' . $order_id);
        }

        wp_die('OK', 'Paylis Callback', array('response' => 200));
    }

    /**
     * Verify webhook signature from Paylis
     */
    private function verify_signature($payload)
    {
        // Get signature from headers
        $signature = $_SERVER['HTTP_X_PAYLIS_SIGNATURE'] ?? '';
        $timestamp = $_SERVER['HTTP_X_PAYLIS_TIMESTAMP'] ?? '';

        if (empty($signature) || empty($timestamp)) {
            error_log('Paylis: Missing signature or timestamp headers');
            return false;
        }

        // Verify timestamp to prevent replay attacks (5 minute window)
        $current_time = time();
        if (abs($current_time - intval($timestamp)) > 300) {
            error_log('Paylis: Timestamp too old or too new. Current: ' . $current_time . ', Received: ' . $timestamp);
            return false;
        }

        // Create expected signature
        $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $this->api_key);

        // Compare signatures securely
        if (!hash_equals($expected_signature, $signature)) {
            error_log('Paylis: Signature mismatch. Expected: ' . $expected_signature . ', Received: ' . $signature);
            return false;
        }

        return true;
    }

    /**
     * Check if gateway should be available
     */
    public function is_available()
    {
        if ($this->enabled === 'yes') {
            if (empty($this->wallet_address) || empty($this->api_key)) {
                return false;
            }
            return true;
        }
        return false;
    }
}