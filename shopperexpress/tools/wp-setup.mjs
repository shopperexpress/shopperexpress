/**
 * Brings up the local WordPress this theme is developed against.
 *
 * Every step is skipped when it has already been done, so the script can be re-run after a
 * `ddev delete`, a wiped docroot, or a half-finished first attempt.
 *
 * DDEV itself is a global install, not a dependency of this project — see the README.
 */

import { existsSync, readFileSync, rmSync, writeFileSync } from 'fs';
import { join } from 'path';
import { THEME, alignSiteUrls, ddevRunner, fail, readProjectName, requireDdev, requireSiteRoot } from './ddev.mjs';

requireDdev();

const ROOT = requireSiteRoot();
const runner = ddevRunner(ROOT);
const { ddev, ddevQuery } = runner;

const ADMIN = {
  user: 'admin',
  password: 'admin',
  email: 'admin@example.com',
};

// The theme review project's sample content. Kept at its upstream URL: vendored, its 600KB
// sits in every generated project and in this generator's own package, to be used once.
const UNIT_TEST_DATA = {
  url: 'https://raw.githubusercontent.com/WPTT/theme-test-data/master/themeunittestdata.wordpress.xml',
  file: 'themeunittestdata.wordpress.xml',
};

// A committed dump takes precedence over the sample content: it is the project's own state,
// shared through the repository, and it makes a fresh machine's setup work offline.
const SEED_FILE = 'db/seed.sql.gz';

// Guards both content sources, stored in the database: re-running setup after either import
// must not import again — a seed re-import silently rolls the database back. A seed
// exported from an already-seeded site carries the marker inside it.
const CONTENT_MARKER = 'p2h_theme_content_seeded';

// `npm run wp:setup -- --skip-content` brings the site up empty.
const SKIP_CONTENT = process.argv.includes('--skip-content');

/**
 * Imports the committed seed database, when the project ships one.
 *
 * Returns whether a seed exists — an already-imported seed still counts, so the caller does
 * not fall through to the sample content on a machine that was simply set up twice.
 */
const importSeedDatabase = () => {
  if (!existsSync(join(ROOT, SEED_FILE))) return false;

  if (ddevQuery('wp', 'option', 'get', CONTENT_MARKER) !== null) return true;

  console.log('\n🌱 Importing the seed database...');
  ddev('import-db', `--file=${join(ROOT, SEED_FILE)}`);

  alignSiteUrls(runner, url);

  ddev('wp', 'option', 'update', CONTENT_MARKER, '1');

  return true;
};

/**
 * Fills the site with the WordPress Theme Unit Test data.
 *
 * A theme has to style output nobody writes by hand — threaded comments, image captions,
 * unusually long titles, deeply nested lists, every alignment, pagination. This is the content
 * the theme review process checks against, so it is the content worth developing against.
 *
 * Guarded by an option: existing posts are legitimate content, not evidence of an import,
 * and a re-run must not import every entry a second time.
 */
const importThemeUnitTestData = async () => {
  if (ddevQuery('wp', 'option', 'get', CONTENT_MARKER) !== null) return;

  const target = join(ROOT, UNIT_TEST_DATA.file);

  // The archive is 600KB of XML sitting in the docroot, which the webserver would happily
  // serve and `git add .` would pick up. A failing ddev command ends the process from inside
  // ddev(), which skips finally blocks — the exit hook is the path that still runs, so the
  // file is gone however the import ends. Exit hooks do not run on a signal, so Ctrl+C is
  // routed through process.exit to reach the same cleanup.
  process.on('exit', () => rmSync(target, { force: true }));
  process.once('SIGINT', () => process.exit(130));

  if (!existsSync(target)) {
    console.log('\n📥 Fetching the Theme Unit Test data...');

    const response = await fetch(UNIT_TEST_DATA.url);

    if (!response.ok) {
      // Not fatal. The site is already usable; only the sample content is missing.
      console.warn(`   Could not fetch it (${response.status} ${response.statusText}). Skipping — re-run to try again.`);
      return;
    }

    writeFileSync(target, Buffer.from(await response.arrayBuffer()));
  }

  console.log('\n📦 Importing it...');

  // Tolerated like the fetch above, and for the same reason: an offline first run already
  // has a usable site, and a fatal exit here would contradict the fetch having just said so.
  if (ddevQuery('wp', 'plugin', 'install', 'wordpress-importer', '--activate') === null) {
    console.warn('   Could not install the importer plugin. Skipping the sample content — re-run to try again.');
    return;
  }

  // DDEV's mutagen mode copies the project into the container asynchronously, so a file
  // written a moment ago on the host is not necessarily there yet. Flushing is a no-op in the
  // other performance modes, hence the tolerated failure.
  ddevQuery('mutagen', 'sync');

  // `ddev wp` runs from the project root, which is also where WordPress is installed.
  ddev('wp', 'import', UNIT_TEST_DATA.file, '--authors=create');
  ddev('wp', 'option', 'update', CONTENT_MARKER, '1');

  rmSync(target, { force: true });
};

/**
 * Puts one of the site's menus in each of the theme's locations.
 *
 * The imported data ships several menus but cannot know this theme's locations, so without this
 * the header and footer navigation renders nothing at all. `all-pages` is the deeply nested one,
 * and the reason to want it in the header is that it exercises submenus — where theme navigation
 * usually breaks.
 *
 * Runs outside the import guard, since a location can be empty for other reasons, but skips any
 * location that already has a menu: re-running setup must not undo an assignment made by hand.
 */
const assignMenus = () => {
  // `--format=json`: wp-cli's CSV renders a menu holding several locations as one quoted,
  // comma-joined field — two half-quoted locations to a naive split — while `locations`
  // arrives here as a real array.
  const raw = ddevQuery('wp', 'menu', 'list', '--fields=slug,locations', '--format=json');

  // A plugin's notice or deprecation lands on stdout ahead of the JSON, and menus are the
  // last cosmetic step — a parse failure here must not lose the banner naming the site.
  let rows;

  try {
    rows = raw ? JSON.parse(raw) : [];
  } catch {
    console.warn('   Could not read the menu list — assign menus under Appearance → Menus.');
    return;
  }

  // `empty-menu` exists in the data precisely to have no items, which is not what anyone wants
  // to look at in a header.
  const menus = rows.map(({ slug }) => slug).filter((slug) => slug && slug !== 'empty-menu');
  const taken = rows.flatMap(({ locations }) => locations ?? []).filter(Boolean);

  if (!menus.length) return;

  const pick = (...preferred) => preferred.find((slug) => menus.includes(slug)) ?? menus[0];

  const wanted = Object.entries({
    primary: pick('all-pages'),
    footer: pick('short', 'all-pages-flat'),
  }).filter(([location]) => !taken.includes(location));

  if (!wanted.length) return;

  console.log('\n🧭 Assigning menus...');

  wanted.forEach(([location, slug]) => {
    ddevQuery('wp', 'menu', 'location', 'assign', slug, location);
    console.log(`   ${location} → ${slug}`);
  });
};

const projectName = readProjectName(ROOT);
const configPath = join(THEME, 'config.json');

if (!existsSync(configPath)) {
  fail(`No config.json at ${THEME}. Run this from inside a generated theme.`);
}

const config = JSON.parse(readFileSync(configPath, 'utf8'));
const stylesheet = join(THEME, config.dest, config.styles.dest, `${config.styles.bundle}.css`);
const url = `https://${projectName}.ddev.site`;

if (!existsSync(stylesheet)) {
  fail(`No compiled stylesheet at ${stylesheet}.\nRun "npm run build" first.`);
}

console.log(`\n🐳 Starting DDEV project "${projectName}"...`);
ddev('start');

if (!existsSync(join(ROOT, 'wp-includes'))) {
  console.log('\n⬇️  Downloading WordPress...');
  ddev('wp', 'core', 'download');
}

// DDEV provisions a wp-config.php of its own for a docroot that already holds WordPress, which
// an empty one did not on the first start. Writing it here covers that ordering; the database
// is always reachable as db/db/db on the host named `db` inside a DDEV project.
if (!existsSync(join(ROOT, 'wp-config.php'))) {
  console.log('\n🔧 Writing wp-config.php...');
  ddev('wp', 'config', 'create', '--dbname=db', '--dbuser=db', '--dbpass=db', '--dbhost=db');
}

/*
    WordPress builds every asset URL, link and redirect from its own site address, not from the
    address it was reached on. Stored as a fixed value, that makes the site unusable from anywhere
    but the DDEV hostname: opening it through a `ddev share` tunnel returns markup whose
    stylesheet and scripts still point at <project>.ddev.site — which resolves to 127.0.0.1 on
    whatever device is asking.

    Deriving both from the request host instead makes the site answer correctly on every address
    it is reachable at. This belongs to the local install only: wp-config.php lives in the
    gitignored docroot and never ships with the theme.

    Only addresses the site is legitimately served on are adopted: the DDEV hostname, the
    loopback pair the dev-server override below rewrites to, and the tunnel domains
    `ddev share` publishes through. Any client can put anything in Host — and through a
    public tunnel, an unchecked value hands the sender every URL WordPress builds,
    password-reset links included. Everything else, wp-cli's request-less invocations
    included, falls back to the canonical DDEV address.
*/
// Anchored with \z rather than $: PCRE's $ also matches before a trailing newline, which
// would let `127.0.0.1\n` through and into every URL WordPress builds.
const ALLOWED_HOSTS = `(${projectName}\\.ddev\\.site|127\\.0\\.0\\.1|localhost|\\[::1\\]|[a-z0-9-]+\\.trycloudflare\\.com|[a-z0-9.-]+\\.ngrok(-free)?\\.(app|dev|io))(:\\d+)?`;
const REQUEST_HOST = `isset( $_SERVER['HTTP_HOST'] ) && preg_match( '/^${ALLOWED_HOSTS}\\z/i', $_SERVER['HTTP_HOST'] ) ? ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || 'https' === ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] : '${url}'`;

// These two writes carry the whole request-host scheme — loud on failure, unlike a query,
// because a site without them serves unstyled markup through every tunnel and proxy.
['WP_HOME', 'WP_SITEURL'].forEach((constant) => ddev('wp', 'config', 'set', constant, REQUEST_HOST, '--raw'));

/*
    The request host above is a lie for anything reached through the webpack dev server: DDEV's
    router routes by `Host` and rewrites every `X-Forwarded-*` header, so the proxy carries the
    browser's address in `X-Devserver-Host`/`-Proto`, which the router leaves alone. The pair is
    folded into the superglobals before WordPress loads, so every consumer — WP_HOME above,
    is_ssl(), the canonical redirect — agrees on the address the browser actually used; anything
    less and redirect_canonical() bounces a proxied request forever. A page viewed through the
    dev server then keeps its styles, scripts and links on the dev server, where live reload
    works — and a browser that trusts the dev server needs to trust nothing else.

    Only a loopback host with both headers present is adopted: the dev server binds loopback
    and always sends the pair, while any client on the network can send these headers too —
    reached through a `ddev share` tunnel, an unchecked override would hand the sender every
    URL WordPress builds, password-reset links included.
*/
const HOST_OVERRIDE = `
/* The webpack dev server forwards the browser's address in these headers — DDEV's router
   rewrites Host and every X-Forwarded-*. Adopted whole, so WP_HOME, is_ssl() and the
   canonical redirect agree on the address the browser actually used. Only a loopback host
   with both headers present counts: the pair is meaningful from the local dev server alone,
   and any other client can send these headers too. */
if ( isset( $_SERVER['HTTP_X_DEVSERVER_HOST'], $_SERVER['HTTP_X_DEVSERVER_PROTO'] )
\t&& preg_match( '/^(127\\.0\\.0\\.1|localhost|\\[::1\\])(:\\d+)?\\z/', $_SERVER['HTTP_X_DEVSERVER_HOST'] )
) {
\t$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_DEVSERVER_HOST'];
\tif ( 'https' === $_SERVER['HTTP_X_DEVSERVER_PROTO'] ) {
\t\t$_SERVER['HTTPS'] = 'on';
\t} else {
\t\tunset( $_SERVER['HTTPS'] );
\t}
}
`;

// The config writes above ran in the container; in mutagen mode the host copy can lag behind.
ddevQuery('mutagen', 'sync');

const wpConfigFile = join(ROOT, 'wp-config.php');

if (!existsSync(wpConfigFile)) {
  fail(`No wp-config.php at ${ROOT} after "wp config set" — the container and the host may be out of sync.`);
}

const wpConfig = readFileSync(wpConfigFile, 'utf8');

if (!wpConfig.includes('HTTP_X_DEVSERVER_HOST')) {
  // The override must run before WordPress loads, so it anchors on the opening tag; with no
  // anchor the rewrite is byte-identical, silent, forever — hence the refusal.
  if (!wpConfig.includes('<?php')) {
    fail(`${wpConfigFile} has no <?php opening tag to anchor the dev-server host override.`);
  }

  // DDEV regenerates any file carrying `#ddev-generated`, which would take the override and
  // the request-derived WP_HOME with it. The line is DDEV's own opt-out.
  const owned = wpConfig.replace(/^.*#ddev-generated.*\n/m, '');

  writeFileSync(wpConfigFile, owned.replace('<?php', `<?php\n${HOST_OVERRIDE}`));
  ddevQuery('mutagen', 'sync');
}

// `wp core is-installed` exits non-zero until the tables are there, which is the check itself.
if (ddevQuery('wp', 'core', 'is-installed') === null) {
  console.log('\n⚙️  Installing WordPress...');
  ddev(
    'wp',
    'core',
    'install',
    `--url=${url}`,
    `--title=${projectName}`,
    `--admin_user=${ADMIN.user}`,
    `--admin_password=${ADMIN.password}`,
    `--admin_email=${ADMIN.email}`,
    '--skip-email'
  );
}

// A seed import replaces the whole database, the active theme and the user table with it, so
// the content is settled before the theme is activated and before the credentials are printed.
let seeded = false;

if (!SKIP_CONTENT) {
  seeded = importSeedDatabase();

  if (!seeded) {
    await importThemeUnitTestData();
  }
}

console.log('\n🎨 Activating the theme...');
ddev('wp', 'theme', 'activate', projectName);

if (!SKIP_CONTENT) {
  assignMenus();
}

console.log(
  [
    '',
    '✅ Ready.',
    '',
    `   Site:  ${url}`,
    seeded ? `   Admin: ${url}/wp-admin  (the accounts the seed database carries)` : `   Admin: ${url}/wp-admin  (${ADMIN.user} / ${ADMIN.password})`,
    '',
    '   Run "npm run dev" in another terminal to rebuild and reload on every change.',
    '   Database: npm run db:export / db:import share content; db:snapshot / db:restore undo.',
    '',
  ].join('\n')
);
