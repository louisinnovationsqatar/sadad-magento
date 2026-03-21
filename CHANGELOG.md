# Changelog

All notable changes to `louis-innovations/sadad-magento` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-03-21

### Added

- Initial release of the SADAD Payment Gateway module for Magento 2.4+
- Support for three checkout modes: v1.1 (standard redirect), v2.1 (enhanced redirect), v2.2 (embedded)
- Full refund support via the SADAD API (full refunds only, as per SADAD API limitations)
- Webhook receiver at `POST /rest/V1/sadad/webhook` with SDK signature validation
- Callback handler at `POST /sadad/payment/callback`
- Admin configuration panel: merchant ID, secret key, website, environment, checkout mode, language, order prefix, logging, debug
- Read-only display of Webhook URL and Callback URL in admin with one-click copy
- Audit log table `sadad_transactions` created via InstallSchema
- Knockout.js checkout component for Magento 2 checkout
- SADAD payment info block for order/invoice views
- English (`en_US`) and Arabic (`ar_SA`) translations
- Built by [Louis Innovations](https://www.louis-innovations.com)
