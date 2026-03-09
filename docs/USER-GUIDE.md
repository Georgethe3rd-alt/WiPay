# WiPay WooCommerce — User Guide

## Table of Contents

1. [Getting Your WiPay Credentials](#getting-your-wipay-credentials)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Testing with Sandbox](#testing-with-sandbox)
5. [Going Live Checklist](#going-live-checklist)
6. [How Payments Work](#how-payments-work)
7. [Managing Orders](#managing-orders)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

---

## Getting Your WiPay Credentials

Before installing the plugin, you need a WiPay merchant account.

1. Visit [wipaycaribbean.com](https://wipaycaribbean.com) and sign up for a merchant account
2. Complete the verification process
3. Once approved, log in to your WiPay dashboard
4. Navigate to **Settings** → **API** to find your **Account Number**
5. Note your account number — you'll need it during plugin configuration

> **Tip**: WiPay provides sandbox credentials for testing. Request these from your WiPay account manager or through the merchant dashboard.

---

## Installation

### Method 1: WordPress Plugin Upload (Recommended)

1. Download the `wipay-woocommerce.zip` file
2. In your WordPress admin, go to **Plugins** → **Add New** → **Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual Upload via FTP/SFTP

1. Extract the `wipay-woocommerce.zip` file
2. Upload the entire `wipay-woocommerce` folder to `/wp-content/plugins/`
3. In WordPress admin, go to **Plugins** and click **Activate** next to "WiPay WooCommerce Payment Gateway"

### Method 3: From GitHub

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Georgethe3rd-alt/WiPay.git wipay-woocommerce
```

Then activate in WordPress admin → Plugins.

---

## Configuration

After activating the plugin:

1. Go to **WooCommerce** → **Settings** → **Payments**
2. Find **WiPay Caribbean** in the list of payment methods
3. Click **Manage** (or the toggle to enable, then Manage)

### Settings Explained

| Setting | Description | Example |
|---------|-------------|---------|
| **Enable/Disable** | Turn the payment method on or off | ✅ Enabled |
| **Title** | What customers see at checkout | "Pay with Card (WiPay)" |
| **Description** | Short text below the title at checkout | "Secure credit card payment via WiPay" |
| **Account Number** | Your WiPay merchant account number | `1234567890` |
| **Environment** | Sandbox for testing, Live for real payments | Sandbox / Live |
| **Country** | Your WiPay-registered country | Trinidad & Tobago |
| **Currency** | Auto-fills based on country (editable) | TTD |
| **Fee Structure** | Who pays the processing fee | Customer pays / Merchant absorbs |
| **Debug Mode** | Enable detailed logging for troubleshooting | ☐ Disabled (enable when needed) |

### Country & Currency Mapping

When you select a country, the currency auto-populates:

| Country | Default Currency |
|---------|-----------------|
| Trinidad & Tobago | TTD |
| Jamaica | JMD |
| Barbados | BBD |
| Saint Lucia | XCD |
| Grenada | XCD |
| Guyana | GYD |

You can manually override the currency if needed (e.g., to charge in USD).

---

## Testing with Sandbox

**Always test in Sandbox mode before going live.**

1. Set **Environment** to **Sandbox**
2. Enter your sandbox Account Number (if different from live)
3. Save settings
4. Place a test order on your store
5. You'll be redirected to WiPay's sandbox payment page
6. Use test card details provided by WiPay
7. Complete the payment
8. Verify the order status updates correctly in WooCommerce

### What to Test

- [ ] Successful payment → order moves to "Processing"
- [ ] Failed/cancelled payment → order moves to "Failed"
- [ ] Customer receives order confirmation email
- [ ] Order notes show payment details (transaction ID)
- [ ] Webhook callback works (check WooCommerce logs)

### Viewing Logs

If Debug Mode is enabled:

1. Go to **WooCommerce** → **Status** → **Logs**
2. Select the log file starting with `wipay-woocommerce`
3. Review entries for payment requests, callbacks, and any errors

---

## Going Live Checklist

Before accepting real payments, confirm:

- [ ] **SSL Certificate** — Your site must use HTTPS (required for live payments)
- [ ] **WiPay Account Approved** — Your merchant account is fully verified and active
- [ ] **Account Number** — Updated to your live account number
- [ ] **Environment** — Switched from Sandbox to **Live**
- [ ] **Test Order** — Place and complete at least one real transaction
- [ ] **Webhook URL** — Accessible from the internet (not behind auth/firewall)
- [ ] **Debug Mode** — Disabled (unless actively troubleshooting)
- [ ] **Currency** — Matches your WiPay account settings
- [ ] **Fee Structure** — Set to your preference

---

## How Payments Work

Here's what happens when a customer pays:

1. **Customer clicks "Place Order"** — WooCommerce creates the order with status "Pending Payment"
2. **Redirect to WiPay** — Customer is securely redirected to WiPay's hosted payment page
3. **Customer enters card details** — On WiPay's PCI-compliant secure page
4. **Payment processed** — WiPay processes the card transaction
5. **Customer redirected back** — Returns to your store's thank-you page
6. **Webhook confirmation** — WiPay sends a server-to-server confirmation (backup verification)
7. **Order updated** — Status changes to "Processing" (success) or "Failed" (declined)

The webhook ensures payment is confirmed even if the customer closes their browser before being redirected back.

---

## Managing Orders

### Order Statuses

| Status | Meaning |
|--------|---------|
| Pending Payment | Order created, awaiting payment at WiPay |
| Processing | Payment received successfully |
| Failed | Payment was declined or cancelled |

### Order Notes

Every payment event is recorded in the order notes:

- "Awaiting WiPay payment" — when the order is created
- "WiPay payment successful. Transaction ID: XXX (via return/webhook)" — on success
- "WiPay payment failed. Reason: XXX" — on failure

### Refunds

WiPay does not currently support automated refunds via API. To refund a customer:

1. Log in to your [WiPay Merchant Dashboard](https://wipaycaribbean.com)
2. Find the transaction using the Transaction ID (shown in order notes)
3. Process the refund through WiPay's interface
4. Manually update the order status in WooCommerce if needed

---

## Troubleshooting

### Payment page not loading

- Verify your Account Number is correct
- Check that Environment matches your WiPay account type
- Ensure your site has a valid SSL certificate
- Check WooCommerce logs for error details

### Order stays "Pending" after payment

- The webhook may not be reaching your server
- Check that your site is publicly accessible (not password-protected or behind maintenance mode)
- Ensure no security plugin is blocking POST requests to `/wc-api/wipay_webhook`
- Enable Debug Mode and check logs for webhook entries

### "Payment configuration error" at checkout

- Your WiPay Account Number is missing or empty
- Go to WooCommerce → Settings → Payments → WiPay Caribbean and enter your account number

### Customer sees error after returning from WiPay

- The return URL may be blocked by a caching plugin
- Add `/wc-api/*` to your caching plugin's exclusion list
- Check that permalinks are set correctly (Settings → Permalinks → Save)

### Logs show no activity

- Debug Mode may be disabled — enable it in the gateway settings
- Check that WooCommerce logging is working (WooCommerce → Status → Logs should show files)

---

## FAQ

**Q: Is this plugin PCI compliant?**
A: Yes. Card details are entered only on WiPay's hosted payment page, which is PCI DSS compliant. Your store never handles raw card data.

**Q: Can I accept payments in USD?**
A: Yes. Override the auto-populated currency by manually entering "USD" in the Currency field.

**Q: Does this work with WooCommerce Subscriptions?**
A: Not currently. The plugin supports one-time payments only. Subscription support may be added in a future version.

**Q: Can I use this alongside other payment gateways?**
A: Absolutely. WooCommerce allows multiple active payment methods. Customers can choose their preferred option at checkout.

**Q: What happens if the customer's browser closes during payment?**
A: The server-to-server webhook ensures the order is updated regardless of whether the customer is redirected back to your site.

**Q: Which countries are supported?**
A: Trinidad & Tobago, Jamaica, Barbados, Saint Lucia, Grenada, and Guyana.

**Q: Is there a transaction limit?**
A: Transaction limits are set by WiPay based on your merchant account. Check your WiPay dashboard for details.

**Q: How do I get support?**
A: Contact WiPay support at support@wipaycaribbean.com or visit [wipaycaribbean.com](https://wipaycaribbean.com).
