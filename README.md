# WiPay WooCommerce Payment Gateway

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

Accept credit card payments across the Caribbean via **WiPay's** secure hosted checkout — directly in your WooCommerce store.

## Features

- **Multi-Country Support** — Trinidad & Tobago, Jamaica, Barbados, Saint Lucia, Grenada, Guyana
- **Automatic Currency Mapping** — Select your country, currency auto-fills (TTD, JMD, BBD, XCD, GYD, USD)
- **Sandbox & Live Modes** — Test thoroughly before going live
- **Flexible Fee Structure** — Customer pays or merchant absorbs processing fees
- **Secure Hosted Checkout** — Customers are redirected to WiPay's PCI-compliant payment page
- **Webhook Verification** — Server-to-server payment confirmation for reliability
- **Detailed Logging** — Full debug logging via WooCommerce's built-in logger
- **Order Notes** — Every payment event is recorded on the order
- **Developer Friendly** — Hooks, filters, and clean architecture for customisation
- **HPOS Compatible** — Works with WooCommerce High-Performance Order Storage
- **Translation Ready** — Full i18n support with `.pot` template included

## Quick Start

1. Download or clone this repository
2. Upload the `wipay-woocommerce` folder to `/wp-content/plugins/`
3. Activate the plugin in WordPress → Plugins
4. Go to WooCommerce → Settings → Payments → WiPay Caribbean
5. Enter your WiPay Account Number
6. Select your country and environment
7. Enable the gateway and save

## Documentation

- **[User Guide](docs/USER-GUIDE.md)** — Installation, configuration, going live
- **[Developer Guide](docs/DEVELOPER-GUIDE.md)** — Architecture, hooks, filters, extending
- **[Changelog](docs/CHANGELOG.md)** — Version history

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 5.8+ |
| WooCommerce | 5.0+ |
| PHP | 7.4+ |
| SSL | Required for live payments |

## Supported Countries & Currencies

| Country | Code | Default Currency |
|---------|------|-----------------|
| Trinidad & Tobago | TT | TTD |
| Jamaica | JM | JMD |
| Barbados | BB | BBD |
| Saint Lucia | LC | XCD |
| Grenada | GD | XCD |
| Guyana | GY | GYD |

USD is accepted across all countries.

## Payment Flow

```
Customer Checkout → WiPay Hosted Page → Payment Processed
                                             ↓
                              ┌───────────────┴───────────────┐
                              ↓                               ↓
                     Customer Redirect              Server Webhook
                     (GET → return URL)            (POST → response_url)
                              ↓                               ↓
                       Order Updated ◄────────────────────────┘
```

## Support

- **WiPay Dashboard**: [wipaycaribbean.com](https://wipaycaribbean.com)
- **Issues**: Use this repository's issue tracker
- **Email**: support@wipaycaribbean.com

## License

GPL v2 or later. See [LICENSE](LICENSE).

---

Built with ❤️ by [WiPay Caribbean](https://wipaycaribbean.com)
