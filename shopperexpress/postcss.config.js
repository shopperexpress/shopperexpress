module.exports = {
  plugins: {
    'postcss-sort-media-queries': { sort: 'mobile-first' },
    // Turn off the custom-properties fallback feature: it injects a resolved
    // value before every var() (e.g. `color:#0052a1;color:var(--token)`), which
    // duplicates token-based declarations across the whole stylesheet. Every
    // browser target supports CSS custom properties natively, so the fallbacks
    // are dead weight; disabling keeps var() untouched and trims the build.
    'postcss-preset-env': { features: { 'custom-properties': false } },
  },
};
