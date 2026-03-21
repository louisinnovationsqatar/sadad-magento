# LouisInnovations_Sadad — SADAD Payment Gateway for Magento 2

[![Magento 2.4+](https://img.shields.io/badge/Magento-2.4%2B-orange.svg)](https://magento.com)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> Built by [Louis Innovations](https://www.louis-innovations.com)

Integrates [SADAD Qatar](https://www.sadad.qa) — Qatar's national payment gateway — into Magento 2.4+ stores. Powered by the [louis-innovations/sadad-php-sdk](https://github.com/louis-innovations/sadad-php-sdk).

---

## Features

- Three checkout modes: **v1.1** (standard redirect), **v2.1** (enhanced multi-product), **v2.2** (embedded secure page)
- Full refunds via the SADAD API
- Webhook receiver with signature validation
- Admin configuration: merchant ID, secret key, environment, language, order prefix, logging
- One-click copy for Webhook URL and Callback URL in admin
- Audit log table (`sadad_transactions`)
- English + Arabic translations (`en_US` / `ar_SA`)
- Compatible with Magento 2.4.x, PHP 8.1+

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.1 |
| Magento Open Source / Adobe Commerce | ^2.4.0 |
| louis-innovations/sadad-php-sdk | ^1.0 |

---

## Installation

### Via Composer (recommended)

```bash
composer require louis-innovations/sadad-magento
php bin/magento module:enable LouisInnovations_Sadad
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Manual installation

1. Copy the module directory to `app/code/LouisInnovations/Sadad/`
2. Run the commands above from step 2 onward.

---

## Configuration

Navigate to **Stores > Configuration > Sales > Payment Methods > SADAD Payment Gateway**.

| Setting | Description |
|---------|-------------|
| Enable | Activate the payment method |
| Title | Label shown to customers at checkout |
| Merchant ID | Your 7-digit SADAD merchant ID |
| Secret Key | SADAD secret key (stored encrypted) |
| Website Identifier | Website code registered with SADAD |
| Environment | `Test` (sandbox) or `Live` (production) |
| Checkout Mode | `v1.1`, `v2.1`, or `v2.2` — see descriptions in admin |
| Gateway Language | `English` or `Arabic` |
| Order ID Prefix | Prefix appended to Magento order IDs (e.g. `MGT-`) |
| Logging | Write requests/responses to `var/log/sadad.log` |
| Debug | Verbose debug logging (disable in production) |
| **Webhook URL** | Copy this into your SADAD merchant portal |
| **Callback URL** | Auto-configured; shown for reference |

---

## URLs

After installation, your store exposes these endpoints:

| Purpose | URL |
|---------|-----|
| Customer redirect | `https://yourstore.com/sadad/payment/redirect` |
| Payment callback (POST) | `https://yourstore.com/sadad/payment/callback` |
| Webhook receiver (POST) | `https://yourstore.com/rest/V1/sadad/webhook` |

Configure the **Webhook URL** in your SADAD merchant portal under Notification / IPN settings.

---

## Checkout Flow

```
Customer clicks "Pay with SADAD"
  --> Magento creates order (pending_payment)
  --> Controller redirects to SADAD gateway (auto-submit form)
  --> Customer completes payment on SADAD
  --> SADAD POSTs to Callback URL (customer redirected)
  --> SADAD POSTs to Webhook URL (server-to-server confirmation)
  --> Order updated to "processing"
```

---

## Refunds

Only **full refunds** are supported (SADAD API limitation). To refund:

1. Open the order in Magento Admin.
2. Create a credit memo for the full amount.
3. The module will call the SADAD refund API automatically.

Refunds are available within 90 days of the original transaction.

---

## Troubleshooting

- **Logs**: `var/log/sadad.log` (enable logging in admin config)
- **Webhook not received**: Verify the webhook URL is accessible from the internet and configured in the SADAD portal
- **Merchant ID validation**: Must be exactly 7 digits
- **Test cards**: Use the credentials provided in your SADAD sandbox account

---

## License

MIT — see [LICENSE](LICENSE)

---

## Support

- GitHub Issues: [github.com/louis-innovations/sadad-magento/issues](https://github.com/louis-innovations/sadad-magento/issues)
- Email: [info@louis-innovations.com](mailto:info@louis-innovations.com)
- Website: [louis-innovations.com](https://www.louis-innovations.com)
