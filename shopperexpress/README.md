# Shopperexpress WordPress Theme

Custom WordPress theme for automotive dealership websites built on top of ACF PRO, WP Forms, and the Intice platform.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.0.2+ |
| WordPress | 6.0+ |
| ACF PRO | 6.x+ |
| Node.js | 14.17.3+ |
| Composer | any recent |

---

## Quick Start

```bash
# PHP dependencies
composer install

# Frontend dependencies & production build
cd assets/
npm install
npm run build
```

---

## Development

```bash
# Frontend (run from assets/)
npm run dev            # webpack dev server with HMR
npm run build          # production build → assets/dist/
npm run lint:fix:css   # fix SCSS lint issues
npm run lint:fix:js    # fix JS lint issues

# PHP code style
composer cs            # PHPCS with WordPress Coding Standards
```

---

## Architecture Overview

The theme uses a **Dependency Injection** pattern via [Auryn](https://github.com/rdlowrey/auryn). Every feature is a **Theme Component** — a class implementing `App\Components\Theme_Component` with a `register()` method. All components are declared in `inc/Components/class-theme.php` and resolved through the DIC.

**PSR-4 autoloading:** namespace `App\` → `inc/`. Example: `App\Components\Base\Scripts` → `inc/Components/Base/class-scripts.php`.

### Template Rendering (Three-Layer Pattern)

ACF Flexible Content layouts and Gutenberg blocks share HTML through three layers:

1. `template-parts/acf/acf-{layout}.php` — Flexible Content wrapper, calls `get_sub_field()`
2. `template-parts/acf-blocks/{slug}.php` — Gutenberg block wrapper, calls `get_field()`
3. `template-parts/acf-shared/{slug}.php` — Single source of truth for HTML, reads only `$args`

### Custom Post Types

`listings`, `used-listings`, `offers`, `finance-offers`, `lease-offers`, `service-offers`, `conditional-offers`, `research`

### Operating Modes

The theme has a dual mode controlled by `is_api_mode()` (WP option `api_mode`):

- **Standard mode** — vehicle data from ACF fields on WP posts
- **API mode** — vehicle data from Intice Nexus REST API (`inc/Components/Api/`)

---

## Operation Center (SOC)

A unified WordPress admin dashboard at **WP Admin → Operation Center**.

| Panel | Purpose |
|---|---|
| System Status | PHP/WP environment diagnostics |
| API Health | Internal + third-party API health probes |
| Cache Manager | Multi-layer cache management & post-type cache regen |
| Cron Manager | Scheduled task monitoring, manual trigger, reschedule |
| Database Health | DB stats, cleanup tools |
| Developer Tools | VIN checker via Chromedata |
| Import Monitor | WP All Import health tracking |
| API Settings | Intice Nexus API mode, credentials, cache controls |
| **Lead Delivery** | ADFXML lead delivery settings, log, retry |
| **VDR Requests** | Vehicle Detail Report API call log & statistics |

---

## ADF Lead Delivery

Leads submitted via WP Forms or direct AJAX can be delivered by **email** (legacy) or **API** (Intice IO endpoint). The delivery method and all related settings are configured in **Operation Center → Lead Delivery**.

### How it works

1. A lead form is submitted (WP Forms or AJAX action `submit_adf_lead` / `adf`)
2. `wps_build_adf_xml()` builds the ADFXML string
3. `wps_dispatch_adf()` checks `adf_delivery_method` option:
   - **email** → `wps_send_adf_email()` → `wp_mail()` to configured recipients
   - **api** → `ADF_Api_Client::send()` → `POST` to Intice IO endpoint
4. Every attempt is logged to `{prefix}_adf_lead_log`

### Reliability features

| Feature | How |
|---|---|
| Fallback to email | Sends via `wp_mail()` after API failure when enabled |
| Auto-retry | `ADF_Cron` runs every 15 min via WP Cron, retries `failed` rows up to `adf_max_retries` |
| Manual retry | Retry button in SOC Lead Delivery log table |
| Admin notification | Email to configured address on every API failure |
| Duplicate prevention | Blocks same email+phone within configurable time window |

### API payload

```json
{
  "payload_type": "adfxml",
  "source": "wordpress",
  "adfxml": "<?xml version=\"1.0\" encoding=\"utf-8\"?>..."
}
```

Authentication: `Authorization: Bearer {secret_key}` (key stored AES-256-CBC encrypted).

### WP Forms integration

Set comma-separated WP Forms IDs in SOC → Lead Delivery → "WP Forms — ADF Form IDs". Submissions for those forms automatically trigger ADF delivery. Field mapping is by WP Forms field type (`name`, `email`, `phone`, `textarea`/`text` by label).

### Lead log columns

`id` · `submitted_at` · `site_name` · `form_name` · `lead_source` · `first_name` · `last_name` · `email` · `phone` · `delivery_method` · `api_endpoint` · `response_code` · `response_body` · `status` · `retry_count` · `error_message` · `adfxml_payload`

---

## Key Files

```
inc/
  theme-functions.php                        # wps_build_adf_xml, wps_dispatch_adf, WP Forms hook
  Components/
    class-theme.php                          # Component registry (DIC)
    Base/
      class-acf.php                          # ACF + DB table migrations (adf_lead_log, vdr_log)
      class-ajax.php                         # Public AJAX handlers (submit_adf_lead, get_pdf, …)
      class-adf-api-client.php               # Intice IO HTTP client, key encryption
      class-adf-cron.php                     # WP Cron auto-retry for failed leads
      class-json-ld.php                      # Vehicle JSON-LD schema (incl. additionalProperty)
      class-ai-vdp.php                       # AI VDP description via OpenAI (customizable prompts)
    SOC/
      class-soc.php                          # SOC bootstrap, menu registration
      class-soc-ajax.php                     # SOC AJAX dispatcher (30+ actions)
      Modules/
        class-lead-delivery.php              # Lead Delivery SOC module
        class-vdr-requests.php               # VDR Requests SOC module
      views/
        lead-delivery.php                    # Settings + stats + log view
        lead-delivery-table.php              # Paginated log table partial
        vdr-requests.php                     # VDR stats + log view
        vdr-requests-table.php               # Paginated VDR log table partial

assets/src/
  styles/soc.scss                            # SOC admin styles (incl. Lead Delivery + modal)
  js/soc/soc.js                              # SOC admin JS (incl. SOC.bindLeadDelivery)
  js/static/app.js                           # Frontend JS (incl. VDR badge: GA, error modal)
```

---

## ACF JSON

Field group definitions are stored in `acf-json/` and version-controlled. Always sync field groups here rather than relying solely on database state.

---

## VDR (Vehicle Detail Report) Badge

The `additional_custom_badges` ACF repeater on Listings pages supports an `action = api` badge type that requests a Chromedata PDF report.

### Features

| Feature | Details |
|---|---|
| GA event tracking | Fires `gtag('event', 'vdr_download', {...})` on click; label configurable per badge via ACF field `ga_event_label` |
| Error modal | Bootstrap modal shown on API failure; message configurable globally via ACF field `vdr_error_message` (Options page) |
| Hide on error | On first API failure, a WP transient (`vdr_error_{VIN}`, 24h TTL) is set; badge is suppressed server-side for all users until the next successful request clears the transient |
| Request log | Every API call (hit or miss, cache or live) is logged to `{prefix}_vdr_log` |

### VDR log columns

`id` · `requested_at` · `vin` · `dealer_name` · `site_name` · `result` · `http_code` · `from_cache`

---

## AI VDP Description

When the **Enable AI VDP Description Population** toggle is on (Theme Options), vehicle detail pages auto-populate a description via OpenAI `gpt-4o-mini`.

The system prompt and user prompt template are customizable directly in Theme Options (ACF fields `ai_vdp_system_prompt` and `ai_vdp_user_prompt`). Placeholders:

- System prompt: `{dealer}`, `{city}`
- User prompt: `{vehicle}` (replaced with year/make/model/trim/price/mileage/color summary)

If the fields are empty, built-in defaults are used.

---

## Vehicle JSON-LD Schema

All VDP pages (standard ACF mode and API mode) output a `schema.org/Vehicle` JSON-LD block with `additionalProperty` entries populated from the vehicle's **Highlighted Features** (`features_items` repeater). Features are sorted by `ranking` (desc), truncated by the configured `limit_feature_list` value, and text can be overridden per-feature via `feature_list_chromedata`.

---

## External Integrations

| Service | Location | Notes |
|---|---|---|
| Twilio | `vendor/twilio/sdk` | SMS |
| Chromedata / JD Power | `class-chromedata-client.php` | VIN decode + VDR PDF API |
| OpenAI | `class-ai.php` + `class-ai-crawler.php` + `class-ai-vdp.php` | Embeddings, crawl pipeline, AI VDP descriptions |
| Intice Nexus | `inc/Components/Api/class-intice-api-client.php` | Vehicle inventory API |
| Intice IO Leads | `inc/Components/Base/class-adf-api-client.php` | ADFXML lead delivery API |
