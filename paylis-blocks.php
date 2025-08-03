<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

class WC_Paylis_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'paylis';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_paylis_settings', []);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function is_active()
    {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'paylis-blocks-integration',
            plugin_dir_url(__FILE__) . 'paylis-blocks.js',
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-html-entities'],
            '1.0.0',
            true
        );

        return ['paylis-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title', 'Pay with Stablecoin IDRX (Paylis)'),
            'description' => $this->get_setting('description', 'Pay securely with stablecoin through Paylis payment gateway.'),
            'supports' => ['products'],
            'wallet_address' => $this->get_setting('wallet_address', ''),
            'api_key' => !empty($this->get_setting('api_key', '')) ? 'configured' : 'not_configured',
        ];
    }

    public function enqueue_scripts()
    {
        if (!is_cart() && !is_checkout())
            return;
        wp_enqueue_script('paylis-blocks-integration');
    }
}

add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
    $registry->register(new WC_Paylis_Blocks_Support());
});
