/*
    The build produces a WordPress theme, not a site: a stylesheet, a script bundle, the block
    assets, and everything copied to the theme root. WordPress renders the pages itself from the
    PHP templates, so nothing here generates HTML.
*/

import { execFileSync } from 'child_process';
import { chmodSync, existsSync, mkdirSync, readFileSync, readdirSync, statSync, writeFileSync } from 'fs';
import { homedir } from 'os';
import { basename, join, posix, sep } from 'path';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import CopyWebpackPlugin from 'copy-webpack-plugin';
import TerserPlugin from 'terser-webpack-plugin';
import ImageMinimizerPlugin from 'image-minimizer-webpack-plugin';
import RemoveEmptyScriptsPlugin from 'webpack-remove-empty-scripts';
import config from './config.json' with { type: 'json' };

// The theme root is where this file sits, not where webpack was launched from: every source
// path, `output.path` and `output.clean` below key off it, and a cwd-derived value would build
// into — and clean — whatever directory the command happened to run in.
const __dirname = import.meta.dirname;

// The same root with forward slashes throughout, for the paths that are joined with
// `posix.join` and end up in a glob. fast-glob — which copy-webpack-plugin builds its patterns
// with — reads a backslash as an escape character, so a Windows drive path matches nothing and
// reports no error.
const __posixdirname = __dirname.split(sep).join('/');

const SRC = config.src;
const DEST = config.dest;

// 'auto' resolves an asset's URL from the script that loaded it. The theme is served from
// wp-content/themes/<slug>/, which no build-time value knows and every page path differs from,
// so anything a runtime request resolves — an `import()` chunk, `new Worker(new URL(...))` —
// is only findable this way.
const PUBLIC_PATH = 'auto';

// `dest` scopes both emission and `output.clean`; a value resolving to the theme root would
// hand `clean` every PHP template in the theme.
if (!DEST || DEST === '.' || DEST === './' || DEST.startsWith('..') || DEST.startsWith('/')) {
  throw new Error(`config.json "dest" must name a folder inside the theme — got "${DEST}".`);
}

const getAssetPath = (type, assetPath) => {
  if (type === SRC) {
    return posix.join(__posixdirname, SRC, assetPath);
  }
  return posix.join(__posixdirname, DEST, assetPath);
};

const getAssetName = (dest, name, ext) => posix.join(dest, `${name}.${ext}`);

// Where a static folder is emitted. `dest` is optional and falls back to mirroring `src`.
const getStaticDest = (name) => {
  const asset = config.static[name];

  if (!asset) return '';

  return asset.dest ? asset.dest : asset.src;
};

// Skip `_`-prefixed folders (e.g. images/_originals) — same convention as the templates above.
const IGNORED_STATIC_PATHS = ['**/_*/**'];

const generateStaticAssets = () => {
  const assetsArray = [];

  for (const asset in config.static) {
    const assetObject = config.static[asset];
    const srcPath = getAssetPath(SRC, assetObject.src);
    const destPath = getAssetPath(DEST, getStaticDest(asset));

    if (existsSync(srcPath)) {
      assetsArray.push({ from: srcPath, to: destPath, globOptions: { ignore: IGNORED_STATIC_PATHS } });
    }
  }

  return assetsArray;
};

/*
    WordPress blocks. A block is a directory under blocks/ carrying a block.json, and each of its
    assets is built as its own entry rather than folded into the theme bundle — WordPress enqueues
    a block's style and view script only on requests where that block is present, which it can
    only do while they are separate files.

    A block's assets are addressed relative to its block.json, so they compile into a build/
    folder beside it rather than under `dest` with everything else. The folder also keeps the
    output apart from the sources it is built from: emitting `view.js` over the `view.js` it
    imports would hand the next compilation its own output — and in watch mode every emit
    would count as a source change, rebuilding forever.
*/
const BLOCKS_SRC = 'blocks';
const BLOCKS_BUILD = 'build';

/*
    WordPress reads every block.json from disk on every request that registers a block. A
    manifest is the one file it reads instead — `wp_register_block_metadata_collection()` in
    inc/blocks.php hands it this, and opcache keeps the parsed array between requests, so the
    per-block reads happen once at build time rather than once per page view.

    Emitted as a build asset so it can only ever describe the blocks this build found: a block
    removed from the tree leaves the manifest with it.
*/
const BLOCKS_MANIFEST = 'blocks-manifest.php';

// block.json holds strings, numbers, booleans, arrays and objects and nothing else, so this
// covers every value that can reach it. Written out as PHP source rather than as JSON in a
// string: opcache stores the compiled array, and a json_decode() would run per request.
const phpLiteral = (value, indent = '') => {
  if (value === null) return 'null';
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  if (typeof value === 'number') return String(value);
  if (typeof value !== 'object') return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

  const inner = `${indent}\t`;
  const entries = Array.isArray(value)
    ? value.map((item) => `${inner}${phpLiteral(item, inner)},`)
    : Object.entries(value).map(([key, item]) => `${inner}${phpLiteral(key)} => ${phpLiteral(item, inner)},`);

  return entries.length ? `array(\n${entries.join('\n')}\n${indent})` : 'array()';
};

const BLOCK_ENTRIES = [
  { file: 'style.scss', name: 'style' },
  { file: 'index.js', name: 'index' },
  { file: 'view.js', name: 'view' },
];

const isBlockEntry = (name) => name.startsWith(`${BLOCKS_SRC}/`);

// Only the native block flavour has an editor script; ACF draws the editor form from its field
// group instead. Nothing to externalise means nothing for the dependency plugin to do.
const hasEditorScripts = () => listBlocks().some((block) => existsSync(join(__dirname, BLOCKS_SRC, block, 'index.js')));

const listBlocks = () => {
  const blocksPath = join(__dirname, BLOCKS_SRC);

  if (!existsSync(blocksPath)) return [];

  return readdirSync(blocksPath, { withFileTypes: true })
    .filter((entry) => entry.isDirectory() && existsSync(join(blocksPath, entry.name, 'block.json')))
    .map((entry) => entry.name);
};

// Keyed by directory name, which is how WP_Block_Metadata_Registry addresses a block within a
// collection — the collection's own path is the blocks folder.
const blocksManifestPlugin = () => ({
  apply(compiler) {
    const { Compilation, sources } = compiler.webpack;

    compiler.hooks.thisCompilation.tap('BlocksManifest', (compilation) => {
      compilation.hooks.processAssets.tap({ name: 'BlocksManifest', stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL }, () => {
        const blocks = listBlocks();

        if (!blocks.length) return;

        const metadata = Object.fromEntries(blocks.map((block) => [block, JSON.parse(readFileSync(join(__dirname, BLOCKS_SRC, block, 'block.json'), 'utf8'))]));
        const php = `<?php\n/**\n * Block metadata, written by the build. Edit the block.json files instead.\n *\n * @package shopperexpress\n */\n\nreturn ${phpLiteral(metadata)};\n`;

        compilation.emitAsset(posix.join(BLOCKS_SRC, BLOCKS_MANIFEST), new sources.RawSource(php));
      });
    });
  },
});

const addBlockEntries = (entries) => {
  for (const block of listBlocks()) {
    for (const { file, name } of BLOCK_ENTRIES) {
      const source = posix.join(__posixdirname, BLOCKS_SRC, block, file);

      // Every one of the three is optional: a block may be styles only, or carry no front-end
      // behaviour at all.
      if (!existsSync(source)) continue;

      entries[posix.join(BLOCKS_SRC, block, BLOCKS_BUILD, name)] = {
        import: [source],
        filename: posix.join(BLOCKS_SRC, block, BLOCKS_BUILD, `${name}.js`),
      };
    }
  }

  return entries;
};

// An async chunk carries no entry name, and `output.path` is the theme root — so a chunk
// named by the default pattern lands beside functions.php, where `output.clean` keeps it
// because it is outside the two folders that get cleaned.
const CHUNK_FILENAME = {
  js: posix.join(DEST, getAssetName(config.scripts.dest, '[name].[contenthash]', 'js')),
  css: posix.join(DEST, getAssetName(config.styles.dest, '[name].[contenthash]', 'css')),
};

const pluginsConfiguration = {
  MiniCssExtract: {
    // `?? ''` for the same nameless chunks: isBlockEntry reads the name as a string.
    filename: (pathData) => (isBlockEntry(pathData.chunk?.name ?? '') ? '[name].css' : posix.join(DEST, getAssetName(config.styles.dest, '[name]', 'css'))),
    chunkFilename: CHUNK_FILENAME.css,
  },
  // Both linters cover the blocks' sources as well as src/, the same ground the npm lint
  // scripts walk. The blocks' build/ output is kept out by the lint configs themselves.
  StyleLint: {
    configFile: 'stylelint.config.js',
    files: [posix.join(SRC, config.styles.src, '**/*.scss'), posix.join(BLOCKS_SRC, '**/*.scss')],
  },
  ESLint: {
    overrideConfigFile: 'eslint.config.js',
    extensions: ['.js'],
    files: [join(SRC, config.scripts.src), BLOCKS_SRC],
  },
  ImageMinimizer: [
    {
      // webp/avif are produced by `npm run convert:webp|convert:avif` and are already
      // lossily encoded — re-minifying them here would only inflate them.
      test: /\.(jpe?g|png|gif)$/i,
      minimizer: {
        implementation: ImageMinimizerPlugin.sharpMinify,
        options: {
          encodeOptions: {
            // https://sharp.pixelplumbing.com/api-output#jpeg
            jpeg: { quality: 80 },
            // PNG quality 100% == lossless. https://sharp.pixelplumbing.com/api-output#png
            png: { compressionLevel: 2 },
            // GIF does not support lossless compression at all
            // https://sharp.pixelplumbing.com/api-output#gif
            gif: {},
          },
        },
      },
    },
    {
      minimizer: {
        implementation: ImageMinimizerPlugin.svgoMinify,
        options: {
          encodeOptions: {
            multipass: true,
            // https://github.com/svg/svgo#default-preset
            plugins: ['preset-default'],
          },
        },
      },
    },
  ],
};

/*
    `scope` mirrors the compiler split in serve mode: 'all' is the single production compiler,
    'theme' and 'blocks' are the two serve-mode compilers. Each linter is attached where the
    files it lints are compiled — a plugin only re-runs when its own compiler rebuilds, so one
    attached elsewhere would lint stale sources. The static copy runs once, in the compiler
    that is always present.
*/
const getPlugins = async (mode, scope = 'all') => {
  const isProduction = mode === 'production';
  const plugins = [new RemoveEmptyScriptsPlugin(), new MiniCssExtractPlugin(pluginsConfiguration.MiniCssExtract)];

  if (scope !== 'blocks') {
    const staticAssets = generateStaticAssets();

    if (staticAssets.length) {
      plugins.push(new CopyWebpackPlugin({ patterns: staticAssets }));
    }
  }

  // Attached where the blocks are compiled, so a block edited in serve mode rewrites it.
  if (scope !== 'theme') {
    plugins.push(blocksManifestPlugin());
  }

  if (isProduction) {
    for (const opts of pluginsConfiguration.ImageMinimizer) {
      plugins.push(new ImageMinimizerPlugin(opts));
    }
  }

  // Loaded only when enabled — package isn't required to be installed otherwise. All
  // stylesheet compilation happens in the blocks-side compiler during serve, so that is
  // where the CSS linter re-runs on edits.
  if (config.linters && config.linters.css && scope !== 'theme') {
    const { default: StyleLintPlugin } = await import('stylelint-webpack-plugin');
    plugins.push(new StyleLintPlugin(pluginsConfiguration.StyleLint));
  }

  if (config.linters && config.linters.js) {
    const { default: ESLintPlugin } = await import('eslint-webpack-plugin');
    const files = { all: pluginsConfiguration.ESLint.files, theme: [join(SRC, config.scripts.src)], blocks: [BLOCKS_SRC] };
    plugins.push(new ESLintPlugin({ ...pluginsConfiguration.ESLint, files: files[scope] }));
  }

  // Rewrites the `@wordpress/*` imports a block's editor script makes to the `wp.*` globals
  // WordPress already serves, and emits the .asset.php files register_block_type() reads to
  // learn which of those a script depends on. Loaded only when such a script exists, so an ACF
  // theme never needs the package installed. Applied in every scope, so a dev tree and a
  // production tree carry the same .asset.php files.
  if (hasEditorScripts()) {
    const { default: DependencyExtractionPlugin } = await import('@wordpress/dependency-extraction-webpack-plugin');
    plugins.push(new DependencyExtractionPlugin({ injectPolyfill: false }));
  }

  return plugins;
};

const getScriptsLoader = (scriptsType) => {
  // Opt-in via `"extension": "ts"` in config.json — install typescript + ts-loader and add a
  // tsconfig.json yourself, they are not part of the default template.
  if (/ts/.test(scriptsType)) {
    return {
      test: /\.tsx?$/,
      exclude: /node_modules/,
      rules: [{ use: ['ts-loader'] }],
    };
  }

  return {
    test: /\.m?js$/,
    exclude: /node_modules/,
    rules: [{ use: ['babel-loader'] }],
  };
};

const getModules = (isProduction) => {
  const shouldMinimizeCSS = config.minimizeCSS && isProduction;

  const modules = {
    rules: [
      {
        test: /\.(sa|sc|c)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          { loader: 'css-loader', options: { url: false } },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: {
                  cssnano: shouldMinimizeCSS ? {} : false,
                },
                config: true,
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sassOptions: {
                quietDeps: true,
                silenceDeprecations: ['import'],
                // sass-loader picks `style` from webpack's mode, so a production build
                // emits compressed CSS whatever cssnano above is told to do; the flag
                // drives the output style so `"minimizeCSS": false` holds.
                style: shouldMinimizeCSS ? 'compressed' : 'expanded',
              },
            },
          },
        ],
      },
      // Only reached by assets imported from JS — CopyWebpackPlugin handles everything referenced
      // from markup or CSS. Both destinations come from config.json for the same reason the
      // copied ones do: naming the folder here would put a JS-imported image somewhere else
      // than an identical copied one the moment `static.images.dest` changes.
      {
        test: /\.(png|jpe?g|gif|svg|webp)$/,
        type: 'asset/resource',
        generator: { filename: posix.join(DEST, getStaticDest('images'), '[name][ext]') },
      },
      {
        test: /\.(woff(2)?|eot|ttf|otf)$/,
        type: 'asset/resource',
        generator: { filename: posix.join(DEST, getStaticDest('fonts'), '[name][ext]') },
      },
    ],
  };

  modules.rules.unshift(getScriptsLoader(config.scripts.extension));

  return modules;
};

/*
    Persistent build cache in node_modules/.cache/webpack (webpack's default location, so it is
    already covered by the /node_modules/ entry in .gitignore). Without it `npm run build` has no
    cache at all in production mode and re-runs sass, babel, Terser and the sharp/svgo image
    minification from scratch every time.

    No explicit `buildDependencies` is needed: webpack-cli registers webpack.config.mjs and follows
    its imports down to config.json, and babel-loader / postcss-loader register their own config
    files. All three were verified to invalidate correctly. Listing them here as well would only
    turn a targeted rebuild into a full cache bust. Run `npm run clean:cache` if a build ever does
    look stale.
*/
const getCache = () => {
  // A one-shot CI build never reuses the cache, so it would only pay the serialization cost.
  if (process.env.CI) return false;

  return {
    type: 'filesystem',
    // These projects cache minified image buffers, which makes the pack large.
    compression: 'gzip',
  };
};

const getOptimization = (isProduction) => {
  if (!isProduction) return {};

  return {
    minimize: config.minimizeJS,
    minimizer: [
      new TerserPlugin({
        extractComments: false,
        terserOptions: {
          compress: {
            inline: false,
            drop_console: ['log', 'info', 'debug'],
          },
          format: { comments: false },
        },
      }),
    ],
  };
};

const getThemeEntries = () => {
  const entries = {};

  if (config.scripts) {
    const entryJsFile = join(config.scripts.src, `${config.scripts.bundle}.${config.scripts.extension}`);

    entries[config.scripts.bundle] = [getAssetPath(SRC, entryJsFile)];

    // Scripts loaded only on some screens (e.g. the SOC admin pages), each its own file beside
    // the main bundle in the scripts dest.
    for (const name of config.scripts.extra ?? []) {
      entries[name] = [getAssetPath(SRC, join(config.scripts.src, `${name}.${config.scripts.extension}`))];
    }
  }

  if (config.styles) {
    const entryCSSFile = join(config.styles.src, `${config.styles.bundle}.${config.styles.extension}`);
    const styleAsset = getAssetPath(SRC, entryCSSFile);

    if (entries[config.styles.bundle]) {
      entries[config.styles.bundle].push(styleAsset);
    } else {
      entries[config.styles.bundle] = [styleAsset];
    }

    // Stylesheets loaded only on some screens (wp-login.php, wp-admin), each its own file
    // beside the main bundle in the styles dest.
    // A name shared with scripts.extra (soc) joins that entry: one entry, a .js and a .css.
    for (const name of config.styles.extra ?? []) {
      const extraStyle = getAssetPath(SRC, join(config.styles.src, `${name}.${config.styles.extension}`));

      if (entries[name]) {
        entries[name].push(extraStyle);
      } else {
        entries[name] = [extraStyle];
      }
    }
  }

  return entries;
};

const getEntries = () => addBlockEntries(getThemeEntries());

// An entry importing only stylesheets emits a script stub the RemoveEmptyScripts plugin
// discards — unless the dev server has injected its client into it, which makes the stub a
// real file. Serve mode therefore keeps such entries out of the compiler the client lands in.
const isStyleOnlyEntry = (imports) => imports.every((file) => file.endsWith(`.${config.styles.extension}`));

// mkcert keeps its CA in a fixed per-user location, overridable with $CAROOT. DDEV sets that
// CA up and trusts it for its own router, so a machine running DDEV has it. The candidates
// are checked in the order mkcert itself checks them — $XDG_DATA_HOME wins over the macOS
// default whenever it is set, on macOS too — so this always looks where mkcert wrote.
const getMkcertRoot = () => {
  if (process.env.CAROOT) return process.env.CAROOT;

  const home = homedir();

  if (process.platform === 'win32') return join(process.env.LOCALAPPDATA ?? join(home, 'AppData', 'Local'), 'mkcert');
  if (process.env.XDG_DATA_HOME) return join(process.env.XDG_DATA_HOME, 'mkcert');
  if (process.platform === 'darwin') return join(home, 'Library', 'Application Support', 'mkcert');
  return join(home, '.local', 'share', 'mkcert');
};

/*
    The proxied WordPress site is HTTPS, and browsers probe HTTPS first even on loopback —
    Safari renders a blank page when that probe hits a plain-HTTP port. So when the mkcert CA
    is on this machine, the dev server speaks HTTPS with a loopback certificate minted from
    it, which every local browser already trusts — no warning to click through. The leaf
    carries what Apple demands of a locally-trusted certificate: SHA-2, a 2048-bit key, under
    825 days of validity, the serverAuth EKU and a SAN list. Without the CA, or without
    openssl to mint with, the server stays on plain HTTP.
*/
const getDevServerTLS = () => {
  const caRoot = getMkcertRoot();
  const ca = join(caRoot, 'rootCA.pem');
  const caKey = join(caRoot, 'rootCA-key.pem');

  if (!existsSync(ca) || !existsSync(caKey)) {
    // The fallback is plain HTTP, which Safari renders as a blank page — worth a line saying
    // why, same as a failed mint below. DDEV installs the mkcert CA this looks for.
    console.warn('⚠️  No mkcert CA found — serving plain HTTP. Install mkcert (or DDEV) and run `mkcert -install` for trusted HTTPS.');
    return null;
  }

  const dir = join(__dirname, 'node_modules', '.cache', 'devserver-tls');
  const cert = join(dir, 'cert.pem');
  const key = join(dir, 'key.pem');

  // A leaf older than the CA was signed by a CA that no longer exists, and a leaf inside 30
  // days of its notAfter is about to be rejected by every browser with no click-through —
  // both are reminted. `-checkend` exits 1 for a leaf expiring within the window; any other
  // failure (no openssl to check with) leaves a present leaf in use rather than discarding
  // a certificate that may be fine.
  const needsMint = () => {
    if (!existsSync(cert) || !existsSync(key)) return true;
    if (statSync(cert).mtimeMs < statSync(ca).mtimeMs) return true;

    try {
      execFileSync('openssl', ['x509', '-checkend', '2592000', '-noout', '-in', cert], { stdio: 'pipe' });
      return false;
    } catch (err) {
      return typeof err.status === 'number' && err.status !== 0;
    }
  };

  if (needsMint()) {
    const csr = join(dir, 'csr.pem');
    const ext = join(dir, 'ext.cnf');

    try {
      // The key is trusted by every local browser through the mkcert CA, so it stays readable
      // by this user alone — as does the folder it is minted into.
      mkdirSync(dir, { recursive: true, mode: 0o700 });
      writeFileSync(ext, 'basicConstraints=CA:FALSE\nkeyUsage=digitalSignature,keyEncipherment\nextendedKeyUsage=serverAuth\nsubjectAltName=DNS:localhost,IP:127.0.0.1,IP:::1\n');
      execFileSync('openssl', ['req', '-new', '-newkey', 'rsa:2048', '-nodes', '-keyout', key, '-out', csr, '-subj', '/CN=webpack dev server'], { stdio: 'pipe' });
      chmodSync(key, 0o600);
      execFileSync('openssl', ['x509', '-req', '-in', csr, '-CA', ca, '-CAkey', caKey, '-CAserial', join(dir, 'ca.srl'), '-CAcreateserial', '-sha256', '-days', '397', '-extfile', ext, '-out', cert], { stdio: 'pipe' });
    } catch (err) {
      // The fallback is plain HTTP, which Safari renders as a blank page — the warning
      // names the reason.
      const reason = (err.stderr?.toString().trim() || err.message || '').split('\n')[0];
      console.warn(`⚠️  Could not mint a dev-server certificate (${reason}) — serving plain HTTP.`);
      return null;
    }
  }

  return { type: 'https', options: { cert, key } };
};

// The dev server only ever fronts an existing WordPress: PHP renders the pages, so there is
// nothing to serve from the build folder alone. `proxy` is therefore required. Any other key
// in the `devserver` block passes straight through to webpack-dev-server.
const getDevServer = () => {
  const { proxy, ...rest } = config.devserver ?? {};

  if (!proxy) {
    throw new Error('devserver.proxy in config.json is empty — point it at the address WordPress answers on (see "Devserver" in the README).');
  }

  const server = getDevServerTLS();

  return {
    // Loopback only. The proxy rewrites `Host` so DDEV can route the request, so the markup
    // always points its assets at `<theme>.ddev.site` — an address only this machine resolves.
    // A LAN bind would only print a URL that serves broken pages; real-device testing goes
    // through `ddev share` (see "Testing on a real device" in the README). Bound by address:
    // the name `localhost` resolves to a single family — often `::1`, leaving `127.0.0.1`
    // refused for any client that does not fall back across families.
    host: '127.0.0.1',
    // 'auto' takes the first free port from 8080, so several projects can run at once.
    port: 'auto',
    open: true,
    // styles are extracted to a file, so a hot module swap has nothing to replace — the
    // page reloads.
    hot: false,
    liveReload: true,
    // The addresses the bind above can be reached at. The server proxies WordPress and
    // forwards its cookies, and a name under someone else's control can resolve to 127.0.0.1
    // — Host checking is what stops such a page reading back a logged-in wp-admin.
    allowedHosts: ['localhost', '127.0.0.1', '[::1]'],
    // the build must leave real files in DEST, not only serve them from memory.
    // Match WordPress asset URLs so the middleware serves this compilation before proxying.
    devMiddleware: { writeToDisk: true, publicPath: `/wp-content/themes/${basename(__dirname)}/` },
    // The PHP templates are not modules — WordPress renders them — so webpack's own watching
    // never sees them. Watched here, so editing one reloads the page like an SCSS edit.
    // Build output is excluded: the build emits .asset.php into `dest` and the blocks'
    // build/ folders, and an emit must not fire a second reload on top of the one the
    // compilation already triggers. block.json is registration metadata WordPress reads per
    // request, so editing it reloads too.
    watchFiles: {
      paths: ['**/*.php', 'theme.json', 'blocks/*/block.json'],
      options: { ignored: ['**/node_modules/**', '**/vendor/**', `**/${DEST}/**`, '**/blocks/*/build/**'] },
    },
    ...(server ? { server } : {}),
    ...rest,
    // Everything except /ws falls through to WordPress. /ws is the dev server's own live-reload
    // socket: proxied, its upgrade goes to WordPress and never completes — the client retries
    // forever and the browser never leaves its loading state.
    proxy: [
      {
        context: (pathname) => pathname !== '/ws',
        target: proxy,
        changeOrigin: true,
        secure: false,
        ws: true,
        // DDEV routes by `Host` and rewrites every X-Forwarded-* header, so the browser's real
        // address travels in headers the router leaves alone. wp-config.php builds WP_HOME from
        // them — which is what keeps a proxied page's assets and links on this server, where
        // live reload works, instead of on the proxied hostname.
        on: {
          proxyReq: (proxyReq, req) => {
            if (req.headers.host) proxyReq.setHeader('X-Devserver-Host', req.headers.host);
            proxyReq.setHeader('X-Devserver-Proto', server ? 'https' : 'http');
          },
        },
      },
    ],
  };
};

// `argv` carries the flags webpack was invoked with, `env` the ones webpack-cli sets for its
// own commands. An explicit --mode wins; `webpack serve` is development, since serving a
// production bundle is not what it is for; everything else is production. Nothing here reads
// the npm script name, so a renamed script, a task runner or a bare `npx webpack` all get the
// mode their invocation implies.
export default async (env = {}, argv = {}) => {
  const mode = argv.mode ?? (env.WEBPACK_SERVE ? 'development' : 'production');
  const isProduction = mode === 'production';

  const base = {
    mode,
    // `hidden` emits the maps without the sourceMappingURL comment that points at them, so a
    // shipped theme is debuggable from the map if you have it and gives nothing away if you
    // do not.
    devtool: isProduction ? 'hidden-source-map' : 'source-map',
    stats: 'minimal',
    externals: {
      jquery: 'jQuery',
    },
    output: {
      publicPath: PUBLIC_PATH,
      // The theme, not the build folder: a block's assets belong beside its block.json, which
      // is outside `dest`, and webpack refuses to emit above its output path.
      path: __dirname,
      filename: posix.join(DEST, getAssetName(config.scripts.dest, '[name]', 'js')),
      chunkFilename: CHUNK_FILENAME.js,
      crossOriginLoading: 'anonymous',
      // Only build output is cleaned — `dest` and the blocks' build/ folders. `clean: true`
      // would delete every PHP template in the theme, since webpack removes whatever it did
      // not emit.
      clean: { keep: (asset) => !asset.startsWith(`${DEST}/`) && !new RegExp(`^${BLOCKS_SRC}/[^/]+/${BLOCKS_BUILD}/`).test(asset) },
    },
    cache: getCache(),
    resolve: {
      mainFiles: ['index'],
      extensions: [`.${config.scripts.extension}`],
      alias: {
        JS: getAssetPath(SRC, config.scripts.src),
        Utils: getAssetPath(SRC, `${config.scripts.src}/utils`),
        Vendors: getAssetPath(SRC, `${config.scripts.src}/vendors`),
        Plugins: getAssetPath(SRC, `${config.scripts.src}/plugins`),
        Components: getAssetPath(SRC, `${config.scripts.src}/components`),
        Animations: getAssetPath(SRC, `${config.scripts.src}/animations`),
      },
    },
    optimization: {
      usedExports: true,
      ...getOptimization(isProduction),
    },
    module: getModules(isProduction),
  };

  // A plain build is one compiler over every entry; getDevServer() requires a proxy target,
  // which it has no business demanding.
  if (!env.WEBPACK_SERVE) {
    return { ...base, name: 'theme', entry: getEntries(), plugins: await getPlugins(mode) };
  }

  /*
      Serve mode splits the build in two. The dev server injects its live-reload client into
      every entry of every web-target compiler — there is no per-entry opt-out — and a block's
      view script is enqueued by WordPress on real pages, where each block would open its own
      reload socket and carry the client's weight. The blocks compiler therefore opts out
      wholesale with `target: false`, which the server's injection check excludes; a rebuild
      there still reloads the page through the client the theme bundle carries, because the
      server broadcasts every compiler's results to every connected page. Style-only theme
      entries ride with the blocks for the same reason: the injected client would turn their
      discarded script stub into a real emitted file.
  */
  const scriptEntries = {};
  const blocksSideEntries = {};

  for (const [name, imports] of Object.entries(getThemeEntries())) {
    (isStyleOnlyEntry(imports) ? blocksSideEntries : scriptEntries)[name] = imports;
  }

  addBlockEntries(blocksSideEntries);

  // The two compilers share one output tree, and `clean` removes whatever its own compilation
  // did not emit — each would delete the other's files. Dev leaves cleaning to `npm run build`.
  const output = { ...base.output, clean: false };

  const serveTheme = {
    ...base,
    name: 'theme',
    entry: scriptEntries,
    output,
    plugins: await getPlugins(mode, 'theme'),
    devServer: getDevServer(),
  };

  // Nothing on the blocks side: every entry belongs with the client, one compiler serves all.
  if (!Object.keys(blocksSideEntries).length) {
    return { ...serveTheme, plugins: await getPlugins(mode) };
  }

  // No script bundle for the client to ride in: fall back to one web compiler, accepting the
  // client in every entry over a page with no live reload at all.
  if (!Object.keys(scriptEntries).length) {
    return { ...base, name: 'theme', entry: getEntries(), output, plugins: await getPlugins(mode), devServer: getDevServer() };
  }

  return [
    serveTheme,
    {
      ...base,
      name: 'blocks',
      target: false,
      entry: blocksSideEntries,
      // `target: false` leaves webpack no default chunk format; these are browser scripts, so
      // state what the web target would have chosen.
      output: { ...output, chunkFormat: 'array-push', chunkLoading: 'jsonp' },
      plugins: await getPlugins(mode, 'blocks'),
    },
  ];
};
