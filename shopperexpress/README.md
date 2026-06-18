# Shopperexpress WordPress Theme

Custom WordPress theme for automotive dealer sites. Supports two inventory modes: ACF-based (standard WP posts) and Intice Nexus API mode (external REST API as the data source).

---

## Requirements

- PHP 8.0.2+
- Node 14.17.3+
- WordPress 6.0+
- ACF PRO 6.x+
- Composer

---

## Setup

```bash
composer install
cd assets && npm install && npm run build
```

---

## Operating Modes

### Standard mode (ACF)

Vehicle data lives in WP custom post types (`listings`, `used-listings`) with ACF fields. Single pages use `single-listings.php` / `single-used-listings.php`. Shortcodes (`[year]`, `[make]`, `[price]`, etc.) pull from ACF.

### API mode (Intice Nexus)

Enabled via WP option `api_mode = 1`. Vehicle data comes from the Intice Nexus REST API — no WP posts for inventory.

Check with: `\App\is_api_mode(): bool`

---

## Intice Nexus API Integration

### Components

| File | Purpose |
|---|---|
| `inc/Components/Api/class-intice-api-client.php` | Singleton HTTP client. Transient cache with stale-while-revalidate. |
| `inc/Components/Api/class-intice-vdp.php` | Custom rewrite rules + `template_redirect` intercept for VDP URLs. |
| `inc/Components/Api/class-intice-rest.php` | WP REST endpoints for API mode. |

### API Client

```php
$client = \App\Components\Api\Intice_Api_Client::instance();

$client->get_vehicles(array $filters);              // list vehicles
$client->get_vehicle(string $vin);                  // single vehicle by VIN
$client->get_meta();                                // filter options
$client->update_vehicle(string $vin, array $data);  // PATCH vehicle fields to Nexus
```

`update_vehicle()` sends `PATCH /api/v1/vehicles/{vin}`. Payload fields go nested under a `payload` key and are merged server-side (not replaced). Every save is logged in the Nexus Activity Log with the originating site name and a field-level before/after diff.

### VDP URLs

Format: `/listings/{condition}-{year}-{make}-{model}-{body_style}-{trim}-{vin}-{stock}/`

Template: `template-parts/single/single-listings-api.php`

The template mirrors `single-listings.php` HTML so all existing CSS/JS continues to work without changes.

### Yoast SEO (API VDP)

Since API VDP pages have no real WP post, SEO meta is injected via Yoast filters before `get_header()`. Auto-generated from vehicle data by default. Override per vehicle via payload fields:

| `payload` key | Overrides |
|---|---|
| `seo_title` | `<title>`, og:title, twitter:title |
| `seo_description` | meta description, og:description, twitter:description |
| `seo_image` | og:image, twitter:image |

Edit in the VDP edit modal → **SEO** tab.

### VDP Edit Modal

`template-parts/modal-edit-api.php` — visible to logged-in admin users only.

**Tabs:** General info · Payment · Pricing · Mechanical · Fuel · Description · SEO

Saves via `admin-ajax.php` → action `wps_api_save_vehicle` → `PATCH /api/v1/vehicles/{vin}` on Nexus.

### API Sub-templates (`template-parts/api/`)

| File | Purpose |
|---|---|
| `gallery.php` | Image gallery |
| `description.php` | Vehicle description |
| `vdp_description.php` | Feature/spec list — hidden when empty |
| `payment_list.php` / `payment_list_new.php` | Payment info from payload |
| `conversion_block.php` | CTA / conversion block |
| `unlock.php` | Unlock form (price reveal) |

### Favorites (API mode)

Renders `<button class="api-favorite-button" data-postid="{VIN}">` instead of the `[favorite_button]` shortcode.

AJAX actions: `wps_api_favorite_toggle`, `wps_api_favorites_list`, `wps_api_render_favorites`

### Shortcodes in API mode

`ConversionBlock.php` replaces shortcode placeholders via regex callback using `$_sc_map` (shortcode name → vehicle field). Values wrapped in `<span class="js-is-empty">value</span>`. `class-shortcode.php` contains only ACF/WP logic — no API branches.

### AJAX Handlers (API mode, logged-in only)

| Action | Handler | Purpose |
|---|---|---|
| `wps_api_save_vehicle` | `api_save_vehicle()` | Save vehicle edits → Nexus PATCH |
| `wps_api_favorite_toggle` | `api_favorite_toggle()` | Toggle favorite by VIN |
| `wps_api_favorites_list` | `api_favorites_list()` | Return user favorite VINs |
| `wps_api_render_favorites` | `api_render_favorites()` | Render favorite vehicle cards |

---

## Architecture

### Bootstrapping

`functions.php` → Composer autoloader → Auryn DIC → `App\Components\Base\Theme::init($injector)`.

Every feature is a **Theme Component**: a class implementing `App\Components\Base\Theme_Component` with a single `init()` method, resolved through the DIC.

### Namespace / Autoloading

PSR-4: `App\` → `inc/`. Example: `App\Components\Base\Scripts` = `inc/Components/Base/class-scripts.php`.

### Template Pattern (ACF mode)

Three layers for Flexible Content / Gutenberg blocks:

1. `template-parts/acf/acf-{layout}.php` — FC wrapper, calls `get_sub_field()`
2. `template-parts/acf-blocks/{slug}.php` — Gutenberg block wrapper, calls `get_field()`
3. `template-parts/acf-shared/{slug}.php` — single HTML source, reads only from `$args`

### Key Components

| Component | Location | Purpose |
|---|---|---|
| `class-cpt.php` | Base/ | Registers all custom post types |
| `class-acf.php` | Base/ | ACF integration + custom DB table |
| `class-ajax.php` | Base/ | Admin-ajax handlers (incl. API mode) |
| `class-rest.php` | Base/ | WP REST endpoints |
| `class-shortcode.php` | Base/ | Shortcodes (ACF/WP only) |
| `class-ai.php` / `class-ai-crawler.php` | Base/ | OpenAI embeddings + crawl queue |
| `class-chromedata-client.php` | Base/ | Chromedata API (VIN decode) |
| `class-popup-resolver.php` | Base/ | Popup/modal logic |
| `class-import-monitor*.php` | Base/ | WP All Import health tracking |

### Custom Post Types

`listings`, `used-listings`, `offers`, `finance-offers`, `lease-offers`, `service-offers`, `conditional-offers`, `research`

### SOC (Operation Center)

Admin dashboard at `inc/Components/SOC/`. Each panel implements `App\Components\SOC\Contracts\SOC_Module`. AJAX via `class-soc-ajax.php`. Assets: `soc.scss` → `dist/soc.css`, `soc.js` → `dist/soc.js`.

---

## Frontend

### CSS / SCSS

BEM + Stylelint. Under `assets/src/styles/`:
- `base/` — functions, mixins, helpers, WP reset
- `components/` — UI components
- `layout/` — header, footer, page structure
- `vendors/` — Bootstrap 4, FancyBox, Slick, include-media

Entry points: `style.scss` (frontend), `soc.scss` (admin), `login.scss`.

### JavaScript

Entry points: `assets/src/js/static/app.js` (frontend), `assets/src/js/soc/soc.js` (admin).

Babel + ES modules. ESLint (Airbnb config). Webpack 5 with SVG spritemap + WebP image optimization.

```bash
cd assets
npm run dev      # dev server with HMR
npm run build    # production build → assets/dist/
```

---

## External Integrations

| Integration | Location | Notes |
|---|---|---|
| **Intice Nexus** | `inc/Components/Api/` | Vehicle inventory API (read + write via PATCH) |
| **Twilio** | `vendor/twilio/sdk` | SMS, loaded via Composer |
| **Chromedata** | `class-chromedata-client.php` | VIN decode |
| **OpenAI** | `class-ai.php` | Embeddings API |
| **ACF PRO** | WordPress plugin | Field groups versioned in `acf-json/` |