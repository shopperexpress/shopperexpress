/**
 * What every tool in this folder shares to reach the local WordPress: the site root above the
 * theme, the DDEV wrappers, and the refusals that keep "ddev is not installed" from being
 * misreported as a stopped or broken site.
 *
 * DDEV is a global install, not a dependency of this project — see the README.
 */

import { execFileSync } from 'child_process';
import { existsSync, readFileSync } from 'fs';
import { dirname, join } from 'path';

export const THEME = process.cwd();

export const fail = (message) => {
  console.error(`\n${message}\n`);
  process.exit(1);
};

/**
 * The WordPress site the theme lives in.
 *
 * npm runs from the theme, which sits at wp-content/themes/<slug>, but DDEV is configured for
 * the site around it. Walking up to the DDEV config finds that without assuming a depth. The
 * walk targets the config file itself: `ddev delete` can leave the bare .ddev directory
 * behind, empty.
 */
export const findSiteRoot = () => {
  let dir = THEME;

  while (!existsSync(join(dir, '.ddev', 'config.yaml'))) {
    const parent = dirname(dir);

    if (parent === dir) return null;

    dir = parent;
  }

  return dir;
};

export const requireSiteRoot = () => findSiteRoot() ?? fail(`No .ddev/config.yaml above ${THEME}. Run this from inside a generated theme.`);

// ENOENT is the binary missing from PATH; anything else is DDEV itself misbehaving. Checked
// up front: every later wrapper reports a non-zero exit as the command failing, and a
// missing binary is not a failing command.
export const requireDdev = () => {
  try {
    execFileSync('ddev', ['--version'], { stdio: 'ignore' });
  } catch (err) {
    if (err?.code === 'ENOENT') {
      fail('DDEV is not installed, or not on PATH.\nInstall it first: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/');
    }

    fail('"ddev --version" failed — the DDEV install itself looks broken.');
  }
};

/**
 * The DDEV wrappers, bound to the site root the commands must run from.
 *
 * `ddev` shows the command's own output and treats a non-zero exit as fatal — a failing ddev
 * command has already printed its diagnosis, and a Node stack trace on top would bury it.
 * `ddevQuery` captures the output as the answer and reads a non-zero exit as "found nothing";
 * stderr is swallowed with it, since ddev reports every such answer as a failure in red.
 *
 * @param {string} root Site root.
 */
export const ddevRunner = (root) => ({
  ddev: (...args) => {
    try {
      return execFileSync('ddev', args, { cwd: root, stdio: 'inherit' });
    } catch {
      fail(`"ddev ${args.join(' ')}" failed. See the output above.`);
    }
  },
  ddevQuery: (...args) => {
    try {
      return execFileSync('ddev', args, { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
    } catch {
      return null;
    }
  },
});

/**
 * The DDEV project name — the slug the site answers on.
 *
 * The generator writes it on one line; a regex avoids a YAML dependency for a single field.
 *
 * @param {string} root Site root.
 */
export const readProjectName = (root) => {
  const config = join(root, '.ddev', 'config.yaml');
  const match = readFileSync(config, 'utf8').match(/^name:\s*(.+?)\s*$/m);

  if (!match) {
    fail(`${config} does not declare a project name.`);
  }

  const name = match[1].replace(/^['"]|['"]$/g, '');

  // The name reaches a hostname, a wp-cli argument and — in wp-setup.mjs — the inside of a
  // single-quoted PHP regex literal. DDEV's own grammar for it is this narrow.
  if (!/^[a-z0-9][a-z0-9-]*$/.test(name)) {
    fail(`${config} declares the project name "${name}", which is not a DDEV project name (lowercase letters, digits and hyphens).`);
  }

  return name;
};

/**
 * The site addresses a freshly imported database still carries.
 *
 * `wp option get` runs the value through WordPress's `option_siteurl` / `option_home` filters,
 * which return the WP_HOME and WP_SITEURL constants whenever those are defined — and
 * wp-setup.mjs defines both. `wp option list` reads the options table directly, so it is the
 * only one of the two that answers what the dump actually stored.
 *
 * @param {(...args: string[]) => string | null} ddevQuery
 * @returns {string[]} Distinct origins, empty when the options are unreadable.
 */
export const storedSiteOrigins = (ddevQuery) => {
  const origins = new Set();

  for (const option of ['siteurl', 'home']) {
    const raw = ddevQuery('wp', 'option', 'list', `--search=${option}`, '--format=json');

    if (!raw) continue;

    let rows;
    try {
      rows = JSON.parse(raw);
    } catch {
      continue;
    }

    // `--search` is a LIKE pattern, so `home` also matches `home_url_cache` and anything else
    // a plugin named around it.
    const value = (Array.isArray(rows) ? rows : []).find((row) => row?.option_name === option)?.option_value;

    if (!value) continue;

    try {
      origins.add(new URL(value).origin);
    } catch {
      /* not a URL: left for the developer to see */
    }
  }

  return [...origins];
};

/**
 * Points an imported database at this site.
 *
 * WP_HOME and WP_SITEURL are derived from the request (see wp-setup.mjs), which covers the two
 * options — but post content, serialized option payloads and every stored absolute URL still
 * carry the address the dump was made on, and arrive pointing at another site. GUIDs are left
 * alone: they are identifiers, not links, and feed readers treat a changed GUID as a new post.
 *
 * @param {{ ddev: Function, ddevQuery: Function }} runner
 * @param {string} target The address this site answers on.
 */
export const alignSiteUrls = ({ ddev, ddevQuery }, target) => {
  const origins = storedSiteOrigins(ddevQuery).filter((origin) => origin !== target);

  if (!origins.length) return;

  for (const origin of origins) {
    console.log(`\n🔁 Rewriting ${origin} → ${target} throughout the database...`);
    ddev('wp', 'search-replace', origin, target, '--all-tables', '--precise', '--skip-columns=guid');
  }

  ddevQuery('wp', 'cache', 'flush');
};