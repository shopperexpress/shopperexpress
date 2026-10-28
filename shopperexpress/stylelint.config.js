module.exports = {
  extends: ['stylelint-config-sass-guidelines'],
  // `**`: vendored code nests (src/styles/vendors/include-media/), and a single star
  // matches only direct children.
  // src/styles/legacy/ holds stylesheets carried over verbatim from assets-old/ until they are
  // ported to the theme's conventions.
  ignoreFiles: ['src/styles/base/**', 'src/styles/vendors/**', 'src/styles/vendors-extensions/**', 'src/styles/legacy/**'],
  rules: {
    'selector-no-vendor-prefix': [
      true,
      {
        ignoreSelectors: ['::-webkit-input-placeholder', '/-moz-.*/'],
      },
    ],
    'declaration-block-no-duplicate-properties': true,
    'declaration-empty-line-before': ['never'],
    'rule-empty-line-before': [
      'always-multi-line',
      {
        except: ['first-nested'],
        ignore: ['after-comment'],
      },
    ],
    'at-rule-empty-line-before': [
      'always',
      {
        except: ['first-nested', 'blockless-after-same-name-blockless'],
        ignore: ['after-comment'],
        ignoreAtRules: ['else'],
      },
    ],
    'property-no-vendor-prefix': [
      true,
      {
        ignoreProperties: [
          'outer-spin-button',
          'inner-spin-button',
        ],
      },
    ],
    'selector-class-pattern': null,
    'scss/at-extend-no-missing-placeholder': null,
    // Tailwind's own at-rules. Unknown to the SCSS syntax, so without this a Tailwind
    // project fails to lint the moment it uses @apply or declares its sources.
    'scss/at-rule-no-unknown': [
      true,
      {
        ignoreAtRules: ['apply', 'config', 'custom-variant', 'plugin', 'reference', 'source', 'tailwind', 'theme', 'utility', 'variant'],
      },
    ],
    'selector-pseudo-element-colon-notation': 'double',
    'max-nesting-depth': [
      6,
      {
        ignore: ['blockless-at-rules', 'pseudo-classes'],
        ignoreAtRules: ['include'],
      },
    ],
    '@stylistic/string-quotes': 'single',
    'selector-max-id': 1,
    'selector-max-compound-selectors': 6,
    'selector-no-qualifying-type': [true, { 'ignore': ['class', 'attribute'] }],
  },
};
