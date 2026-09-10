module.exports = {
  env: {
    browser: true,
    node: true,
    es6: true,
    jquery: true,
  },
  ignorePatterns: ['src/js/vendors/', 'src/js/utils/', 'node_modules/'],
  extends: ['airbnb-base', 'prettier'],
  plugins: ['prettier'],
  settings: {
    'import/resolver': 'webpack',
  },
  rules: {
    'prettier/prettier': ['error', {
      'endOfLine': 'auto',
    }],
    'no-console': ['off'],
    'no-unused-expressions': ['error', {
      'allowTernary': true,
      'allowShortCircuit': true,
    }],
    'no-plusplus': ['error', {
      'allowForLoopAfterthoughts': true
    }]
  },
};
