# ShopperExpress

A WordPress theme for automotive dealer sites, with a webpack build and a local WordPress to
develop it against. It serves inventory (new/used vehicles), offers (finance, lease, service,
conditional) and content pages, pulling vehicle data either from WordPress content or live from
the dealer's [Intice](https://www.intice.com/) feed — see
[Theme architecture](#theme-architecture) below.

**This directory is the theme.** It sits at `wp-content/themes/<theme>` inside a WordPress site,
which is where WordPress reads it from and what gets deployed. The build compiles `src/` into
`assets/`; everything else here is the theme itself.

#### Requirements

- [Node JS](https://nodejs.org/) — version 24.15 or newer (see `.nvmrc`)
- [DDEV](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/) — runs the local
  WordPress. Installed once on your machine, not part of this project:
  ```
  brew install ddev mkcert nss    # or see the DDEV docs for your platform
  mkcert -install                 # once — the local CA browsers trust the site through
  ```
  Without [mkcert](https://github.com/FiloSottile/mkcert) on the machine, DDEV silently serves a
  placeholder certificate (`TRAEFIK DEFAULT CERT`) that every browser rejects — if
  `https://<theme>.ddev.site` warns, that is what happened: install mkcert and `ddev restart`.
  The `nss` package is what lets `mkcert -install` reach Firefox's own certificate store.

  On Windows, DDEV runs inside WSL2 with Docker — follow the
  [DDEV Windows guide](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#windows);
  mkcert comes with it.

## Getting started

Run everything from this directory — the theme.

1. Install project dependencies (**_skip if you already have modules installed_**):

```
npm i
```

2. Bring up the local WordPress:

```
npm run wp:setup
```

This builds the theme, starts the containers, installs WordPress around it, activates it, and
fills the site with sample content. It takes a few minutes the first time and
is safe to re-run — every step is skipped once it is done.

When it finishes you have a site at `https://<theme>.ddev.site`, with an admin login of
`admin` / `admin`.

3. In a second terminal, start the dev server:

```
npm run dev
```

Editing SCSS, JS or a PHP template rebuilds and reloads the page. The dev server sits in front of
the WordPress site, so PHP renders your pages while the compiled CSS and JS come from webpack.

The port is picked automatically, starting at 8080, so several projects can run at the same
time. Read the actual address from the banner printed at startup:

```
[webpack-dev-server] Loopback: https://127.0.0.1:8080/
```

The server speaks HTTPS with a certificate signed by the local CA that DDEV set up, so your
browser already trusts it; on a machine without that CA — or without `openssl` to mint the
certificate with — it falls back to plain HTTP and says so. Pages you view through it keep
every style, script and link on the dev server's own address, so live reload keeps working as
you click around the site. Firefox keeps its own certificate store — if it warns about the
certificate on first visit, accept it once; everything is served from that one address.

The server listens on the loopback address only — to view the site from a phone or another
machine, see [Testing on a real device](#testing-on-a-real-device) below.

Use the dev server address for the front end and `https://<theme>.ddev.site/wp-admin` for the
editor: the dev server injects its live-reload client into every page it serves, and a reload
fired by a file save would discard unsaved edits in the block editor.

### Testing on a real device

Share the WordPress site directly, bypassing the dev server:

```
npm run build
ddev share --provider=cloudflared
```

That prints a public `trycloudflare.com` URL which works from anywhere. It needs
[cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)
installed (`brew install cloudflared`) but no account. `ddev share` on its own uses ngrok, which
does need one.

The site follows whatever address it is reached at, so the shared URL renders correctly — that is
what `WP_HOME` and `WP_SITEURL` are set to in the site's `wp-config.php`. Build first: the dev server is
not in front of a tunnel, so only what is in `assets/` is served.

### The sample content

The site is seeded with the [WordPress Theme Unit Test data](https://github.com/WPTT/theme-test-data):
around 59 posts, 23 pages and 34 comments covering the output a theme has to style but nobody
writes by hand — threaded comments, image captions, very long titles, deeply nested lists, every
alignment, pagination, sticky and password-protected posts. Fetching it needs network access —
once per database, since the download is not kept.

When the repository carries a seed database at `db/seed.sql.gz` (see
[Database](#database) below), setup imports that instead: the project's own content, no network
needed.

Two of the sample menus are placed in the theme's locations, so the header and footer navigation
render something. Assignments you make yourself are never overwritten.

Bring the site up empty instead with `npm run wp:setup -- --skip-content`.

## Database

The database is where WordPress keeps everything you click together — posts, pages, menus,
widgets, options. It lives in the DDEV container, not in the repository, so sharing it is a
deliberate act:

| Command | What it does |
|---|---|
| `npm run db:export` | write the database to `db/dump.sql.gz` at the site root |
| `npm run db:import` | load `db/dump.sql.gz` — or any dump: `npm run db:import -- path/to.sql.gz` |
| `npm run db:snapshot` | point-in-time copy, kept by DDEV — take one before risky changes |
| `npm run db:restore` | return to the latest snapshot |

**Sharing content with a teammate**: `npm run db:export`, commit the dump as `db/seed.sql.gz`,
push. Their next `npm run wp:setup` imports it — including on a brand-new machine. Refresh it
whenever the shared state should move forward; they re-import explicitly with
`npm run db:import -- db/seed.sql.gz`.

**Importing a dump from another site** (staging, production): `npm run db:import` rewrites every
stored URL to this site's address afterwards — `wp search-replace` under the hood. Without that
rewrite the imported content keeps linking to the site it came from; only `WP_HOME`/`WP_SITEURL`
follow the request automatically, stored URLs do not.

**What the database does not carry**: media files live in `wp-content/uploads/` and plugins in
`wp-content/plugins/`, and both are gitignored — a shared database references media the receiver
does not have. Copy uploads separately (`rsync`, or a zip of the folder) and install plugins on
each machine.

**Starting over**: `ddev delete` removes the container and its database (exports a backup by
default); the next `npm run wp:setup` rebuilds the site from scratch. For anything less than
scratch, prefer a snapshot.

## Where things are

```
style.css                  the theme header WordPress identifies this theme by
functions.php  index.php  header.php  footer.php  single.php  page.php  …
inc/                       one file per concern: setup, menus, widgets, enqueue,
                           blocks, template-tags
template-parts/            the entry markup those templates render
theme.json  screenshot.png  languages/
blocks/<name>/             one folder per block; its assets compile into build/ beside it
blocks/blocks-manifest.php every block's metadata in one file, written by the build
src/styles/                SCSS; style.scss is the entry point
src/js/                    app.js is the entry point
src/images/  src/fonts/
assets/                    build output — css/, js/, images/, fonts/
```

`assets/`, each block's `build/` folder and `blocks/blocks-manifest.php` are build output and
gitignored; the site root carries a `.gitlab-ci.yml` that rebuilds them on every push.

The PHP is the theme, edited where it sits; nothing compiles it. Only `src/` and the blocks'
`.scss`/`.js` are built. Nothing generates HTML — WordPress renders the pages.

`style.css` carries the header and nothing else. The compiled stylesheet is
`assets/css/style.css`, enqueued from `inc/enqueue.php`.

The WordPress install lives above this directory and is gitignored; only the theme is tracked.

## Theme architecture

`functions.php` only bootstraps: it requires Composer's autoloader, a handful of `inc/*.php`
files loaded by name (`helpers`, `enqueue`, `theme-functions`, …), then hands off to
`App\Components\Theme::register_components()`. That class lists every component in
`inc/Components/`, builds each one through an [Auryn](https://github.com/rdlowrey/auryn) DI
container, and calls its `register()` method — which is where that component wires its own
WordPress hooks. To add behaviour, add a class under `inc/Components/...` implementing
`Theme_Component` and list it there; nothing else discovers it.

PSR-4 autoloading maps the `App\` namespace to `inc/`: `App\Components\Base\Scripts` resolves to
`inc/Components/Base/class-scripts.php`.

```
inc/Components/
  Base/         site-wide features: CPT registration, ACF, menus, scripts/enqueue, AJAX, REST,
                sidebars, shortcodes, options page, login, vehicle view tracking, JSON-LD,
                ADF lead-delivery cron, Google Business Reviews (+ its cron), OpenAI ad copy,
                AI chat / AI crawler / AI VDP summaries, VIN admin, required-plugins notice
  Api/          Intice_REST + Intice_VDP — only registered when API mode (below) is on
  Gutenberg/    block category + editor color palette
  SOC/          the theme's own admin dashboard — see below
```

### Content modes: CPT vs. API

Inventory and offers are modeled as custom post types (`inc/Components/Base/class-cpt.php`):
`listings`, `used-listings`, `offers`, `finance-offers`, `lease-offers`, `conditional-offers`,
`service-offers`, `research` — each with its own `archive-<type>.php` and
`template-parts/single/single-<type>.php`.

A dealer can instead flip **API mode** (`App\is_api_mode()`, the WP option behind SOC → API &
Integrations), which pulls live vehicle data from the Intice Nexus feed through
`inc/Components/Api/` instead of reading it from post content. Templates branch on this: e.g.
`archive-listings.php` and `template-parts/api/gallery.php` render from a `$vehicle` array fetched
over the API, while the CPT-mode equivalents (`template-parts/single/single-listings.php`, etc.)
read ACF fields off the post. Both paths share the same front-end markup/behaviour as far as
possible.

Vehicle availability status (`vehicle-status` — "In Stock", "In Transit", "On Order", "Sold", free
text from the dealer feed) is available under the **same field key in both modes**: a real ACF
`text` field on `listings`/`used-listings` in standard mode, and Nexus's `vehicle_status` column
(exposed as top-level `status`) in API mode. The `[stock]` shortcode's `status`/`field` attributes
filter on this transparently in either mode.

Per-vehicle 360°/spin imagery is itself pluggable: the dealer picks a provider in theme options
(Impel, AutoExact, LESA, FlickFusion, autoport, evo, dealerimage, or Spinmaker) and
`template-parts/components/detail-slider.php` / `template-parts/api/gallery.php` render whichever
embed or launcher link that provider needs.

### Page building: ACF blocks and the three-layer template pattern

Flexible content pages are assembled from the ~40 ACF blocks in `blocks/` (see
[Adding a block](#adding-a-block) below) — galleries, comparison tables, step/feature sections,
sliders, forms, and similar landing-page building blocks.

ACF Flexible Content layouts and Gutenberg (ACF) blocks render the same markup through three
layers, so a layout and a block sharing a design never duplicate HTML:

1. `template-parts/acf/acf-{layout}.php` — Flexible Content wrapper; calls `get_sub_field()`
2. `blocks/{slug}/block.php` — Gutenberg block render; ACF gives it `get_field()` directly, scoped
   to that block instance
3. `template-parts/acf-shared/{slug}.php` — single source of truth for the HTML; reads only its
   `$args`, no ACF calls of its own

### SOC: the admin dashboard

`inc/Components/SOC/` is a self-contained admin area — **WP Admin → Operation Center** — for
running and diagnosing the site, independent of core WordPress screens. Modules implement
`SOC_Module`, render through `inc/Components/SOC/views/`, and are grouped under 4 top-level menu
entries rendered as tabs on one admin page (`SOC::GROUPS` in `class-soc.php`):

| Group | Panels |
|---|---|
| System & Cache | System Status, Cron Manager, Cache Manager, Database Health |
| API & Integrations | API Settings, Developer Tools (VIN checker via Chromedata), API Health |
| Leads & Vehicles | Lead Delivery, VDR Requests, Import Monitor, AI VDP Log |
| SEO & Content | Google Reviews, JSON-LD Schema, Shortcodes (reference) |

### ADF lead delivery

Leads submitted via WP Forms or direct AJAX are delivered by **email** (legacy) or **API** (Intice
IO endpoint), configured in Operation Center → Lead Delivery:

1. A lead form is submitted (WP Forms webhook, or AJAX action `submit_adf_lead` / `adf`).
2. `wps_render_adf_template()` (template resolved by `wps_resolve_adf_template()`, both in
   `inc/theme-functions.php`) builds the ADFXML string.
3. `wps_dispatch_adf()` checks the `adf_delivery_method` option — **email** sends via
   `wps_send_adf_email()` → `wp_mail()`; **api** sends via `ADF_Api_Client::send()` → `POST` to the
   Intice IO endpoint (`Authorization: Bearer {secret_key}`, key stored AES-256-CBC encrypted).
4. Every attempt is logged to `{prefix}_adf_lead_log`, with `ADF_Cron` retrying `failed` rows every
   15 minutes (capped by `adf_max_retries`) and an admin notification email on API failure.

WP Forms reaches ADF through per-form **Webhook** notifications posting to
`admin-ajax.php?action=adf`, handled by `Ajax::adf_action()`; a comma-separated allow-list in SOC →
Lead Delivery restricts which forms may use the API leg (everything else still delivers by email).

### Other vehicle-page features

| Feature | Location | Notes |
|---|---|---|
| Vehicle JSON-LD schema | `class-json-ld.php` | Legacy (hardcoded) or Builder mode (field toggles + source mapping configured in SOC → JSON-LD Schema, stored in option `json_ld_field_config`); `additionalProperty` entries come from the `features_items` repeater |
| VDR (Vehicle Detail Report) badge | `additional_custom_badges` ACF repeater, `action = api` | Requests a Chromedata PDF; GA event on click, error modal, per-VIN transient suppression on failure, every call logged to `{prefix}_vdr_log` |
| AI VDP description | `class-ai-vdp.php` | Auto-populates a VDP description via OpenAI `gpt-4o-mini` when enabled in Theme Options; prompts customizable via ACF fields `ai_vdp_system_prompt` / `ai_vdp_user_prompt` |
| Vehicle view tracking | `class-vehicle-views.php` | All-time VDP view counts per VIN in a custom table; works in both content modes |
| ASC dataLayer | `class-asc-datalayer.php` | Builds the Automotive Standards Council `dataLayer` object for analytics, detects page type across vehicle CPTs |
| Custom shortcodes | `inc/theme-functions.php` (`init` hook) | Admin-defined `[sc_{name}]` shortcodes from a Theme Options repeater — see Operation Center → Shortcodes |
| Template/shortcode export | `class-export.php` | Admin export/import for ACF Flexible Content page templates and custom shortcodes |

### External integrations

| Service | Location | Notes |
|---|---|---|
| Intice Nexus | `inc/Components/Api/class-intice-api-client.php` | Vehicle inventory API (API mode) |
| Intice IO Leads | `inc/Components/Base/class-adf-api-client.php` | ADFXML lead delivery API |
| Chromedata / JD Power | `class-chromedata-client.php` | VIN decode + VDR PDF API |
| OpenAI | `class-ai.php` + `class-ai-crawler.php` + `class-ai-vdp.php` | Embeddings, crawl pipeline, AI VDP descriptions |
| OpenAI Ads | `class-openai-ads.php` + `class-openai-ads-client.php` | Ads measurement pixel + Conversions API (CAPI), key AES-256 encrypted |
| Google Business Profile / Places API | `class-google-business-reviews.php` | Reviews sourcing for the Google Reviews SOC panel |
| Twilio | `vendor/twilio/sdk` | SMS verification codes |

### ACF JSON

Field group definitions are stored in `acf-json/` and version-controlled. Always sync field
groups there rather than relying solely on database state — it is what lets a block's or option
page's fields travel with the repository instead of needing to be recreated per environment.

### Adding a block

Every block in this theme is an ACF block: `block.json` carries the metadata and an `"acf"` key
pointing at `block.php` for the render, with the field definitions themselves living under
`acf-json/`. There is no registration list to update — add a new folder under `blocks/`, give it a
`block.json`, and restart `npm run dev` to discover it. `blocks/buttons` is a straightforward
example to copy:

```
blocks/buttons/
  block.json     name, "acf" key (blockVersion, mode, renderTemplate), and the pointers below
  block.php      the front-end markup — reads fields via get_field()/get_sub_field()
  style.scss     styles, compiled to build/style.css
  view.js        front-end behaviour, compiled to build/view.js
```

ACF Pro has to be active for these blocks to render — the theme shows an admin notice if it is
not. ACF Pro is a paid plugin with no public download: get it from
[advancedcustomfields.com](https://www.advancedcustomfields.com/pro/) and install it under
**Plugins → Add New → Upload Plugin** on every machine, because `wp-content/plugins/` is
gitignored and never travels with the repository.

Set `"acf": { "blockVersion": 3, ... }` explicitly in every block's `block.json` — ACF's own
default for this depends on the ACF/WordPress version combination installed on a given site, and
pinning it avoids a block's editing experience (in-canvas fields vs. falling back to the Settings
sidebar) silently changing between environments.

A block compiles into a `build/` folder beside its sources rather than into `assets/`, because
`block.json` addresses its assets relative to itself. The folder is build output — gitignored,
and recreated by every build.

The build also collects every `block.json` into `blocks/blocks-manifest.php`, which is what
WordPress reads at runtime instead of opening each one on every request. It is regenerated by
every build, so nothing there is worth editing — and a block added without a build is registered
from its `block.json` as before, just more slowly.

Give a block no `version` in its `block.json`. WordPress would use it as the `?ver=` on the
block's stylesheet and view script, and a literal never changes — the theme versions them by
build time instead, so an edited block reaches browsers that already cached it.

Each block's styles and scripts are built separately, which is what lets WordPress load them only
on pages where that block appears. A block that needs no styling or no behaviour can omit those
files.

## Commands

| Command | What it does |
|---|---|
| `npm run wp:setup` | build, start DDEV, install WordPress, activate the theme, seed content |
| `npm run dev` | dev server in front of the local site |
| `npm run build` | production build into `assets` |
| `npm run theme:zip` | package the theme for upload as `<theme>.zip` |
| `npm run db:export` / `db:import` | share database content — see [Database](#database) |
| `npm run db:snapshot` / `db:restore` | point-in-time database undo |
| `npm run lint:css` / `lint:js` / `lint:php` | report problems without changing anything |
| `npm run lint:fix:css` / `lint:fix:js` / `lint:fix:php` | fix what can be fixed |
| `npm run format` | Prettier over `src` and the blocks' sources |
| `npm run clean` | delete the build output — `assets/`, the blocks' `build/` folders and their manifest |
| `npm run clean:cache` | delete webpack's cache |
| `npm run convert:webp` / `convert:avif` | convert `src/images` to one modern format |

The CSS and JS lint commands exist only if you chose those linters when generating. `lint:js`
runs ESLint's own recommended rules — undefined names, unused variables, unreachable code —
alongside the project's stylistic ones, so it reports mistakes as well as formatting.

`lint:php` runs two tools: **PHPCS** with the WordPress Coding Standards, which checks how the
code is written, and **PHPStan**, which checks whether it can work — undefined functions, wrong
argument types, conditions that are always false. Both run inside the DDEV container, so the site
has to be started (`ddev start`) but no PHP has to be installed locally. The first run installs
the tools and writes `composer.lock` — commit it, so every machine lints with the same versions.
Raise PHPStan's `level` in `phpstan.neon` as the theme grows.

Useful DDEV commands: `ddev stop`, `ddev wp <command>` for WP-CLI, `ddev describe` for URLs and
database credentials, `ddev snapshot` / `ddev snapshot restore` for database undo,
`ddev delete` to remove the project and its database.

WordPress core is downloaded above this directory and gitignored — nothing in it is your source.
The theme is already in place, so a rebuild is visible immediately.

## Handing the theme over

```
npm run theme:zip
```

Produces `<theme>.zip` beside the WordPress site, ready for **Appearance → Themes → Add New →
Upload Theme**. It is this directory minus what a server has no use for: `src/`, `tools/`,
`node_modules/`, `vendor/`, the build, lint and Composer configuration, the blocks' SCSS/JS
sources, the source maps — and this README, so the recipient gets the theme, not its workshop.

## Builds

Production builds minify images: `jpg`, `jpeg`, `png` and `gif` through
[sharp](https://sharp.pixelplumbing.com/), `svg` through [svgo](https://github.com/svg/svgo). Your
files in `src/images` are never modified — only the copies written to `assets`. `webp` and `avif`
are deliberately skipped, since they are already lossily encoded and re-encoding would only make
them larger. Development builds copy every image untouched, so what you see in `dev` is the
original.

Builds are cached to `node_modules/.cache/webpack`, so a repeat `dev` or `build` is much faster
than the first one. The cache is invalidated automatically when your sources or `config.json`
change. If a build ever looks stale anyway, clear it with `npm run clean:cache`.

### Converting images

Run **one** of:

```
npm run convert:webp
npm run convert:avif
```

Pick a single format — the two scripts are alternatives, not steps.

Only `.jpg`, `.jpeg` and `.png` are converted (`.svg` and `.gif` are left alone), and a file is
skipped when the converted result would be *larger* than the source. Each converted original is
**moved** to `src/images/_originals/`, never deleted, so you can review the output before
accepting it. That folder is excluded from the build, so nothing in it ships to `assets`.

- Happy with the result? Delete `src/images/_originals/`.
- Not happy? Put the originals back with `npm run convert:webp -- --restore`, then remove the
  generated files by hand.
- Re-encode files that were already converted with `--force`.
- Two sources that differ only by extension (`logo.jpg` and `logo.png`) both target `logo.webp`.
  The script refuses to convert either and asks you to rename one, rather than silently dropping
  an image.

Because the original leaves the build output, **update your markup yourself** — an
`<img src="images/photo.jpg">` in a PHP template or a block becomes
`<img src="images/photo.webp">`.

`src/images/_originals/` is already gitignored, so nothing in it is committed unless you remove
that rule.

## Configuration

`config.json` controls the build's folder structure without touching the webpack config.

`inc/enqueue.php` names `assets/css/style.css` and `assets/js/app.js` as literals, since nothing
at runtime can tell it where the build put them — so `dest`, `styles.dest` and `scripts.dest` are
what make those literals true. `devserver.proxy` is the address WordPress answers on, already
pointing at the DDEV site.
