/**
 * Packages the theme for upload.
 *
 * The theme directory is already named after the theme and already contains what WordPress
 * loads, so this only has to leave out what a server has no use for: the sources the build
 * compiles from, the tooling and configuration that compile them, and the maps. A production
 * build emits maps without the comment that points at them, which keeps them useful to whoever
 * holds them and silent to everyone else; shipped, they publish the sources.
 */

import { execFileSync } from 'child_process';
import { cpSync, existsSync, mkdtempSync, readFileSync, renameSync, rmSync, statSync } from 'fs';
import { tmpdir } from 'os';
import { basename, join, relative, sep } from 'path';
import { THEME, fail, requireSiteRoot } from './ddev.mjs';

const SLUG = basename(THEME);

// Everything here is a build input, a build tool or its configuration. `assets/` and the
// blocks' build/ folders are deliberately not among them — they are the compiled output the
// theme actually runs on.
const EXCLUDED = new Set([
  'src',
  'node_modules',
  'tools',
  'vendor',
  '.git',
  'config.json',
  'package.json',
  'package-lock.json',
  'yarn.lock',
  'composer.json',
  'composer.lock',
  'webpack.config.mjs',
  'babel.config.js',
  'postcss.config.js',
  'prettier.config.js',
  'eslint.config.js',
  'stylelint.config.js',
  'phpcs.xml',
  'phpstan.neon',
  '.nvmrc',
  '.prettierignore',
  '.editorconfig',
  '.gitignore',
  'README.md',
]);

// Only bsdtar — which Windows 10+ ships as tar.exe — picks the archive format from the file
// extension. GNU tar has no zip support at all: asked for a .zip it writes an ordinary tar
// archive under that name, exits 0, and WordPress rejects the upload as not a valid zip.
const isBsdtar = () => {
  try {
    return execFileSync('tar', ['--version'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).includes('bsdtar');
  } catch {
    return false;
  }
};

/**
 * Creates the archive.
 *
 * `zip` is not on a stock Windows install, so bsdtar is the fallback there. Both tools are
 * asked to write the same layout: one top-level directory named for the theme.
 */
const archive = (cwd, folder, destination) => {
  try {
    execFileSync('zip', ['-r', '-q', destination, folder], { cwd, stdio: 'ignore' });
    return 'zip';
  } catch (err) {
    // Only a missing binary is worth falling back for. A tool that ran and failed has a
    // real problem, and silently retrying with another would bury it.
    if (err.code !== 'ENOENT') {
      fail(`"zip -r -q ${destination} ${folder}" failed: ${err.message}`);
    }
  }

  if (!isBsdtar()) {
    return fail('`zip` is not available, and this `tar` cannot write zip archives. Install zip.');
  }

  try {
    execFileSync('tar', ['-a', '-c', '-f', destination, folder], { cwd, stdio: 'ignore' });
    return 'tar';
  } catch (err) {
    return fail(`"tar -a -c -f ${destination} ${folder}" failed: ${err.message}`);
  }
};

if (!existsSync(join(THEME, 'style.css'))) {
  fail(`No style.css in ${THEME}.\nRun this from the theme directory.`);
}

// The build folder is named in config.json, which webpack also reads — naming it here would
// be a second opinion about where the compiled stylesheet lives.
const configPath = join(THEME, 'config.json');

if (!existsSync(configPath)) {
  fail(`No config.json in ${THEME}.\nRun this from the theme directory.`);
}

const { dest } = JSON.parse(readFileSync(configPath, 'utf8'));

if (!existsSync(join(THEME, dest))) {
  fail(`No ${dest}/ directory.\nRun "npm run build" first — the theme needs its compiled stylesheet.`);
}

const staging = mkdtempSync(join(tmpdir(), 'theme-zip-'));
// The archive goes beside the site — inside wp-content, WordPress and any later `theme:zip`
// both have to step around it — and fatal when there is no site root: any other landing
// spot puts the archive among the installed themes.
const destination = join(requireSiteRoot(), `${SLUG}.zip`);

// Written under a name of its own and moved into place at the end. `zip` adds entries into an
// existing archive rather than replacing it, so the previous one cannot simply be written
// over — and removing it up front would leave nothing at all behind a failed build. Beside
// the destination rather than in the staging directory: a rename across filesystems fails,
// and a temporary directory is often one of its own. The name keeps the .zip extension:
// bsdtar picks the archive format from the suffix, and writes an ordinary tar for one it does
// not recognise.
const pending = `${destination}.pending.zip`;

// fail() ends the process from inside archive(), which skips finally blocks.
process.on('exit', () => rmSync(pending, { force: true }));

// `zip` adds to an archive it finds at the destination, so one left by a run that was killed
// outright would have this run's entries appended to its own.
rmSync(pending, { force: true });

try {
  cpSync(THEME, join(staging, SLUG), {
    recursive: true,
    filter: (source) => {
      if (source === THEME) return true;

      const parts = relative(THEME, source).split(sep);
      const [first] = parts;

      if (EXCLUDED.has(first)) return false;
      // The blocks' SCSS and JS sources compile into build/ beside them; only render
      // templates, metadata and that output belong in the archive.
      if (source.endsWith('.map') || source.endsWith('.scss')) return false;
      if (first === 'blocks' && parts.length === 3 && parts[2].endsWith('.js')) return false;

      return true;
    },
  });

  const tool = archive(staging, SLUG, pending);

  rmSync(destination, { force: true });
  renameSync(pending, destination);

  const size = Math.round(statSync(destination).size / 1024);

  console.log(`\n📦 ${SLUG}.zip  (${size} KB, ${tool})\n\n   Upload it under Appearance → Themes → Add New → Upload Theme.\n`);
} finally {
  rmSync(staging, { recursive: true, force: true });
}
