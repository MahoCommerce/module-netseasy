# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Maho_NetsEasy is a payment gateway module integrating **Nexi Nets Easy** (formerly DIBS) into **Maho Commerce** (OpenMage/Magento 1 fork). It is a standalone composer package (`mahocommerce/module-netseasy`, type `maho-module`) installed into a Maho store.

Requires PHP >= 8.3. Uses `declare(strict_types=1)` and PHP 8 features (named arguments, `#[\Override]`).

## Architecture

### Payment Flow

1. **Initialization** (`Model/Payment.php::initialize`) — Creates a Nets payment session via API, stores `netseasy_payment_id` in payment additional info, writes a tracking row to `netseasy_payment` table
2. **Checkout** — Two flows controlled by `checkout_flow` config:
   - **Embedded**: Customer stays on store (`PaymentController::checkoutAction`), JS widget in `checkout.phtml`/`checkout.js`
   - **Hosted**: Customer redirected to Nets hosted page (`PaymentController::redirectAction`)
3. **Return** (`PaymentController::returnAction`) — Handles both GET (hosted) and POST/AJAX (embedded) returns, verifies payment status via API
4. **Webhooks** (`WebhookController`) — Async notifications from Nets for checkout.completed, charge.created, refund.completed, cancel.created, reservation.created
5. **Observer** (`Model/Observer.php`) — `sales_order_place_after` links order_id in tracking table and updates Nets payment reference with order increment ID

### Key Classes

- `Model/Payment.php` — Main payment method model extending `Mage_Payment_Model_Method_Abstract`. Handles authorize, capture, refund, void
- `Model/Api/Client.php` — HTTP client wrapping Symfony HttpClient for Nets API calls. Logs to `netseasy.log` when debug enabled
- `Model/Api/Payment.php` — API operations (create, get, charge, refund, cancel, terminate, updateReference)
- `Model/Api/Dto/` — Request/response DTOs (CreatePaymentRequest, ChargeRequest, RefundRequest, OrderItem, PaymentResponse, etc.)
- `Helper/Data.php` — Config accessors. All config under `payment/netseasy/` path
- `Model/Locale.php` — Currency/country support (ISO2↔ISO3 conversion, supported currencies)

### Database

Single custom table `netseasy_payment` (setup in `sql/maho_netseasy_setup/install-1.0.0.php`):
- Tracks `payment_id` ↔ `quote_id` ↔ `order_id` mapping
- `order_id` is nullable — populated by observer after order placement
- Used by webhooks to resolve store context and find orders

### API Integration

- Test endpoint: `https://test.api.dibspayment.eu`
- Live endpoint: `https://api.dibspayment.eu`
- All amounts use **minor units** (cents) — see `OrderItem::toMinorUnits()`
- Webhook auth uses `Authorization` header compared with `hash_equals()`

### Frontend

- Layout: `app/design/frontend/base/default/layout/maho_netseasy.xml`
- Templates: `app/design/frontend/base/default/template/maho/netseasy/`
- JS: `public/js/maho/netseasy/checkout.js` (embedded checkout widget)

## Conventions

- Module alias is `netseasy` (e.g., `Mage::helper('netseasy')`, `Mage::getModel('netseasy/payment')`)
- All PHP files use `declare(strict_types=1)`
- Method overrides use `#[\Override]` attribute
- Private helper accessors pattern: `private function getHelper()`, `private function getApiPayment()`
- Logging goes to `netseasy.log` file
- Translations via `app/locale/en_US/Maho_NetsEasy.csv`
- License header: SPDX format. PHP/phtml/JS/XML carry `SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>` and `SPDX-License-Identifier: OSL-3.0`. PHP/phtml keep the SPDX block inside the top `/** */` docblock with `@package Maho_NetsEasy`; XML uses a plain `<!-- -->` comment (no `@package`); JS uses `//` line comments.
