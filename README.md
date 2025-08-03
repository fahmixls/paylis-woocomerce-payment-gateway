# Paylis WooCommerce Payment Gateway

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/paylis-payment-gateway.svg)](https://wordpress.org/plugins/paylis-payment-gateway/)
[![License: GPL v3 or later](https://img.shields.io/badge/License-GPL%20v3%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

A WooCommerce payment gateway plugin that enables stablecoin payments through the Paylis payment system. This plugin provides a seamless and secure way for your customers to pay with stablecoins, and for you to receive payments directly to your wallet.

## Features

- **Accept Stablecoin Payments:** Allow customers to pay with IDRX and other stablecoins supported by Paylis.
- **Seamless Integration:** Integrates directly with your WooCommerce checkout process.
- **Block-Based Checkout Support:** Fully compatible with the new WooCommerce block-based checkout experience.
- **Easy Configuration:** Simple setup with your merchant wallet address and API key.
- **Secure Payment Processing:** Uses a secure API to create and process payments, with callback handling to update order status automatically.
- **User-Friendly Experience:** Opens the payment page in a new tab for a smooth and uninterrupted checkout flow.
- **Developer Friendly:** Provides a clean and well-documented codebase for easy customization and extension.

## Installation

1.  **Download the Plugin:** Download the latest version of the plugin from the [WordPress Plugin Directory](https://wordpress.org/plugins/paylis-payment-gateway/) or from the [GitHub repository](https://github.com/your-repo/paylis-payment-gateway).
2.  **Upload to WordPress:**
    - Go to your WordPress admin dashboard.
    - Navigate to **Plugins > Add New**.
    - Click on the **Upload Plugin** button.
    - Choose the downloaded ZIP file and click **Install Now**.
3.  **Activate the Plugin:** Once the installation is complete, click on the **Activate Plugin** button.

## Configuration

1.  **Go to WooCommerce Settings:**
    - In your WordPress admin dashboard, navigate to **WooCommerce > Settings**.
    - Click on the **Payments** tab.
2.  **Enable and Configure Paylis:**
    - Find **Paylis Payment Gateway** in the list of payment methods and click on the **Manage** button.
    - **Enable/Disable:** Check the box to enable the Paylis payment gateway.
    - **Title:** Enter the title that will be displayed to your customers during checkout (e.g., "Pay with Stablecoin (Paylis)").
    - **Description:** Enter a description for the payment method.
    - **Merchant Wallet Address:** Enter your wallet address where you want to receive payments.
    - **API Key:** Enter your Paylis API key. You can get your API key from your Paylis merchant dashboard.
3.  **Save Changes:** Click on the **Save changes** button to save your configuration.

## How It Works

1.  **Customer Selects Paylis:** During checkout, the customer selects the Paylis payment method.
2.  **Redirect to Paylis:** The customer is redirected to the secure Paylis payment page in a new tab.
3.  **Complete Payment:** The customer completes the payment using their preferred stablecoin.
4.  **Callback and Order Update:** Once the payment is complete, Paylis sends a callback to your store to update the order status.
5.  **Order Confirmation:** The customer is redirected back to your store's order confirmation page.

## For Developers

### API Integration

The plugin communicates with the Paylis API to create and process payments. The API endpoint is defined in the `config.php` file.

**API Request:**

When a customer places an order, the plugin sends a POST request to the Paylis API with the following data:

```json
{
  "amount": "100.00",
  "order_id": 123,
  "address": "your-wallet-address"
}
```

**API Response:**

The API is expected to return a JSON response with the payment URL:

```json
{
  "payment_url": "https://paylis.com/pay/some-payment-id"
}
```

### Callback Handling

The plugin handles callbacks from Paylis to update the order status. The callback URL is `https://your-domain.com/wc-api/paylis_callback`. The callback request should be a POST request with a JSON payload containing the order ID and the payment status.

### Customization

You can customize the plugin by editing the following files:

- `class-paylis-gateway.php`: The main gateway class. You can modify the payment processing logic here.
- `paylis-styles.css`: The CSS file for styling the payment method on the checkout page.
- `config.php`: The configuration file where you can change the API endpoint.

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Support

If you need help with the plugin, please open an issue on the [GitHub repository](https://github.com/fahmixls/paylis-woocomerce-payment-gateway/issues).

## License

This plugin is licensed under the GPL v3 or later. See the [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html) file for more details.

