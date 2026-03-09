# WiPay WooCommerce Payment Gateway Plugin - Build Spec

## Overview
Build a production-ready WooCommerce payment gateway plugin for WiPay Caribbean (wipaycaribbean.com). This replaces an outdated plugin. Must be robust, well-documented, and WordPress plugin directory ready.

## WiPay API Details

### Payment Request Endpoint
- **URL**: `https://{country_code}.wipayfinancial.com/plugins/payments/request`
- **Method**: POST (form submission - redirect user to WiPay hosted checkout)
- **Country codes**: `tt` (Trinidad), `jm` (Jamaica), `bb` (Barbados), `lc` (Saint Lucia), `gd` (Grenada), `gy` (Guyana)

### Required Form Fields
| Field | Description |
|-------|-------------|
| `account_number` | Merchant's WiPay account number |
| `country_code` | 2-letter country code (TT, JM, BB, LC, GD, GY) |
| `currency` | Currency code (TTD, USD, JMD, BBD, XCD, GYD) |
| `environment` | `sandbox` or `live` |
| `fee_structure` | `customer_pay` or `merchant_absorb` |
| `method` | `credit_card` |
| `order_id` | Unique order identifier |
| `origin` | Plugin/app name identifier |
| `total` | Payment amount (2 decimal places) |
| `addr_email` | Customer email |
| `url` | Return URL after payment (GET callback) |
| `response_url` | Server-to-server webhook URL (POST) |

### Callback Response (GET to return URL)
Query parameters: `status`, `order_id`, `transaction_id`, `reasonDescription`
- `status`: `success` or `failed`

### Webhook Response (POST to response_url)
POST body: `status`, `order_id`, `transaction_id`, `reasonDescription`

## Plugin Requirements

### Core Features
1. **WooCommerce Payment Gateway** extending `WC_Payment_Gateway`
2. **Multi-country support** - dropdown for TT, JM, BB, LC, GD, GY with auto currency mapping
3. **Sandbox/Live toggle** in settings
4. **Fee structure option** - customer pays or merchant absorbs
5. **Order status management** - pending → processing/completed on success, failed on failure
6. **Webhook handler** for server-to-server payment confirmation
7. **Return URL handler** for customer redirect after payment
8. **Refund support** (if WiPay API supports, otherwise note as manual)
9. **Detailed logging** via WooCommerce logger
10. **Order notes** for every payment event (attempt, success, failure, webhook)

### Settings Page Fields
- Enable/Disable
- Title (shown on checkout)
- Description (shown on checkout)
- WiPay Account Number
- Environment (Sandbox/Live)
- Country Code (dropdown with all supported countries)
- Currency (auto-populated based on country, editable)
- Fee Structure (Customer Pay / Merchant Absorb)
- Debug Mode (enable logging)

### Security
- Nonce verification on callbacks
- Order ID validation (must match WooCommerce order)
- Transaction amount verification
- Webhook signature validation (if available)
- Sanitize all inputs
- Use WordPress escaping functions for output

### WordPress Standards
- Follow WordPress Coding Standards
- Use WordPress HTTP API (`wp_remote_post`, `wp_remote_get`)
- Proper i18n with text domain `wipay-woocommerce`
- GPL v2+ license
- Plugin header with proper metadata
- Uninstall hook to clean up options
- Compatibility: WordPress 5.8+, WooCommerce 5.0+, PHP 7.4+
- HPOS (High-Performance Order Storage) compatible

### File Structure
```
wipay-woocommerce/
├── wipay-woocommerce.php          # Main plugin file
├── includes/
│   ├── class-wc-wipay-gateway.php # Payment gateway class
│   ├── class-wc-wipay-webhook.php # Webhook handler
│   ├── class-wc-wipay-logger.php  # Logging utility
│   └── class-wc-wipay-countries.php # Country/currency mapping
├── assets/
│   ├── css/
│   │   └── wipay-checkout.css     # Checkout styling
│   └── images/
│       └── wipay-logo.png         # Gateway logo (placeholder)
├── languages/
│   └── wipay-woocommerce.pot      # Translation template
├── templates/
│   └── payment-form.php           # Checkout form template
├── docs/
│   ├── USER-GUIDE.md              # End-user documentation
│   ├── DEVELOPER-GUIDE.md         # Developer/API documentation
│   └── CHANGELOG.md               # Version history
├── uninstall.php                  # Cleanup on uninstall
├── readme.txt                     # WordPress plugin directory readme
├── LICENSE                        # GPL v2+
└── README.md                      # GitHub readme
```

### Documentation Requirements

#### USER-GUIDE.md
- Installation instructions (manual + WordPress plugin upload)
- Configuration walkthrough with screenshots descriptions
- How to get WiPay merchant credentials
- Setting up sandbox for testing
- Going live checklist
- Troubleshooting common issues
- FAQ

#### DEVELOPER-GUIDE.md
- Architecture overview
- Hooks and filters available for customization
- Payment flow diagram (text-based)
- Webhook handling details
- Adding new country support
- Testing guide
- Contributing guidelines

### Hooks & Filters to Implement
- `wipay_payment_args` - filter payment request arguments
- `wipay_before_process_payment` - action before payment processing
- `wipay_after_payment_complete` - action after successful payment
- `wipay_after_payment_failed` - action after failed payment
- `wipay_country_currencies` - filter country-to-currency mapping
- `wipay_gateway_icon` - filter gateway icon URL
- `wipay_order_received_text` - filter thank you page text

### readme.txt (WordPress.org format)
Include: description, installation, FAQ, screenshots descriptions, changelog, upgrade notices

## Quality Standards
- No PHP warnings/notices
- PSR-4 compatible class loading
- Proper error handling with try/catch
- Defensive coding (check WooCommerce active, check order exists, etc.)
- Mobile-friendly checkout form
- Accessible (WCAG 2.1 AA basics)

## IMPORTANT
- This is Wayne's company (WiPay Caribbean). Make it professional and polished.
- Plugin slug: `wipay-woocommerce`
- Text domain: `wipay-woocommerce`
- Version: 2.0.0 (since this replaces an older v1)
- Author: WiPay Caribbean
- Author URI: https://wipaycaribbean.com
