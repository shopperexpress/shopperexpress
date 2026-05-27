# ShopperExpress — WordPress Theme

Custom WordPress theme for a car/auto shopper platform. Handles vehicle listings, dealer offers, finance/leasing, and editorial research content.

## Table of contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Commands](#commands)
- [Structure](#structure)
- [Architecture](#architecture)
- [Operation Center (SOC)](#operation-center-soc)
- [ACF Flexible Content & Gutenberg Blocks](#acf-flexible-content--gutenberg-blocks)
- [Autoloading & DIC](#autoloading--dic)
- [Coding Standards](#coding-standards)
- [Frontend Build](#frontend-build)
- [Post Types](#post-types)
- [AI Chat System](#ai-chat-system)

---

## Overview

ShopperExpress is a production WordPress theme built on a component-based PHP architecture. It uses:

- **PSR-4 class autoloading** via Composer (`App\` namespace → `inc/`)
- **Auryn DIC** for dependency injection
- **ACF** for all content (Flexible Content layouts + Gutenberg blocks)
- **Webpack 5** for JS/SCSS compilation
- **WordPress Coding Standards** enforced via PHPCS

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.0.2 |
| Composer | any current |
| Node.js | ≥ 14.17.3 |
| WordPress | ≥ 6.0 |
| ACF PRO | ≥ 6.x |

---

## Installation

```bash
# 1. Place theme in wp-content/themes/shopperexpress
# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
cd assets
npm install
```

---

## Commands

### PHP

```bash
composer install       # install PHP dependencies
composer cs            # run PHPCS linter
```

### Frontend (run from /assets)

```bash
npm install            # install JS/CSS dependencies
npm run dev            # webpack dev watch + HMR
npm run build          # production build → assets/dist/
npm run lint:fix:css   # stylelint --fix
npm run lint:fix:js    # eslint --fix
```

---

## Structure

```
shopperexpress/
├── acf-json/                        # ACF Local JSON — field groups & block groups
│   ├── group_601ab60960670.json     # Main Flexible Content field group
│   ├── group_block_*.json           # ACF Block field groups (one per block)
│   └── ...
├── assets/                          # Frontend build system
│   ├── src/
│   │   ├── js/                      # Source JavaScript (ES modules)
│   │   └── scss/                    # Source SCSS (BEM)
│   ├── dist/                        # Compiled output — do not edit
│   ├── images/
│   │   └── block-previews/          # Block editor preview images (1200×600 JPEG)
│   ├── webpack.config.js
│   ├── babel.config.js
│   ├── postcss.config.js
│   ├── eslintrc.js
│   ├── stylelint.config.js
│   └── package.json
├── inc/                             # PHP theme logic (namespace App\)
│   └── Components/
│       ├── Base/                    # Core feature classes
│       │   ├── class-scripts.php    # Asset enqueueing
│       │   ├── class-menus.php      # Navigation menus
│       │   ├── class-cpt.php        # Custom post types
│       │   ├── class-acf.php        # ACF integration + custom tables
│       │   ├── class-admin.php      # WP admin customisation
│       │   ├── class-api.php        # REST API extensions
│       │   ├── class-ajax.php       # Admin-ajax handlers
│       │   ├── class-rest.php       # Custom REST endpoints
│       │   ├── class-ai.php         # Hybrid AI chat endpoint
│       │   ├── class-ai-crawler.php # Site content crawler for AI
│       │   ├── class-chromedata-client.php  # Shared Chromedata API client
│       │   ├── class-vin-admin.php           # VIN service (service methods only)
│       │   ├── class-import-monitor-tracker.php   # WP All Import hook tracker
│       │   ├── class-import-monitor-cron.php      # Import monitor cron schedule
│       │   ├── class-import-monitor-notifier.php  # Import failure/success alerts
│       │   └── ...
│       ├── SOC/                     # Operation Center — unified admin dashboard
│       │   ├── class-soc.php        # Bootstraps modules, registers menu + assets
│       │   ├── class-soc-ajax.php   # Single AJAX dispatcher (soc_nonce)
│       │   ├── class-soc-assets.php # Enqueues soc.css + soc.js + localises socData
│       │   ├── Contracts/
│       │   │   └── interface-soc-module.php  # SOC_Module interface
│       │   ├── Modules/             # One class per panel
│       │   │   ├── class-system-status.php
│       │   │   ├── class-api-health.php
│       │   │   ├── class-cron-manager.php
│       │   │   ├── class-cache-manager.php
│       │   │   ├── class-database-health.php
│       │   │   ├── class-developer-tools.php    # VIN Checker panel
│       │   │   ├── class-soc-import-monitor.php # Import Monitor panel
│       │   │   ├── class-log-viewer.php
│       │   │   ├── class-maintenance.php
│       │   │   ├── class-performance.php
│       │   │   └── class-security-snapshot.php
│       │   ├── Support/
│       │   │   ├── class-soc-cache.php    # Transient cache wrapper
│       │   │   ├── class-soc-logger.php   # File-based logger
│       │   │   ├── class-soc-response.php # AJAX response helpers
│       │   │   └── class-cache-registry.php
│       │   └── views/               # Panel HTML templates
│       │       ├── dashboard.php
│       │       ├── system-status.php
│       │       ├── api-health.php
│       │       ├── cron-manager.php
│       │       ├── cache-manager.php
│       │       ├── database-health.php
│       │       ├── developer-tools.php  # VIN Checker UI
│       │       ├── import-monitor.php
│       │       ├── log-viewer.php
│       │       ├── maintenance.php
│       │       ├── performance.php
│       │       └── security-snapshot.php
│       ├── Gutenberg/
│       │   ├── class-register-gutenberg-blocks.php  # Auto-registers ACF blocks
│       │   ├── class-custom-blocks-category.php     # Adds custom block category
│       │   └── class-gutenberg-color-palette.php    # Custom editor palette
│       ├── class-theme.php          # Bootstraps all components
│       └── class-theme-component.php  # Interface all components implement
├── template-parts/
│   ├── acf/                         # Flexible Content layout wrappers (thin)
│   ├── acf-blocks/                  # Gutenberg block render wrappers (thin)
│   ├── acf-shared/                  # Shared section markup — single source of truth
│   ├── components/                  # Reusable UI partials
│   └── single/                      # Single post-type partials
├── pages/                           # Page templates (template-*.php)
├── languages/                       # i18n .po/.mo files
├── vendor/                          # Composer packages — do not edit
├── functions.php                    # Theme entry point
├── header.php
├── footer.php
├── archive-listings.php             # Primary vehicle SRP
├── archive-used-listings.php
├── archive-offers.php
├── archive-*.php                    # Per-offer-type archives
├── single.php
├── search.php
├── style.css                        # Theme header + minimal global CSS
├── phpcs.xml.dist                   # PHPCS ruleset
└── composer.json
```

---

## Architecture

### Component system

All feature classes live in `inc/Components/` and implement `App\Components\Theme_Component`:

```php
interface Theme_Component {
    public function register(): void;
}
```

Components are registered in `Theme::get_theme_components()` and resolved through Auryn:

```php
// Access the DI container anywhere
App\injector()->make( Some_Class::class );
App\injector()->execute( '\App\Components\Some_Class::some_method' );
```

To add a new component: create a class that implements `Theme_Component`, then add it to the array in `Theme::get_theme_components()`.

### Text domain

`shopperexpress`

---

## Operation Center (SOC)

Operation Center is a unified admin dashboard (WordPress menu: **Operation Center**) that consolidates all site-health, maintenance, and developer tools in one place. Navigation uses the standard WordPress submenu — each panel is a separate submenu page.

### SOC_Module interface

Every panel implements `App\Components\SOC\Contracts\SOC_Module`:

```php
interface SOC_Module {
    public function get_slug(): string;   // URL key: soc-{slug}
    public function get_label(): string;  // Submenu + panel heading
    public function get_icon(): string;   // Dashicon class
    public function collect(bool $force_refresh = false): array; // Data gathering
    public function render(array $data): void;                   // HTML output
}
```

To add a panel: create a class implementing `SOC_Module` in `inc/Components/SOC/Modules/`, add a view in `inc/Components/SOC/views/`, then register it in `SOC::boot_modules()`.

### Panels

| Panel | Slug | Description |
|---|---|---|
| System Status | `system-status` | PHP, WP, memory, plugin list |
| API Health | `api-health` | Pings registered external APIs (incl. Chromedata with auth) |
| Cron Manager | `cron-manager` | View, run, reschedule, and delete WP cron events |
| Cache Manager | `cache-manager` | Per-post-type cache status, clear, regenerate |
| Database Health | `database-health` | DB size, cleanup tools (revisions, spam, trash, auto-drafts, orphaned rows) |
| VIN Checker | `developer-tools` | Decode a 17-char VIN via the Chromedata API; background job + polling |
| Import Monitor | `import-monitor` | WP All Import run history, status, per-import active toggle |
| Log Viewer | `log-viewer` | Browse SOC file-based logs by type |
| Maintenance | `maintenance` | Site maintenance mode toggle |
| Performance | `performance` | Runtime performance metrics |
| Security Snapshot | `security-snapshot` | Basic security audit |

### AJAX

All AJAX requests go through a single dispatcher in `SOC_Ajax`. Every action requires:
- `nonce` — value of `socData.nonce` (action: `soc_nonce`)
- `manage_options` capability

Registered actions:

| Action | Handler | Purpose |
|---|---|---|
| `soc_load_panel` | `handle_load_panel` | Re-render a panel and return HTML |
| `soc_refresh_module` | `handle_refresh_module` | Return fresh collected data |
| `soc_db_cleanup` | `handle_db_cleanup` | Run a DB cleanup type |
| `soc_clear_cache` | `handle_clear_cache` | Flush all caches |
| `soc_run_cron` | `handle_run_cron` | Manually fire a cron hook |
| `soc_delete_cron` | `handle_delete_cron` | Unschedule a cron event |
| `soc_reschedule_cron` | `handle_reschedule_cron` | Change cron recurrence |
| `soc_clear_transients` | `handle_clear_transients` | Delete all transients |
| `soc_clear_object_cache` | `handle_clear_object_cache` | Flush object cache |
| `soc_optimize_tables` | `handle_optimize_tables` | OPTIMIZE all DB tables |
| `soc_clear_pt_cache` | `handle_clear_pt_cache` | Clear a post-type cache |
| `soc_regen_pt_cache` | `handle_regen_pt_cache` | Trigger cache regeneration |
| `soc_flush_expired` | `handle_flush_expired` | Delete expired transients |
| `soc_view_pt_keys` | `handle_view_pt_keys` | List cache keys for a post type |
| `soc_pt_cache_status` | `handle_pt_cache_status` | Poll cache build status |
| `soc_test_api` | `handle_test_api` | Ping a URL |
| `soc_dismiss_notice` | `handle_dismiss_notice` | Dismiss an admin notice |
| `soc_vin_lookup` | `handle_vin_lookup` | VIN decode via Chromedata |
| `soc_vin_clear_history` | `handle_vin_clear_history` | Clear per-user VIN history |
| `soc_vin_run_background` | `handle_vin_run_background` | Dispatch background API job |
| `soc_vin_poll` | `handle_vin_poll` | Poll features_items population |
| `soc_im_toggle` | `handle_im_toggle` | Toggle import active flag |

### Support classes

| Class | Purpose |
|---|---|
| `SOC_Cache` | Transient wrapper — `get($module, $key)`, `set(...)`, `forget($module, $key)`. Always call `forget()` after mutations. |
| `SOC_Logger` | File-based logger to `wp-content/soc-logs/`. Types: `api`, `cron`, `cache`, `security`, `performance`, `general`. |
| `SOC_Response` | `::success(array $data)` / `::error(string $msg, int $status)` — terminates with `wp_send_json_*`. |

### Chromedata client

`App\Components\Base\Chromedata_Client` is the shared API client for all Chromedata (Atmosphere) calls. It reads credentials from ACF options (`url_chromedata`, `appid_chromedata`, `secret_chromedata`) and handles auth header generation (nonce + timestamp + SHA1 digest).

```php
// Authenticated PUT request
$result = Chromedata_Client::request($url, ['VIN' => '...', 'onlyDecodeUsing' => 'V,E,C,S']);

// Health check (used by API Health panel)
$health = Chromedata_Client::health_check(5); // returns ['ok', 'status', 'response_time_ms', ...]
```

### Frontend assets

SOC assets are enqueued only on Operation Center pages (`strpos($hook, 'soc')`).

| File | Purpose |
|---|---|
| `assets/src/styles/soc.scss` → `assets/dist/soc.css` | All SOC panel styles |
| `assets/src/js/soc/soc.js` → `assets/dist/js/soc.js` | Panel JS (refresh, AJAX actions, cron, DB cleanup, cache) |

The JS global `socData` is localised by `SOC_Assets`:
```js
socData.ajaxUrl  // admin-ajax.php URL
socData.nonce    // wp_create_nonce('soc_nonce')
```

---

## ACF Flexible Content & Gutenberg Blocks

The theme runs a **dual rendering architecture**: all sections work as both classic Flexible Content layouts and as standalone Gutenberg blocks, with zero HTML duplication.

### Rendering layers

```
template-parts/
├── acf/            ← Flexible Content wrappers
│                     collect sub_fields → pass $args to acf-shared
├── acf-blocks/     ← Gutenberg block wrappers
│                     collect get_field() → pass $args to acf-shared
└── acf-shared/     ← Single source of truth for all section HTML
                      receives $args, renders markup, no ACF calls
```

**Rule:** Neither `acf/` nor `acf-blocks/` templates contain HTML. They only gather data and delegate to `acf-shared/`.

### Available sections / blocks

| Section | Flexible layout name | Block slug | Shared partial |
|---|---|---|---|
| Info Accordion | `info-accordion` | `acf/info-accordion` | `acf-shared/info-accordion.php` |
| Block Logo | `block-logo` | `acf/block-logo` | `acf-shared/block-logo.php` |
| Get Offer | `get-offer` | `acf/get-offer` | `acf-shared/get-offer.php` |
| Buy | `buy` | `acf/buy` | `acf-shared/buy.php` |
| Content Block | `content-block` | `acf/content-block` | `acf-shared/content-block.php` |
| Content Header | `content_header` | `acf/content-header` | `acf-shared/content-header.php` |
| Single Video | `single_video` | `acf/single-video` | `acf-shared/single-video.php` |
| Video Section | `video_section` | `acf/video-section` | `acf-shared/intro-section.php` |
| Buttons | `buttons` | `acf/buttons` | `acf-shared/buttons.php` |
| Info Images | `info_images` | `acf/info-images` | `acf-shared/info-images.php` |
| Intro Blocks | `blocks` | `acf/intro-blocks` | `acf-shared/intro-blocks.php` |
| Offers | `offers` | `acf/offers` | `acf-shared/offers.php` |
| Images | `images` | `acf/images` | `acf-shared/images.php` |
| HTML Block | `html` | `acf/html-block` | `acf-shared/html-block.php` |
| Form | `form` | `acf/form` | `acf-shared/form.php` |
| Logo Section | `logo_section` | `acf/logo-section` | `acf-shared/logo-section.php` |
| Intro Slider | `intro_slider` | `acf/intro-slider` | `acf-shared/intro-slider.php` |
| Text & Video Slider | `text_and_video_slider` | `acf/text-video-slider` | `acf-shared/text-video-slider.php` |
| Tabs Slider | `tabs_slider` | `acf/tabs-slider` | `acf-shared/tabs-slider.php` |
| Content & Video | `content_and_video` | `acf/content-and-video` | `acf-shared/content-and-video.php` |
| Full Width Image | `full_width_image_section` | `acf/full-width-image` | `acf-shared/full-width-image.php` |
| Full Width Slider | `full-width-slider` | `acf/full-width-slider` | `acf-shared/full-width-slider.php` |
| Map Section | `map_section` | `acf/map-section` | `acf-shared/map-section.php` |
| Sub Footer | `sub_footer_section` | `acf/sub-footer` | `acf-shared/sub-footer.php` |
| Gallery | `gallery` | `acf/gallery` | `acf-shared/gallery.php` |
| Contact Information | `contact_information` | `acf/contact-information` | `acf-shared/contact-information.php` |
| Contact Widget | `contact_widget` | `acf/contact-widget` | `acf-shared/contact-widget.php` |
| Offer Cards | `offer-cards` | `acf/offer-cards` | `acf-shared/offer-cards.php` |

### ACF JSON sync

All field groups are stored in `acf-json/`. ACF reads them automatically. If the database is out of sync, go to **Custom Fields → Tools → Sync**.

- `group_601ab60960670.json` — master Flexible Content field group
- `group_block_*.json` — one block field group per section (location rule: `block == acf/{slug}`)

### Block registration

Blocks are registered automatically. `Register_Gutenberg_Blocks` scans `template-parts/acf-blocks/` and calls `acf_register_block()` for every `.php` file. Block metadata (title, description, icon, category, keywords) is read from the file's docblock header comment.

### Block editor previews

Place a `1200×600` JPEG named to match the block template file in `assets/images/block-previews/`. If the image is present it is shown in the editor inserter; otherwise a styled placeholder is displayed. See `assets/images/block-previews/README.md` for the full naming table.

### Adding a new section

1. Create `template-parts/acf-shared/{slug}.php` — markup only, reads from `$args`
2. Create `template-parts/acf/{slug}.php` — collects `get_sub_field()` calls, passes `$args`, calls `get_template_part('template-parts/acf-shared/{slug}', null, $args)`
3. Create `template-parts/acf-blocks/{slug}.php` — collects `get_field()` calls, passes `$args`, calls the same shared partial; add docblock header with Title/Description/Keywords/Category/Icon
4. Add the layout to the Flexible Content field group via ACF admin and sync JSON
5. Create `acf-json/group_block_{slug}.json` with the same fields and location rule `acf/{slug}`
6. Add a block preview image to `assets/images/block-previews/{slug}.jpg`

---

## Autoloading & DIC

Composer classmap autoloads everything under `inc/`. The `App\` namespace maps to `inc/`.

```php
// Resolve a class with its dependencies
$instance = App\injector()->make( \App\Components\Base\Scripts::class );

// Execute a method with injected dependencies
App\injector()->execute( '\App\Components\Base\Scripts::enqueue' );
```

---

## Coding Standards

PHP follows [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards). Some rules are relaxed for PSR-4 compatibility (file naming, short array/ternary syntax).

```bash
composer cs            # check all PHP files
```

PHPCS config: `phpcs.xml.dist`

JS follows the **Airbnb ESLint** ruleset. SCSS follows **BEM** via stylelint-selector-bem-pattern.

---

## Frontend Build

Source files live in `assets/src/`. Compiled output goes to `assets/dist/` (do not edit directly).

| Tool | Purpose |
|---|---|
| Webpack 5 | Bundler |
| Babel | ES module transpilation |
| PostCSS | CSS transforms |
| Bootstrap 4 | UI framework |
| ESLint (Airbnb) | JS linting |
| Stylelint (BEM) | CSS linting |
| Prettier | Code formatting |

---

## Post Types

| Slug | Description |
|---|---|
| `listings` | New vehicle listings |
| `used-listings` | Used vehicle listings |
| `offers` | General dealer offers |
| `finance-offers` | Finance offer type |
| `lease-offers` | Lease offer type |
| `service-offers` | Service offer type |
| `conditional-offers` | Conditional offer type |
| `research` | Editorial/research articles |

Primary SRP template: `archive-listings.php`

Page templates in `pages/`: `template-srp.php`, `template-saved.php`, `template-flexible.php`, `template-fullwidth.php`

---

## AI Chat System

The theme includes a hybrid AI chat system powered by OpenAI embeddings.

| Class | File | Purpose |
|---|---|---|
| `AI` | `inc/Components/Base/class-ai.php` | REST endpoint, intent routing, cosine ranking, rate limiting |
| `AI_Crawler` | `inc/Components/Base/class-ai-crawler.php` | Sitemap/link crawl, content extraction, embedding queue |

**Knowledge hierarchy:**
1. FAQ embeddings (cosine ≥ 0.75)
2. Crawled website pages (min score 0.10, up to 3 pages × 2000 chars)
3. General automotive guidance
4. Contact fallback

**Cron schedules:**

| Schedule | Hook | Interval |
|---|---|---|
| Full crawl | `ai_crawler_full` | daily |
| Incremental crawl | `ai_crawler_incremental` | every 2 hours |
| Embed queue | `ai_crawler_embed_queue` | every 15 minutes |
