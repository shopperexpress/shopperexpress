module.exports = function (api) {
  api.cache(true);

  const presets = [['@babel/preset-env']];

  const plugins = [
    [
      'babel-plugin-polyfill-corejs3',
      {
        method: 'usage-global',
        version: require('core-js/package.json').version,
      },
    ],
  ];

  return {
    presets,
    plugins,
  };
};
