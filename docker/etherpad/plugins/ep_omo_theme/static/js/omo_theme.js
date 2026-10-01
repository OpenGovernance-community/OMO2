'use strict';

const skinVariants = require('ep_etherpad-lite/static/js/skin_variants');

const darkVariants = ['super-dark-editor', 'dark-background', 'super-dark-toolbar'];
const lightVariants = ['super-light-toolbar', 'super-light-editor', 'light-background'];

const normalizePreference = (preference) => (
  preference === 'dark' || preference === 'light' || preference === 'system'
    ? preference
    : 'system'
);

const resolveTheme = (preference) => {
  if (preference === 'dark' || preference === 'light') return preference;
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    ? 'dark'
    : 'light';
};

const applyTheme = (preference) => {
  skinVariants.updateSkinVariantsClasses(
    resolveTheme(normalizePreference(preference)) === 'dark' ? darkVariants : lightVariants,
  );
};

exports.postAceInit = () => {
  window.addEventListener('message', (event) => {
    const data = event && event.data && typeof event.data === 'object' ? event.data : null;
    if (!data || data.type !== 'omo-theme-sync') return;
    applyTheme(data.preference);
  });
};
