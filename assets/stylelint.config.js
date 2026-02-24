module.exports = {
  extends: ['stylelint-config-sass-guidelines'],
  plugins: ['stylelint-selector-bem-pattern'],
  ignoreFiles: ['src/styles/base/*', 'src/styles/vendors/*', 'src/styles/vendors-extensions/*'],
  rules: {
    'selector-no-vendor-prefix': [
      true,
      {
        ignoreSelectors: ['::-webkit-input-placeholder', '/-moz-.*/']
      }
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
    'property-no-vendor-prefix': [
      true,
      {
        ignoreProperties: [
          'appearance',
          'outer-spin-button',
          'inner-spin-button',
          'src/styles/vendors/*',
          'src/styles/vendors-extensions/*',
          'src/styles/base/*',
          'src/styles/typography/*',
        ],
      },
    ],
    'selector-class-pattern': null,
    'scss/at-extend-no-missing-placeholder': null,
    'selector-pseudo-element-colon-notation': 'double',
    'max-nesting-depth': [
      3,
      {
        ignore: ['blockless-at-rules', 'pseudo-classes'],
        ignoreAtRules: ['include'],
      },
    ],
    'plugin/selector-bem-pattern': {
      componentName: '[A-Z]+',
      componentSelectors: {
        initial: '^\\.{componentName}(?:-[a-z]+)?$',
        combined: '^\\.combined-{componentName}-[a-z]+$',
      },
      utilitySelectors: '^\\.util-[a-z]+$',
    },
  },
};
