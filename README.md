# Paylis WooCommerce Payment Gateway

A WooCommerce payment gateway plugin that enables stablecoin payments through the Paylis payment system.

## Features

- Accept stablecoin payments through Paylis
- Compatible with WooCommerce block-based checkout
- Simple setup with wallet address and API key
- Secure payment processing with callback handling
- Opens payment in new tab for better user experience

## Installation

1. Create a folder named `paylis-payment-gateway` in your WordPress plugins directory (`/wp-content/plugins/`)
2. Upload all the plugin files to this folder:

   - `paylis-payment-gateway.php` (main plugin file)
   - `class-paylis-gateway.php`
   - `class-paylis-blocks-support.php`
   - `paylis-blocks.js`
   - `paylis-styles.css`

3. Activate the plugin from WordPress admin panel (Plugins > Installed Plugins)

## Setup

1. Go to **WooCommerce > Settings > Payments**
2. Find **Paylis Payment Gateway** and click **Manage**
3. Configure the following settings:

   - **Enable/Disable**: Check to enable the payment gateway
   - **Title**: The payment method title shown to customers (default: "Pay with IDRX Stablecoin (Paylis)")
   - **Description**: Description shown during checkout
   - **Merchant Wallet Address**: Your wallet address for receiving payments
   - **API Key**: Your Paylis API key

4. Save the settings

## File Structure

```
paylis-payment-gateway/
├── paylis-payment-gateway.php          # Main plugin file
├── class-paylis-gateway.php            # Payment gateway class
├── class-paylis-blocks-support.php     # Block editor support
├── paylis-blocks.js                    # JavaScript for blocks
├── paylis-styles.css                   # Styling
└── README.md                           # This file
```

## How It Works

1. Customer selects Paylis payment method during checkout
2. Plugin sends payment request to Paylis API with order details
3. Paylis API returns a payment URL
4. Customer is redirected to payment URL in new tab
5. After payment completion, Paylis sends callback to update order status
6. Order status is updated automatically based on payment result

## API Integration

The plugin expects the Paylis API to:

**Request Format:**

```json
{
  "amount": "100.00",
  "currency": "USD",
  "order_id": "123",
  "wallet_address": "your_wallet_address",
  "callback_url": "https://yoursite.com/wc-api/paylis_callback",
  "return_url": "https://yoursite.com/checkout/order-received/123/",
  "customer_email": "customer@example.com",
  "description": "Order #123 - Your Site Name"
}
```

**Expected Response:**

```json
{
  "payment_url": "https://paylis.com/pay/abc123",
  "payment_id": "payment_abc123"
}
```

**Callback Format:**
The plugin expects callbacks at `/wc-api/paylis_callback` with:

```json
{
  "order_id": "123",
  "payment_id": "payment_abc123",
  "status": "completed",
  "transaction_id": "tx_abc123"
}
```

## Customization

You can customize the plugin by:

1. **Changing the API endpoint**: Edit the `$api_url` variable in the `call_paylis_api()` method
2. **Adding custom fields**: Modify the `init_form_fields()` method
3. **Custom styling**: Edit `paylis-styles.css`
4. **Additional validation**: Add custom validation in the `process_payment()` method

## Support

This is an MVP version. For production use, consider adding:

- Enhanced error handling and logging
- Webhook signature verification
- Refund support
- Multi-currency support
- Advanced configuration options

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

## Author

Created by Fahmi for Paylis payment gateway integration.
