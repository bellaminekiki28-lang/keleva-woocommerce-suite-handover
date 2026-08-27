(function () {
  'use strict';

  function markDecorativeVendorLogos(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('.wcfmmp_sold_by_container_left > img:not([alt])').forEach(function (image) {
      image.setAttribute('alt', '');
      image.setAttribute('aria-hidden', 'true');
    });
  }

  function normalizeBrandLabel(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('.site-brand[aria-label]').forEach(function (brand) {
      if (/avocado toast|brunch|café|الأفوكادو|برنش|المقهى/i.test(brand.getAttribute('aria-label') || '')) {
        brand.setAttribute('aria-label', document.documentElement.dir === 'rtl' ? 'فيلورا، العودة إلى أعلى الصفحة' : 'Velora, revenir en haut');
      }
    });
  }

  function restoreBrokenResponsiveImages(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('picture > img').forEach(function (image) {
      function useNativeFallback() {
        var picture = image.parentElement;
        if (image.dataset.kelevaMediaFallback || !picture || picture.tagName !== 'PICTURE') {
          return;
        }
        image.dataset.kelevaMediaFallback = '1';
        picture.querySelectorAll('source[type="image/avif"], source[type="image/webp"]').forEach(function (source) {
          source.remove();
        });
        image.src = image.getAttribute('src') || image.src;
      }

      image.addEventListener('error', useNativeFallback, { once: true });
      if (image.complete && !image.naturalWidth) {
        useNativeFallback();
      }
    });
  }

  function init() {
    markDecorativeVendorLogos(document);
    normalizeBrandLabel(document);
    restoreBrokenResponsiveImages(document);

    if (!window.MutationObserver) {
      return;
    }

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            markDecorativeVendorLogos(node);
            normalizeBrandLabel(node);
            restoreBrokenResponsiveImages(node);
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
