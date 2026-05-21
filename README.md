# Maho NetsEasy

![Maho Commerce](https://img.shields.io/badge/Maho_Commerce-module-orange)
![License](https://img.shields.io/badge/license-OSL--3.0-blue)
![PHP](https://img.shields.io/badge/php-%3E%3D8.3-8892BF)
![PHPStan Level](https://img.shields.io/badge/PHPStan-level%208-brightgreen)

**Nexi Nets Easy** (formerly DIBS) payment gateway integration for [Maho Commerce](https://mahocommerce.com).

Accept payments via cards, wallets, and local payment methods across the Nordics and Europe — directly in your Maho store.

## Features

- **Two checkout flows** — Embedded (on-site widget) or Hosted (redirect to Nets payment page)
- **Authorize & Capture** — Choose between authorize-only or immediate capture
- **Full order lifecycle** — Capture, refund (partial and full), and void operations from the Maho admin
- **Webhook support** — Real-time async notifications for payment events
- **Line item details** — Optionally send cart items to Nets for enhanced payment pages
- **Multi-currency** — Supports DKK, SEK, NOK, EUR, USD, GBP, CHF, and PLN
- **Multi-locale** — Nets checkout widget automatically displayed in the customer's language (15 locales)
- **Merchant consumer data** — Pre-fill customer info from Maho or let Nets collect it
- **Country restrictions** — Allow payments from all or specific countries
- **Debug logging** — Toggle detailed API logging to `var/log/netseasy.log`

## Requirements

- PHP >= 8.3
- Maho Commerce
- A [Nexi Nets Easy](https://www.nexigroup.com/en/solutions/e-commerce/) merchant account

## Installation

```bash
composer require mahocommerce/module-netseasy
```

Clear the cache after installation:

```bash
php maho cache:flush
```

## Configuration

Navigate to **System > Configuration > Payment Methods > Nets Easy** in the Maho admin panel.

| Setting | Description | Default |
|---|---|---|
| **Enabled** | Activate the payment method | No |
| **Title** | Payment method name shown at checkout | Nets Easy |
| **Environment** | Test or Live mode | Test |
| **Test Secret Key** | API secret key for the test environment | — |
| **Test Checkout Key** | Checkout key for the test environment | — |
| **Live Secret Key** | API secret key for the live environment | — |
| **Live Checkout Key** | Checkout key for the live environment | — |
| **Checkout Flow** | Embedded (on-site) or Hosted (redirect) | Embedded |
| **Payment Action** | Authorize Only or Authorize and Capture | Authorize Only |
| **Terms URL** | Link to your store's terms and conditions | — |
| **Webhook Secret** | Token to verify incoming webhook requests from Nets | — |
| **Merchant Handles Consumer Data** | Send customer data from the store instead of letting Nets collect it | Yes |
| **Send Order Items** | Include line item details in the payment request | Yes |
| **Applicable Countries** | All countries or specific countries only | All |
| **Minimum Order Total** | Minimum order amount for this method | — |
| **Maximum Order Total** | Maximum order amount for this method | — |
| **Debug** | Log API requests/responses to `var/log/netseasy.log` | No |
| **Sort Order** | Display position among payment methods | 100 |

## Webhooks

To receive real-time payment notifications, configure webhooks in your Nets Easy merchant portal with the following URL:

```
https://your-store.com/netseasy/webhook/index
```

Set the **Authorization** header to match the **Webhook Secret** configured in Maho.

The module handles these webhook events:
- `payment.checkout.completed`
- `payment.charge.created.v2`
- `payment.refund.completed`
- `payment.cancel.created`
- `payment.reservation.created.v2`

## Checkout Flows

### Embedded Checkout

The customer stays on your store. A JavaScript widget is loaded inline on the checkout page, providing a seamless payment experience without redirects.

### Hosted Payment Page

The customer is redirected to a Nets-hosted payment page. After completing or cancelling payment, they are returned to your store.

## How It Works

1. **Order placement** — A Nets payment session is created via the API and a tracking record is stored in the `netseasy_payment` table
2. **Payment** — The customer completes payment via the embedded widget or hosted page
3. **Return** — The customer is returned to the store, and the payment status is verified via the API
4. **Webhooks** — Asynchronous notifications confirm the final payment state
5. **Admin operations** — Capture, refund, and void are available from the order view in the admin panel

## Supported Currencies

DKK, SEK, NOK, EUR, USD, GBP, CHF, PLN

## Checkout Widget Locales

The Nets Easy checkout widget (embedded or hosted) is automatically displayed in the customer's language based on the store locale. Supported widget languages:

Swedish, Norwegian, Danish, Finnish, German (DE/AT/CH), English (GB/US), Dutch, French (FR/BE), Spanish, Italian, Polish

## License

This module is licensed under the [Open Software License v3.0](LICENSE.txt).

## Links

- [Maho Commerce](https://mahocommerce.com)
- [Nexi Nets Easy Documentation](https://developer.nexigroup.com/nexi-checkout/en-EU/docs/)
- [Nexi Nets Easy API Reference](https://developer.nexigroup.com/nexi-checkout/en-EU/api/)
