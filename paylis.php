<?php
/**
 * Plugin Name: Paylis Payment Gateway
 * Description: Accept stablecoin payments through Paylis payment gateway
 * Version: 1.0.0
 * Author: Fahmi
 * Text Domain: paylis-payment-gateway
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
add_action('plugins_loaded', 'paylis_init_gateway');

function paylis_init_gateway()
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'paylis_woocommerce_missing_notice');
        return;
    }

    // Include the gateway class
    include_once('class-paylis-gateway.php');

    // Add the gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', 'paylis_add_gateway');
}

function paylis_woocommerce_missing_notice()
{
    echo '<div class="error"><p><strong>Paylis Payment Gateway</strong> requires WooCommerce to be installed and active.</p></div>';
}

function paylis_add_gateway($gateways)
{
    $gateways[] = 'WC_Paylis_Gateway';
    return $gateways;
}

// Register block support for checkout
add_action('woocommerce_blocks_loaded', 'paylis_register_payment_method_type');

function paylis_register_payment_method_type()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once 'class-paylis-blocks-support.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ($payment_method_registry) {
            $payment_method_registry->register(new WC_Paylis_Blocks_Support());
        }
    );
}