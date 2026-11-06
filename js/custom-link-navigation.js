(function () {
  'use strict';
  function run() {
    Object.keys(window.YoAACustomLinkTabs || {}).forEach(function (key) {
      if (!/^[a-z0-9-]+$/.test(key)) return;
      var entry = window.YoAACustomLinkTabs[key];
      document.querySelectorAll('.woocommerce-MyAccount-navigation-link--' + key + ' > a').forEach(function (anchor) {
        if (anchor.href !== new URL(entry.url, document.baseURI).href) return;
        anchor.target = '_blank';
        var rel = (anchor.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
        ['noopener', 'noreferrer'].forEach(function (token) { if (rel.indexOf(token) === -1) rel.push(token); });
        anchor.setAttribute('rel', rel.join(' '));
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, {once: true});
  else run();
}());
