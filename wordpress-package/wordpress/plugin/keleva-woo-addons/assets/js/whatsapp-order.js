(() => {
  'use strict';

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-keleva-whatsapp-order]');
    if (!link) return;
    link.setAttribute('aria-busy', 'true');
    link.setAttribute('aria-label', 'Préparation de votre commande WhatsApp');
  });
})();
