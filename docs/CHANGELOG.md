# Changelog

All notable changes to the WiPay WooCommerce Payment Gateway are documented here.

## [2.0.0] — 2026-03-09

### Added
- Complete rewrite of the WiPay WooCommerce payment gateway
- Multi-country support: Trinidad & Tobago, Jamaica, Barbados, Saint Lucia, Grenada, Guyana
- Automatic currency mapping when country is changed
- Sandbox and Live environment toggle
- Configurable fee structure (customer pays or merchant absorbs)
- Server-to-server webhook handler for reliable payment confirmation
- Customer return URL handler with proper order status updates
- Comprehensive WooCommerce logging integration (debug, info, warning, error)
- Order notes for every payment event (initiated, success, failure, webhook)
- Developer hooks and filters:
  - `wipay_payment_args` — filter payment request parameters
  - `wipay_before_process_payment` — action before payment processing
  - `wipay_after_payment_complete` — action after successful payment
  - `wipay_after_payment_failed` — action after failed payment
  - `wipay_country_currencies` — filter country-to-currency mapping
  - `wipay_gateway_icon` — filter the gateway icon URL
  - `wipay_order_received_text` — filter thank-you page text
- HPOS (High-Performance Order Storage) compatibility
- Full i18n support with `.pot` translation template
- Checkout page CSS styling
- SVG gateway logo
- Clean uninstall with option cleanup
- Comprehensive User Guide and Developer Guide documentation
- WordPress.org-ready readme.txt

### Changed
- Minimum PHP version: 7.4 (up from 5.6)
- Minimum WordPress version: 5.8 (up from 4.0)
- Minimum WooCommerce version: 5.0 (up from 3.0)

### Removed
- Legacy v1 codebase (full rewrite)

## [1.x] — Previous Versions

The original WiPay WooCommerce plugin. Replaced entirely by v2.0.0.
