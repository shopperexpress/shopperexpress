const { defineConfig } = require('eslint/config');
const js = require('@eslint/js');
const globals = require('globals');
const eslintPluginPrettierRecommended = require('eslint-plugin-prettier/recommended');

// Third-party code kept in the tree as it was published: priority-nav, with the Element.prototype.remove
// override the forms rely on. Not formatted, not linted.
const VENDORED = ['src/js/plugins/priorityNavPlugin.js'];

// Scripts moved over from assets-old/old/js unchanged. Kept out of the linters until they are
// rewritten to the theme's rules, like src/styles/legacy/ for Stylelint.
const LEGACY = ['src/js/legacy/'];

module.exports = defineConfig([
  {
    // assets/ and the blocks' build/ folders are build output, not source. src/js/utils is
    // the project's own — app.js imports it through the Utils alias — and is deliberately
    // not excluded.
    ignores: ['node_modules/', 'assets/', 'blocks/*/build/', ...VENDORED, ...LEGACY],
  },
  // The rules that find mistakes rather than style: undefined names, unreachable code,
  // duplicate object keys, a bound variable nothing reads. The stylistic rules below are
  // additions to these, not a replacement for them.
  js.configs.recommended,
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 'latest',
      globals: {
        ...globals.browser,
        // WordPress enqueues its own jQuery, and the build resolves `jquery` imports to this
        // global rather than bundling a copy — so it is defined on every page of the theme.
        jQuery: 'readonly',
        // Globals the theme's scripts share. The plugins under src/js/plugins register them on
        // window, WordPress localises `ajax`, and the rest come from third-party scripts on the page.
        jcf: 'readonly',
        ResponsiveHelper: 'readonly',
        SmoothScroll: 'readonly',
        ajax: 'readonly',
        socData: 'readonly',
        acf: 'readonly',
        bootstrap: 'readonly',
        gtag: 'readonly',
        inticeEvents: 'readonly',
        google: 'readonly',
        YT: 'readonly',
        Vimeo: 'readonly',
        _wq: 'readonly',
        DocumentTouch: 'readonly',
        // The jcf plugin is a UMD wrapper that probes for these module systems, and the responsive
        // helper falls back to a legacy `media` global.
        define: 'readonly',
        module: 'readonly',
        require: 'readonly',
        media: 'readonly',
      },
      sourceType: 'module',
    },
    rules: {
      'prettier/prettier': 'error',
      semi: 'error',
      // Errors and warnings stay useful in the console; debug logging does not.
      'no-console': ['error', { allow: ['warn', 'error'] }],
      'no-unused-expressions': [
        'error',
        {
          allowTernary: true,
          allowShortCircuit: true,
        },
      ],
      'no-plusplus': 'off',
      // The scripts under src/js carry long-lived code that is kept as it was written.
      'no-unused-vars': ['error', { args: 'none', caughtErrors: 'none' }],
      'no-empty': ['error', { allowEmptyCatch: true }],
      'no-useless-assignment': 'off',
      'no-useless-escape': 'off',
      'no-fallthrough': 'off',
      'no-self-assign': 'off',
    },
  },
  {
    // Plugins are kept close to how they were first written, so unused bindings in them are
    // not reported.
    files: ['src/js/plugins/**/*.js'],
    rules: {
      'no-unused-vars': 'off',
    },
  },
  eslintPluginPrettierRecommended,
]);
