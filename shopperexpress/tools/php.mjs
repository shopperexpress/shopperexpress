/**
 * Runs the PHP tooling inside the DDEV container.
 *
 * Both tools live in the theme's own vendor/, but `ddev exec` runs at the project root — so the
 * working directory has to be named explicitly, as a path inside the container rather than on
 * the host.
 *
 * Usage: node tools/php.mjs [--fix]
 */

import { execFileSync } from 'child_process';
import { relative, sep } from 'path';
import { THEME, fail, requireDdev, requireSiteRoot } from './ddev.mjs';

const FIX = process.argv.includes('--fix');

// DDEV mounts the project at this path, whatever it is called on the host.
const CONTAINER_ROOT = '/var/www/html';

requireDdev();

const ROOT = requireSiteRoot();
const workingDir = [CONTAINER_ROOT, ...relative(ROOT, THEME).split(sep)].join('/');

const ddevStatus = (...args) => {
  try {
    execFileSync('ddev', args, { cwd: ROOT, stdio: 'inherit' });
    return 0;
  } catch (err) {
    return typeof err.status === 'number' ? err.status : 1;
  }
};

const ddev = (...args) => ddevStatus(...args) === 0;

const inTheme = (...command) => ddev('exec', '-d', workingDir, ...command);

const inThemeStatus = (...command) => ddevStatus('exec', '-d', workingDir, ...command);

if (!inTheme('composer', 'install', '--quiet', '--no-interaction')) {
  fail('Could not install the PHP tooling. Is the site started? Try "ddev start".');
}

// phpcbf reports through its exit code rather than a boolean: 0 is "nothing to fix", 1 is
// "fixed everything fixable" — both successes — and only 2+ means problems remain.
const phpcsOk = FIX ? inThemeStatus('vendor/bin/phpcbf') <= 1 : inTheme('vendor/bin/phpcs');

// Both run even if the first reports problems: seeing every finding at once beats fixing one
// tool's worth and discovering the other's afterwards.
const results = [
  ['phpcs', phpcsOk],
  ['phpstan', FIX || inTheme('vendor/bin/phpstan', 'analyse', '--no-progress')],
];

const failed = results.filter(([, ok]) => !ok).map(([name]) => name);

if (failed.length) {
  fail(`${failed.join(' and ')} reported problems.`);
}

console.log(`\n✅ ${FIX ? 'Fixed what could be fixed.' : 'PHP is clean.'}\n`);
