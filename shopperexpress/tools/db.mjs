/**
 * Database lifecycle for the local WordPress: export, import, snapshot, restore.
 *
 * Wraps the DDEV commands so the dump location and the URL rewrite an imported database
 * needs are conventions of the project rather than something every developer keeps in their
 * head. DDEV is a global install, not a dependency of this project — see the README.
 *
 * Usage: node tools/db.mjs <command> [file]
 *
 *   export     write the database to db/dump.sql.gz at the site root
 *   import     load a dump (default db/dump.sql.gz), then align its URLs with this site
 *   snapshot   a point-in-time copy of the database, kept by DDEV
 *   restore    return to the latest snapshot
 *
 * A dump exported as db/seed.sql.gz and committed becomes the site's seed: `npm run wp:setup`
 * imports it on a fresh machine instead of fetching the Theme Unit Test data.
 */

import { existsSync, mkdirSync } from 'fs';
import { join, resolve } from 'path';
import { THEME, alignSiteUrls, ddevRunner, fail, readProjectName, requireDdev, requireSiteRoot } from './ddev.mjs';

requireDdev();

const ROOT = requireSiteRoot();
const runner = ddevRunner(ROOT);
const { ddev } = runner;

const DUMP = 'db/dump.sql.gz';

const [, , command, argument] = process.argv;

switch (command) {
  case 'export': {
    mkdirSync(join(ROOT, 'db'), { recursive: true });
    ddev('export-db', `--file=${join(ROOT, DUMP)}`);
    console.log(`\n📤 ${DUMP} written at the site root.\n   Commit it as db/seed.sql.gz to make it the seed wp:setup imports on a fresh machine.\n`);
    break;
  }

  case 'import': {
    // A path argument is typed from the theme, where npm runs — but the dumps this project
    // writes live at the site root, so a path that misses from the theme is tried from
    // there before failing. `db/seed.sql.gz` therefore works typed from either place.
    const typed = argument ? [resolve(THEME, argument), resolve(ROOT, argument)].find(existsSync) : null;
    const dump = argument ? (typed ?? resolve(THEME, argument)) : join(ROOT, DUMP);

    if (!existsSync(dump)) {
      fail(`No dump at ${dump} (also tried from the site root).\nExport one first with "npm run db:export", or pass a path: npm run db:import -- path/to/dump.sql.gz`);
    }

    ddev('import-db', `--file=${dump}`);
    alignSiteUrls(runner, `https://${readProjectName(ROOT)}.ddev.site`);
    console.log('\n📥 Imported.\n');
    break;
  }

  case 'snapshot': {
    ddev('snapshot');
    console.log('\n📸 Snapshot taken. "npm run db:restore" returns to the latest one.\n');
    break;
  }

  case 'restore': {
    ddev('snapshot', 'restore', '--latest');
    break;
  }

  default:
    fail('Usage: node tools/db.mjs <export|import|snapshot|restore> [file]');
}
