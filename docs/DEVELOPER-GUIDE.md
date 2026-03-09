# WiPay WooCommerce — Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [File Structure](#file-structure)
3. [Payment Flow](#payment-flow)
4. [Class Reference](#class-reference)
5. [Hooks & Filters](#hooks--filters)
6. [Webhook Handling](#webhook-handling)
7. [Adding Country Support](#adding-country-support)
8. [Testing](#testing)
9. [Coding Standards](#coding-standards)
10. [Contributing](#contributing)

---

## Architecture Overview

The plugin follows WordPress and WooCommerce conventions:

- **Main file** (`wipay-woocommerce.php`) — Plugin bootstrap, dependency checks, class loading
- **Gateway class** (`WC_WiPay_Gateway`) — Extends `WC_Payment_Gateway`, handles all payment logic
- **Webhook class** (`WC_WiPay_Webhook`) — Handles server-to-server POST callbacks from WiPay
- **Countries class** (`WC_WiPay_Countries`) — Country/currency mapping and API URL resolution
- **Logger class** (`WC_WiPay_Logger`) — Thin wrapper around `WC_Logger` with level-based methods

```
┌─────────────────────────────────────────────────┐
│                  WordPress                       │
│  ┌─────────────────────────────────────────────┐ │
│  │               WooCommerce                    │ │
│  │  ┌──────────────────┐  ┌──────────────────┐ │ │
│  │  │  WC_WiPay_Gateway │  │ WC_WiPay_Webhook │ │ │
│  │  │  (checkout flow)  │  │ (server callback)│ │ │
│  │  └────────┬─────────┘  └────────┬─────────┘ │ │
│  │           │                      │           │ │
│  │  ┌────────┴──────────────────────┴─────────┐ │ │
│  │  │  WC_WiPay_Countries  │  WC_WiPay_Logger │ │ │
│  │  └─────────────────────────────────────────┘ │ │
│  └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
         │                          ▲
         ▼ (POST form redirect)     │ (GET return / POST webhook)
┌─────────────────────────────────────────────────┐
│            WiPay Payment Gateway                 │
│     {cc}.wipayfinancial.com/plugins/payments     │
└─────────────────────────────────────────────────┘
```

---

## File Structure

```
wipay-woocommerce/
├── wipay-woocommerce.php              # Plugin bootstrap & dependency checks
├── includes/
│   ├── class-wc-wipay-gateway.php     # WC_Payment_Gateway extension (642 lines)
│   ├── class-wc-wipay-webhook.php     # Server-to-server webhook handler
│   ├── class-wc-wipay-logger.php      # WooCommerce logger wrapper
│   └── class-wc-wipay-countries.php   # Country/currency/API URL mapping
├── assets/
│   ├── css/
│   │   └── wipay-checkout.css         # Checkout page styles
│   └── images/
│       └── wipay-logo.svg             # Gateway icon
├── templates/
│   └── payment-form.php               # Self-submitting redirect form
├── languages/
│   └── wipay-woocommerce.pot          # i18n translation template
├── docs/
│   ├── USER-GUIDE.md                  # End-user documentation
│   ├── DEVELOPER-GUIDE.md             # This file
│   └── CHANGELOG.md                   # Version history
├── uninstall.php                      # Cleanup on plugin deletion
├── readme.txt                         # WordPress.org plugin directory
├── README.md                          # GitHub readme
└── LICENSE                            # GPL v2+
```

---

## Payment Flow

### Step-by-Step

```
1. Customer → Checkout → "Place Order"
       │
       ▼
2. WC_WiPay_Gateway::process_payment($order_id)
   - Validates settings (account number configured)
   - Sets order status to "pending"
   - Reduces stock, empties cart
   - Returns redirect to receipt page
       │
       ▼
3. WC_WiPay_Gateway::receipt_page($order_id)
   - Builds payment args (build_payment_args)
   - Renders self-submitting HTML form (templates/payment-form.php)
   - Form POSTs to: https://{cc}.wipayfinancial.com/plugins/payments/request
       │
       ▼
4. Customer on WiPay hosted page
   - Enters card details
   - WiPay processes payment
       │
       ├──── Success ────┐
       │                  │
       ▼                  ▼
5a. GET Return URL    5b. POST Webhook URL
    (customer browser)     (server-to-server)
       │                  │
       ▼                  ▼
    handle_return()    WC_WiPay_Webhook::handle()
       │                  │
       ▼                  ▼
    payment_complete()  payment_complete()
    (idempotent — safe to call twice)
       │
       ▼
6. Order status → "processing"
   Order note added with transaction ID
   Customer redirected to thank-you page
```

### WiPay API Request Parameters

| Parameter | Source | Example |
|-----------|--------|---------|
| `account_number` | Plugin settings | `1234567890` |
| `country_code` | Plugin settings (uppercase) | `TT` |
| `currency` | Plugin settings | `TTD` |
| `environment` | Plugin settings | `sandbox` |
| `fee_structure` | Plugin settings | `customer_pay` |
| `method` | Hardcoded | `credit_card` |
| `order_id` | WooCommerce order ID | `1234` |
| `origin` | Plugin constant | `WiPay_WooCommerce_v2` |
| `total` | Order total (2 decimals) | `99.99` |
| `addr_email` | Billing email | `customer@example.com` |
| `url` | Generated return URL | `https://store.com/wc-api/wipay_return?order_id=1234` |
| `response_url` | Generated webhook URL | `https://store.com/wc-api/wipay_webhook` |

### WiPay Response Parameters (Return & Webhook)

| Parameter | Description |
|-----------|-------------|
| `status` | `success` or `failed` |
| `order_id` | The order ID sent in the request |
| `transaction_id` | WiPay's unique transaction identifier |
| `reasonDescription` | Human-readable failure reason (if failed) |

---

## Class Reference

### WC_WiPay_Gateway

Extends `WC_Payment_Gateway`. Core payment logic.

| Method | Description |
|--------|-------------|
| `__construct()` | Initialises gateway, loads settings, wires hooks |
| `init_form_fields()` | Defines admin settings fields |
| `admin_options()` | Renders settings page with JS currency auto-fill |
| `on_settings_saved()` | Auto-syncs currency when country changes |
| `process_payment($order_id)` | Validates and initiates payment flow |
| `receipt_page($order_id)` | Renders the redirect form |
| `build_payment_args($order)` | Builds WiPay API parameters |
| `get_api_endpoint()` | Returns country-specific API URL |
| `handle_return()` | Processes GET callback from WiPay |
| `payment_complete($order, $txn_id, $source)` | Marks order paid (idempotent) |
| `payment_failed($order, $reason, $txn_id, $source)` | Marks order failed |
| `process_refund($order_id, $amount, $reason)` | Returns error (manual refunds only) |
| `thankyou_page($text, $order)` | Customises thank-you text |

### WC_WiPay_Webhook

Handles server-to-server POST callbacks.

| Method | Description |
|--------|-------------|
| `init()` | Registers the `woocommerce_api_wipay_webhook` hook |
| `handle()` | Processes incoming webhook, validates order, updates status |
| `get_webhook_url()` | Returns the webhook endpoint URL |

### WC_WiPay_Countries

Static utility class for country/currency data.

| Method | Description |
|--------|-------------|
| `get_countries()` | Returns full country data array |
| `get_country_options()` | Returns `code => name` for dropdowns |
| `get_currency_for_country($code)` | Returns default currency for a country |
| `get_api_base_url($code)` | Returns `https://{cc}.wipayfinancial.com` |

### WC_WiPay_Logger

Static wrapper around WooCommerce's logger.

| Method | Description |
|--------|-------------|
| `debug($message, $context)` | Log debug message (only when debug enabled) |
| `info($message, $context)` | Log info message |
| `warning($message, $context)` | Log warning message |
| `error($message, $context)` | Log error message |
| `reset_cache()` | Clear cached logger instance |

---

## Hooks & Filters

### Actions

```php
// Before payment processing begins
do_action( 'wipay_before_process_payment', $order );

// After successful payment
do_action( 'wipay_after_payment_complete', $order, $transaction_id, $source );

// After failed payment
do_action( 'wipay_after_payment_failed', $order, $reason, $transaction_id, $source );
```

### Filters

```php
// Modify payment request parameters before sending to WiPay
add_filter( 'wipay_payment_args', function( $args, $order ) {
    // Example: add a custom field
    $args['custom_field'] = 'my_value';
    return $args;
}, 10, 2 );

// Change the gateway icon
add_filter( 'wipay_gateway_icon', function( $url ) {
    return get_stylesheet_directory_uri() . '/images/my-wipay-icon.png';
});

// Customise thank-you page text
add_filter( 'wipay_order_received_text', function( $text, $order ) {
    return 'Thanks for your payment! Order #' . $order->get_id() . ' is confirmed.';
}, 10, 2 );

// Add or modify supported countries/currencies
add_filter( 'wipay_country_currencies', function( $countries ) {
    $countries['sr'] = [
        'name'     => 'Suriname',
        'currency' => 'SRD',
        'domain'   => 'sr',
    ];
    return $countries;
});
```

---

## Webhook Handling

### Endpoint

The webhook listens at:
```
https://yoursite.com/wc-api/wipay_webhook
```

This URL is automatically generated and sent to WiPay as the `response_url` parameter.

### Security

The webhook handler validates:

1. **Required parameters** — `status`, `order_id`, and `transaction_id` must be present
2. **Order existence** — The order ID must correspond to a real WooCommerce order
3. **Payment method** — The order must have been placed using the WiPay gateway
4. **Idempotency** — Already-completed orders are not re-processed

### Debugging Webhooks

1. Enable Debug Mode in plugin settings
2. Check logs at WooCommerce → Status → Logs → `wipay-woocommerce-*`
3. Look for entries starting with `[Webhook]`

If webhooks aren't arriving:
- Ensure your site is publicly accessible
- Check that no firewall or security plugin blocks POST requests to `/wc-api/wipay_webhook`
- Verify SSL certificate is valid (WiPay may reject self-signed certs)

---

## Adding Country Support

To add a new Caribbean country:

### Option 1: Via Filter (Recommended)

In your theme's `functions.php` or a custom plugin:

```php
add_filter( 'wipay_country_currencies', function( $countries ) {
    $countries['vc'] = [
        'name'     => 'Saint Vincent and the Grenadines',
        'currency' => 'XCD',
        'domain'   => 'vc',
    ];
    return $countries;
});
```

### Option 2: Core Modification

Edit `includes/class-wc-wipay-countries.php` and add to the `COUNTRIES` constant:

```php
'vc' => [
    'name'     => 'Saint Vincent and the Grenadines',
    'currency' => 'XCD',
    'domain'   => 'vc',
],
```

> **Note**: The `domain` value is used to construct the API URL: `https://{domain}.wipayfinancial.com`

---

## Testing

### Manual Testing Checklist

1. **Sandbox Payment Success**
   - Place order → complete payment on WiPay sandbox
   - Verify: order status = "Processing", order note has transaction ID

2. **Sandbox Payment Failure**
   - Place order → cancel/fail payment on WiPay sandbox
   - Verify: order status = "Failed", error message shown to customer

3. **Webhook Delivery**
   - Enable debug logging
   - Complete a payment
   - Check logs for `[Webhook] Payment confirmed` entry

4. **Settings Validation**
   - Remove account number → try checkout → verify error message
   - Change country → verify currency auto-updates
   - Toggle environment → verify sandbox warning appears/disappears

5. **Edge Cases**
   - Browser closes during payment → webhook should still update order
   - Double-click "Place Order" → should not create duplicate charges
   - Back button from WiPay page → order stays "Pending"

### Automated Testing

For PHPUnit testing, mock the WiPay API responses:

```php
// Example test for build_payment_args
public function test_payment_args_structure() {
    $gateway = new WC_WiPay_Gateway();
    $order   = wc_create_order();
    $order->set_total( '50.00' );
    $order->set_billing_email( 'test@example.com' );
    $order->save();

    $args = $gateway->build_payment_args( $order );

    $this->assertArrayHasKey( 'account_number', $args );
    $this->assertArrayHasKey( 'total', $args );
    $this->assertEquals( '50.00', $args['total'] );
    $this->assertEquals( 'credit_card', $args['method'] );
}
```

---

## Coding Standards

This plugin follows:

- **WordPress Coding Standards** (WPCS)
- **WooCommerce Code Reference** conventions
- PHP 7.4+ type hints where applicable
- Proper escaping: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Proper sanitisation: `sanitize_text_field()`, `absint()`, `wp_unslash()`
- i18n: All user-facing strings wrapped in `__()` or `esc_html_e()` with text domain `wipay-woocommerce`

### Linting

```bash
# Install WPCS
composer require --dev wp-coding-standards/wpcs dealerdirect/phpcodesniffer-composer-installer

# Run
vendor/bin/phpcs --standard=WordPress wipay-woocommerce.php includes/
```

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Follow the coding standards above
4. Test manually with WooCommerce + WiPay sandbox
5. Submit a pull request with a clear description

### Commit Message Format

```
type: short description

Longer explanation if needed.

Types: feat, fix, docs, refactor, test, chore
```

---

## Constants

Defined in `wipay-woocommerce.php`:

| Constant | Description |
|----------|-------------|
| `WIPAY_VERSION` | Plugin version (`2.0.0`) |
| `WIPAY_PLUGIN_FILE` | Absolute path to main plugin file |
| `WIPAY_PLUGIN_DIR` | Plugin directory path (trailing slash) |
| `WIPAY_PLUGIN_URL` | Plugin directory URL (trailing slash) |
| `WIPAY_MIN_PHP` | Minimum PHP version (`7.4`) |
| `WIPAY_MIN_WC` | Minimum WooCommerce version (`5.0`) |
