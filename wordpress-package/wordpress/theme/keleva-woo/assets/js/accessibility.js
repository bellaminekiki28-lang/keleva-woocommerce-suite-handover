(function () {
  'use strict';

  function markDecorativeVendorLogos(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('.wcfmmp_sold_by_container_left > img:not([alt])').forEach(function (image) {
      image.setAttribute('alt', '');
      image.setAttribute('aria-hidden', 'true');
    });
  }

  function init() {
    markDecorativeVendorLogos(document);

    if (!window.MutationObserver) {
      return;
    }

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            markDecorativeVendorLogos(node);
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
}());
