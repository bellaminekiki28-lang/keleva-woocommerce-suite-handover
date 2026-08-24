(() => {
  const config = window.KelevaStorefront;
  if (!config) return;

  let cartNonce = null;
  let dialog = null;

  const createDialog = () => {
    if (dialog) return dialog;
    dialog = document.createElement('dialog');
    dialog.className = 'keleva-quick-view-dialog';
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
      dialog.innerHTML = '';
    });
    document.body.appendChild(dialog);
    return dialog;
  };

  const fetchCart = async () => {
    const response = await fetch(`${config.storeApiRoot}cart`, { credentials: 'same-origin' });
    cartNonce = response.headers.get('Nonce') || cartNonce;
    if (!response.ok) throw new Error('Cart unavailable');
    return response.json();
  };

  const cartMoney = (amount, totals = {}) => {
    const minorUnit = Number(totals.currency_minor_unit ?? 2);
    const value = Number(amount ?? 0) / (10 ** minorUnit);
    const currency = totals.currency_code || 'EUR';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(value);
  };

  const setCartItemQuantity = async (key, quantity) => {
    if (!cartNonce) await fetchCart();
    const response = await fetch(`${config.storeApiRoot}cart/update-item?key=${encodeURIComponent(key)}&quantity=${encodeURIComponent(quantity)}`, {
      method: 'POST', credentials: 'same-origin', headers: cartNonce ? { Nonce: cartNonce } : {},
    });
    cartNonce = response.headers.get('Nonce') || cartNonce;
    if (!response.ok) throw new Error('Could not update product');
    const cart = await response.json();
    updateCartUI(cart);
    return cart;
  };

  const deleteCartItem = async (key) => {
    if (!cartNonce) await fetchCart();
    const response = await fetch(`${config.storeApiRoot}cart/remove-item?key=${encodeURIComponent(key)}`, {
      method: 'POST', credentials: 'same-origin', headers: cartNonce ? { Nonce: cartNonce } : {},
    });
    cartNonce = response.headers.get('Nonce') || cartNonce;
    if (!response.ok) throw new Error('Could not remove product');
    const cart = await response.json();
    updateCartUI(cart);
    return cart;
  };

  const renderCartLines = (cart) => {
    const roots = document.querySelectorAll('[data-velora-cart-lines]');
    if (!roots.length) return;
    const items = Array.isArray(cart.items) ? cart.items : [];
    roots.forEach((root) => {
      root.replaceChildren();
      if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'velora-cart-rail__empty';
        empty.innerHTML = '<span aria-hidden="true">▢</span><p>Votre panier est prêt à accueillir une bonne idée.</p>';
        root.appendChild(empty);
        return;
      }
      items.forEach((item) => {
        const line = document.createElement('article');
        line.className = 'velora-cart-rail__line';
        const image = document.createElement('img');
        image.src = item.images?.[0]?.thumbnail || item.images?.[0]?.src || '';
        image.alt = '';
        const copy = document.createElement('div');
        copy.className = 'velora-cart-rail__line-copy';
        const heading = document.createElement('div');
        heading.className = 'velora-cart-rail__line-heading';
        const name = document.createElement('strong');
        name.textContent = item.name || '';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.dataset.veloraCartRemove = item.key;
        remove.setAttribute('aria-label', `Retirer ${item.name || 'ce produit'}`);
        remove.textContent = '×';
        heading.append(name, remove);
        const price = document.createElement('span');
        price.textContent = cartMoney(item.prices?.price, cart.totals);
        const quantity = document.createElement('div');
        quantity.className = 'velora-cart-rail__quantity';
        const minus = document.createElement('button');
        minus.type = 'button';
        minus.dataset.veloraCartQuantity = item.key;
        minus.dataset.veloraCartNextQuantity = String(Math.max(0, Number(item.quantity || 1) - 1));
        minus.setAttribute('aria-label', 'Diminuer la quantité');
        minus.textContent = '−';
        const count = document.createElement('span');
        count.textContent = String(item.quantity || 1);
        const plus = document.createElement('button');
        plus.type = 'button';
        plus.dataset.veloraCartQuantity = item.key;
        plus.dataset.veloraCartNextQuantity = String(Number(item.quantity || 1) + 1);
        plus.setAttribute('aria-label', 'Augmenter la quantité');
        plus.textContent = '+';
        quantity.append(minus, count, plus);
        copy.append(heading, price, quantity);
        line.append(image, copy);
        root.appendChild(line);
      });
    });
  };

  let crossSellRequest = 0;
  const renderCartCrossSells = (products) => {
    const root = document.querySelector('[data-keleva-cart-cross-sells]');
    if (!root) return;
    root.replaceChildren();
    if (!products.length) {
      root.hidden = true;
      return;
    }
    const title = document.createElement('h3');
    title.textContent = 'Compléter votre sélection';
    const list = document.createElement('div');
    list.className = 'keleva-cart-cross-sells__list';
    products.forEach((product) => {
      const item = document.createElement('article');
      item.className = 'keleva-cart-cross-sells__item';
      const image = document.createElement('img');
      image.src = product.image || '';
      image.alt = '';
      const copy = document.createElement('div');
      const name = document.createElement('strong');
      name.textContent = product.name || '';
      const price = document.createElement('small');
      price.textContent = product.price || '';
      copy.append(name, price);
      const add = document.createElement('button');
      add.type = 'button';
      add.dataset.kelevaAddProduct = String(product.id);
      add.setAttribute('aria-label', `Ajouter ${product.name || 'ce produit'} au panier`);
      add.textContent = 'Ajouter';
      item.append(image, copy, add);
      list.appendChild(item);
    });
    root.append(title, list);
    root.hidden = false;
  };
  const loadCartCrossSells = async () => {
    if (!config.crossSellsRoot) return;
    const requestId = ++crossSellRequest;
    try {
      const response = await fetch(config.crossSellsRoot, { credentials: 'same-origin' });
      if (!response.ok) throw new Error('Cross sells unavailable');
      const data = await response.json();
      if (requestId === crossSellRequest) renderCartCrossSells(Array.isArray(data.products) ? data.products : []);
    } catch {
      if (requestId === crossSellRequest) renderCartCrossSells([]);
    }
  };

  const updateCartUI = (cart) => {
    const count = Number(cart.items_count ?? 0);
    document.querySelectorAll('[data-keleva-cart-count]').forEach((element) => {
      element.textContent = element.hasAttribute('data-velora-header-cart-count') ? String(count).padStart(2, '0') : String(count);
    });
    document.querySelectorAll('[data-keleva-cart-message]').forEach((message) => {
      message.textContent = count ? `${count} article(s) déjà sélectionné(s).` : 'Votre panier est prêt à accueillir une bonne idée.';
    });
    const totals = cart.totals || {};
    const subtotal = cartMoney(totals.total_items ?? totals.total_price, totals);
    const total = cartMoney(totals.total_price ?? totals.total_items, totals);
    document.querySelectorAll('[data-velora-cart-subtotal]').forEach((element) => { element.textContent = count ? subtotal : '—'; });
    document.querySelectorAll('[data-velora-cart-total]').forEach((element) => { element.textContent = count ? total : '—'; });
    document.querySelectorAll('[data-velora-cart-delivery]').forEach((element) => { element.textContent = count ? 'Calculée au checkout' : '—'; });
    document.querySelectorAll('[data-velora-cart-progress]').forEach((element) => {
      const raw = Number(totals.total_items ?? 0) / (10 ** Number(totals.currency_minor_unit ?? 2));
      element.style.setProperty('--velora-progress', `${Math.min(100, Math.round((raw / 60) * 100))}%`);
    });
    renderCartLines(cart);
    void loadCartCrossSells();
  };

  let drawerTrigger = null;
  const cartDrawer = () => document.querySelector('#keleva-cart-drawer');
  const closeCartDrawer = () => {
    const drawer = cartDrawer();
    if (!drawer) return;
    drawer.setAttribute('aria-hidden', 'true');
    drawer.hidden = true;
    document.body.classList.remove('keleva-cart-drawer-open');
    drawerTrigger?.focus();
  };
  const openCartDrawer = async (trigger = null) => {
    const drawer = cartDrawer();
    if (!drawer) return;
    drawerTrigger = trigger || document.activeElement;
    drawer.hidden = false;
    drawer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('keleva-cart-drawer-open');
    try { updateCartUI(await fetchCart()); } catch { /* Le lien panier WooCommerce reste disponible en secours. */ }
    window.requestAnimationFrame(() => drawer.querySelector('.keleva-cart-drawer__close')?.focus());
  };
  const bindCartDrawer = () => {
    document.querySelectorAll('[data-keleva-cart-trigger]').forEach((trigger) => trigger.addEventListener('click', (event) => {
      event.preventDefault();
      openCartDrawer(trigger);
    }));
    document.querySelectorAll('[data-keleva-cart-close]').forEach((control) => control.addEventListener('click', closeCartDrawer));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !cartDrawer()?.hidden) closeCartDrawer(); });
    const panel = cartDrawer()?.querySelector('.keleva-cart-drawer__panel');
    let swipeStart = null;
    const resetSwipe = () => { swipeStart = null; };
    panel?.addEventListener('pointerdown', (event) => {
      if (event.pointerType !== 'touch' || event.target.closest('a, button, input, select, textarea, label')) return;
      swipeStart = { id: event.pointerId, x: event.clientX, y: event.clientY };
    });
    panel?.addEventListener('pointercancel', resetSwipe);
    panel?.addEventListener('pointerup', (event) => {
      if (!swipeStart || event.pointerId !== swipeStart.id) return;
      const horizontalDistance = event.clientX - swipeStart.x;
      const verticalDistance = event.clientY - swipeStart.y;
      resetSwipe();
      if (horizontalDistance >= 72 && horizontalDistance > Math.abs(verticalDistance) * 1.35) closeCartDrawer();
    });
  };

  const addToCart = async (id, quantity = 1, variation = []) => {
    if (!cartNonce) await fetchCart();
    const response = await fetch(`${config.storeApiRoot}cart/add-item`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        ...(cartNonce ? { Nonce: cartNonce } : {}),
      },
      body: JSON.stringify({ id: Number(id), quantity: Number(quantity), variation }),
    });
    cartNonce = response.headers.get('Nonce') || cartNonce;
    if (!response.ok) throw new Error('Could not add product');
    const cart = await response.json();
    updateCartUI(cart);
    openCartDrawer();
    return cart;
  };

  const addRestaurantToCart = async (id, sauces) => {
    if (!cartNonce) await fetchCart();
    const response = await fetch(`${config.quickViewRoot}products/${encodeURIComponent(id)}/add-to-cart`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        ...(cartNonce ? { Nonce: cartNonce } : {}),
      },
      body: JSON.stringify({ sauces }),
    });
    if (!response.ok) throw new Error('Restaurant cart unavailable');
    const cart = await response.json();
    updateCartUI(cart);
    openCartDrawer();
    return cart;
  };

  const addConfiguredProductToCart = async (id, payload) => {
    if (!cartNonce) await fetchCart();
    const response = await fetch(`${config.quickViewRoot}products/${encodeURIComponent(id)}/add-to-cart`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        ...(cartNonce ? { Nonce: cartNonce } : {}),
      },
      body: JSON.stringify(payload),
    });
    cartNonce = response.headers.get('Nonce') || cartNonce;
    if (!response.ok) {
      let message = 'Impossible d’ajouter cette sélection.';
      try { message = (await response.json()).message || message; } catch { /* Réponse non JSON. */ }
      throw new Error(message);
    }
    const cart = await fetchCart();
    updateCartUI(cart);
    openCartDrawer();
    return cart;
  };

  const variationPayload = (attributes) => Object.entries(attributes)
    .filter(([, value]) => value)
    .map(([attribute, value]) => ({ attribute, value }));

  const variationMatch = (variations, attributes) => variations.find((variation) => (
    Object.entries(variation.attributes || {}).every(([attribute, value]) => attributes[attribute] === value)
  ));

  const bindProductVariationForms = () => {
    document.querySelectorAll('[data-velora-variable-form]').forEach((form) => {
      if (form.dataset.veloraVariationBound === 'true') return;
      form.dataset.veloraVariationBound = 'true';
      let variations = [];
      try { variations = JSON.parse(form.dataset.variations || '[]'); } catch { variations = []; }

      const selects = [...form.querySelectorAll('[data-velora-variation-select]')];
      const variationId = form.querySelector('[data-velora-variation-id]');
      const status = form.querySelector('[data-velora-variation-status]');
      const price = form.querySelector('[data-velora-variation-price]');
      const submit = form.querySelector('[data-velora-variation-submit]');
      const image = form.closest('.velora-single-product')?.querySelector('.keleva-product-gallery__image img');
      const summaryPrice = form.closest('.velora-single-product__summary')?.querySelector('.velora-product-price-line .price');
      let selected = null;
      const optionGroups = bindProductOptionGroups(form);

      const refresh = () => {
        const attributes = Object.fromEntries(selects.map((select) => [select.name, select.value]));
        const complete = selects.every((select) => select.value);
        selected = complete ? variationMatch(variations, attributes) : null;
        if (!selected) {
          variationId.value = '0';
          submit.disabled = true;
          if (price) price.innerHTML = '';
          if (status) {
            status.className = 'velora-variation-form__status';
            status.textContent = complete ? 'Cette combinaison est indisponible.' : 'Choisissez vos options.';
            if (complete) status.classList.add('is-unavailable');
          }
          return;
        }
        variationId.value = String(selected.id);
        submit.disabled = !selected.can_add || Boolean(optionGroups && !optionGroups.isValid());
        if (price) price.innerHTML = selected.price_html || '';
        if (summaryPrice) summaryPrice.innerHTML = selected.price_html || '';
        if (image && selected.image?.src) {
          image.src = selected.image.src;
          image.alt = selected.image.alt || image.alt;
        }
        if (status) {
          status.className = `velora-variation-form__status ${selected.can_add ? 'is-ready' : 'is-unavailable'}`;
          status.textContent = selected.can_add ? 'Option disponible.' : 'Cette option est momentanément indisponible.';
        }
      };

      selects.forEach((select) => select.addEventListener('change', refresh));
      form.querySelectorAll('[data-keleva-product-option]').forEach((choice) => choice.addEventListener('change', refresh));
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selected || !selected.can_add || (optionGroups && !optionGroups.isValid())) return;
        const quantity = Number(form.querySelector('input.qty')?.value || 1);
        submit.disabled = true;
        const previousText = submit.textContent;
        submit.textContent = 'Ajout…';
        try {
          if (optionGroups) {
            await addConfiguredProductToCart(form.dataset.productId, {
              variation_id: selected.id,
              variation: variationPayload(selected.attributes),
              quantity,
              options: optionGroups.values(),
            });
          } else {
            await addToCart(selected.id, quantity, variationPayload(selected.attributes));
          }
          submit.textContent = config.i18n.added;
        } catch {
          submit.textContent = config.i18n.error;
          submit.disabled = false;
        }
        window.setTimeout(() => { submit.textContent = previousText; }, 1600);
      });
      refresh();
    });
  };

  const bindSaucePicker = (root, max) => {
    const choices = [...root.querySelectorAll('[data-keleva-quick-view-sauce]')];
    const status = root.querySelector('[data-keleva-sauce-status]');
    const refresh = () => {
      const selected = choices.filter((choice) => choice.checked).length;
      choices.forEach((choice) => { choice.disabled = !choice.checked && selected >= max; });
      if (status) status.textContent = selected ? `${selected} sauce${selected > 1 ? 's' : ''} sélectionnée${selected > 1 ? 's' : ''} sur ${max}.` : `Choisissez jusqu’à ${max} sauces.`;
    };
    choices.forEach((choice) => choice.addEventListener('change', refresh));
    refresh();
    return () => choices.filter((choice) => choice.checked).map((choice) => choice.value);
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[character]);

  const priceLabel = (value) => {
    const holder = document.createElement('div');
    holder.innerHTML = String(value ?? '');
    return holder.textContent?.trim() || '';
  };

  const bindLiveSearch = () => {
    const form = document.querySelector('[data-keleva-live-search]');
    const input = form?.querySelector('input[type="search"]');
    const results = document.querySelector('#keleva-live-search-results');
    if (!form || !input || !results || !config.productsRoot) return;
    let timer = null;
    const hide = () => { results.hidden = true; results.replaceChildren(); input.setAttribute('aria-expanded', 'false'); };
    const render = (products) => {
      results.replaceChildren();
      if (!products.length) {
        const empty = document.createElement('p');
        empty.className = 'keleva-live-search-results__empty';
        empty.textContent = 'Aucun produit trouvé. Lancez la recherche complète.';
        results.appendChild(empty);
      } else {
        products.forEach((product) => {
          const link = document.createElement('a');
          link.href = product.permalink || `${config.productsRoot}/${product.id}`;
          link.setAttribute('role', 'option');
          const image = document.createElement('img');
          image.src = product.images?.[0]?.thumbnail || product.images?.[0]?.src || '';
          image.alt = '';
          const copy = document.createElement('span');
          copy.textContent = product.name || '';
          const price = document.createElement('small');
          price.textContent = priceLabel(product.prices?.price || product.price_html || '');
          copy.appendChild(price);
          link.append(image, copy);
          results.appendChild(link);
        });
      }
      results.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    };
    input.addEventListener('input', () => {
      const query = input.value.trim();
      window.clearTimeout(timer);
      if (query.length < 2) { hide(); return; }
      timer = window.setTimeout(async () => {
        try {
          const response = await fetch(`${config.productsRoot}?search=${encodeURIComponent(query)}&per_page=5`, { credentials: 'same-origin' });
          if (!response.ok) throw new Error('Search unavailable');
          render(await response.json());
        } catch { hide(); }
      }, 180);
    });
    input.addEventListener('keydown', (event) => { if (event.key === 'Escape') hide(); });
    document.addEventListener('click', (event) => { if (!form.contains(event.target)) hide(); });
  };

  const productOptionsMarkup = (groups) => groups.map((group) => {
    const single = group.display === 'radio' || Number(group.max) === 1;
    const inputType = single ? 'radio' : 'checkbox';
    const required = Boolean(group.required);
    return `<fieldset class="keleva-product-options__group keleva-product-options__group--${escapeHtml(group.display)}" data-keleva-option-group data-option-group-id="${escapeHtml(group.id)}" data-option-max="${Number(group.max) || 1}" data-option-required="${required ? 'true' : 'false'}"><legend>${escapeHtml(group.label)}${required ? ' <em aria-hidden="true">*</em>' : ''}</legend><p class="keleva-product-options__hint">${single ? 'Choisissez une option.' : `Jusqu’à ${Number(group.max) || 1} options.`}</p><div class="keleva-product-options__choices">${(group.options || []).map((option) => `<label class="keleva-product-options__option"><input type="${inputType}" name="keleva_option_${escapeHtml(group.id)}" value="${escapeHtml(option.id)}" data-keleva-product-option><span>${escapeHtml(option.label)}</span><small>${escapeHtml(priceLabel(option.price_html))}</small></label>`).join('')}</div><p class="keleva-product-options__status" data-keleva-option-status aria-live="polite"></p></fieldset>`;
  }).join('');

  const bindProductOptionGroups = (root) => {
    const groups = [...root.querySelectorAll('[data-keleva-option-group]')];
    if (!groups.length) return null;
    const refreshGroup = (group) => {
      const choices = [...group.querySelectorAll('[data-keleva-product-option]')];
      const selected = choices.filter((choice) => choice.checked);
      const max = Math.max(1, Number(group.dataset.optionMax || 1));
      const required = group.dataset.optionRequired === 'true';
      choices.forEach((choice) => { choice.disabled = !choice.checked && selected.length >= max; });
      const status = group.querySelector('[data-keleva-option-status]');
      if (!status) return;
      status.className = 'keleva-product-options__status';
      if (required && !selected.length) {
        status.textContent = 'Ce choix est requis.';
        status.classList.add('is-error');
      } else if (selected.length) {
        status.textContent = max === 1 ? 'Option sélectionnée.' : `${selected.length} option${selected.length > 1 ? 's' : ''} sélectionnée${selected.length > 1 ? 's' : ''} sur ${max}.`;
      } else {
        status.textContent = max === 1 ? 'Choisissez une option.' : `Jusqu’à ${max} options.`;
      }
    };
    const refresh = () => groups.forEach(refreshGroup);
    groups.forEach((group) => group.querySelectorAll('[data-keleva-product-option]').forEach((choice) => choice.addEventListener('change', refresh)));
    refresh();
    return {
      isValid: () => groups.every((group) => group.dataset.optionRequired !== 'true' || group.querySelector('[data-keleva-product-option]:checked')),
      values: () => Object.fromEntries(groups.map((group) => [
        group.dataset.optionGroupId,
        [...group.querySelectorAll('[data-keleva-product-option]:checked')].map((choice) => choice.value),
      ])),
      refresh,
    };
  };

  const quickViewCache = new Map();
  const prefetchQuickView = (id) => {
    if (!id) return Promise.resolve(null);
    if (quickViewCache.has(id)) return Promise.resolve(quickViewCache.get(id));
    return fetch(`${config.quickViewRoot}products/${encodeURIComponent(id)}`, { credentials: 'same-origin' })
      .then((response) => response.ok ? response.json() : Promise.reject(new Error('Product unavailable')))
      .then((product) => {
        quickViewCache.set(id, product);
        return product;
      })
      .catch(() => {
        quickViewCache.delete(id);
        return null;
      });
  };

  const openQuickView = async (id, trigger) => {
    const panel = createDialog();
    panel.innerHTML = `<div class="keleva-quick-view"><div class="keleva-quick-view__content"><button class="keleva-quick-view__close" type="button" aria-label="Fermer">×</button><p>${config.i18n.loading}</p></div></div>`;
    panel.showModal();
    panel.querySelector('.keleva-quick-view__close')?.addEventListener('click', () => panel.close());
    try {
      let product = quickViewCache.get(id);
      if (!product) {
        const response = await fetch(`${config.quickViewRoot}products/${encodeURIComponent(id)}`, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('Product unavailable');
        product = await response.json();
        quickViewCache.set(id, product);
      }
      const veloraDetails = {
        'Mug Nomade Sienna': ['Acier inoxydable double paroi', '450 ml · garde 6 h au chaud', 'Couvercle anti-éclaboussure'],
        'Pochette Field Olive': ['Cuir tannage végétal', 'Fermeture en laiton brossé', 'Format passeport et câbles'],
        'Vase Forme 02': ['Céramique tournée à la main', '18 cm de hauteur', 'Chaque pièce est légèrement unique'],
        'Lampe Halo Portable': ['Gradation tactile', 'Autonomie jusqu’à 18 h', 'Recharge USB-C'],
        'Carnet Ligne Claire': ['96 pages papier ivoire', 'Reliure cousue', 'Format A5'],
        'Tote Canvas 03': ['Toile de coton épaisse', 'Poche intérieure', 'Anses renforcées'],
        'Plateau Ondulation': ['Bois de noyer huilé', 'Bords adoucis', '34 × 20 cm'],
        'Duo Pause Juste': ['Mug Nomade Sienna', 'Carnet Ligne Claire', 'Étui cadeau inclus'],
      };
      const responsiveSources = `${product.image.avif ? `<source type="image/avif" srcset="${product.image.avif}">` : ''}${product.image.webp ? `<source type="image/webp" srcset="${product.image.webp}">` : ''}`;
      const imageMarkup = responsiveSources ? `<picture>${responsiveSources}<img src="${product.image.src}" alt="${product.image.alt}"></picture>` : `<img src="${product.image.src}" alt="${product.image.alt}">`;
      const hasSauces = Array.isArray(product.sauces) && product.sauces.length > 0;
      const hasProductOptions = Array.isArray(product.option_groups) && product.option_groups.length > 0;
      const isVariable = product.type === 'variable' && Array.isArray(product.attributes) && product.attributes.length > 0;
      const sauceMarkup = hasSauces ? `<fieldset class="keleva-quick-view__sauces" data-keleva-quick-view-sauces><legend>Choisissez vos sauces</legend><p>Jusqu’à ${product.max_sauces} sauces. Vous pouvez tout faire ici, sans quitter le catalogue.</p><p class="keleva-quick-view__sauce-status" data-keleva-sauce-status aria-live="polite"></p>${product.sauces.map((sauce) => `<label><input type="checkbox" value="${sauce.id}" data-keleva-quick-view-sauce><span>${sauce.label}</span><small>${sauce.price_html}</small></label>`).join('')}</fieldset>` : '';
      const optionGroupsMarkup = hasProductOptions ? `<section class="keleva-product-options keleva-quick-view__product-options" data-keleva-product-options>${productOptionsMarkup(product.option_groups)}</section>` : '';
      const variationMarkup = isVariable ? `<div class="keleva-quick-view__variations" data-velora-quick-variations>${product.attributes.map((attribute) => `<label class="velora-quick-view__variation-field"><span>${attribute.label}</span><select data-velora-quick-attribute="${attribute.key}"><option value="">Choisir</option>${attribute.options.map((option) => `<option value="${option.value}">${option.label}</option>`).join('')}</select></label>`).join('')}<p class="velora-quick-view__variation-status" data-velora-quick-variation-status aria-live="polite">Choisissez vos options.</p></div>` : '';
      const detailsMarkup = !hasSauces ? `<div class="velora-quick-view__rating">✦ Sélection éditoriale</div><p class="keleva-quick-view__description">${product.short_description}</p><ul class="velora-quick-view__details">${(veloraDetails[product.name] || []).map((detail) => `<li><span aria-hidden="true">✓</span>${detail}</li>`).join('')}</ul><div class="velora-quick-view__divider"></div>` : '';
      const quantityMarkup = !hasSauces ? `<div class="velora-quick-view__buy-row"><div><span>Prix</span><strong class="keleva-quick-view__price${isVariable ? ' keleva-quick-view__variation-price' : ''}" ${isVariable ? 'data-velora-quick-variation-price' : ''}>${isVariable ? '—' : product.price_html}</strong></div><div class="keleva-quick-view__quantity" aria-label="Quantité"><button type="button" data-velora-quick-quantity="-1" aria-label="Diminuer la quantité">−</button><b data-velora-quick-quantity-value>1</b><button type="button" data-velora-quick-quantity="1" aria-label="Augmenter la quantité">+</button></div></div>` : '';
      const canAdd = isVariable ? product.variations.some((variation) => variation.can_add) : product.can_add;
      const directAdd = hasSauces || hasProductOptions
        ? `<button type="button" data-keleva-add-configured-product="${product.id}" ${canAdd && !isVariable ? '' : 'disabled'}>${canAdd ? 'Ajouter ma sélection' : 'Indisponible'}</button>`
        : `<button type="button" ${isVariable ? 'data-velora-add-variable' : `data-keleva-add-product="${product.id}"`} ${canAdd && !isVariable ? '' : 'disabled'}>${canAdd ? 'Ajouter au panier' : 'Indisponible'}</button>`;
      const buyNow = `<button class="keleva-quick-view__buy-now" type="button" data-keleva-buy-now ${canAdd && !isVariable ? '' : 'disabled'}>${canAdd ? 'Acheter maintenant' : 'Indisponible'}</button>`;
      panel.innerHTML = `<div class="keleva-quick-view"><div class="keleva-quick-view__image">${imageMarkup}</div><div class="keleva-quick-view__content"><button class="keleva-quick-view__close" type="button" aria-label="Fermer">×</button><p class="keleva-eyebrow">${product.category} · sélection Velora</p><h2>${product.name}</h2>${detailsMarkup}${sauceMarkup}${variationMarkup}${optionGroupsMarkup}${quantityMarkup}<div class="keleva-quick-view__form">${directAdd}${buyNow}<a class="keleva-quick-view__detail-link" href="${product.permalink}">Voir le détail</a></div><p class="velora-quick-view__secure">◈ Paiement protégé · retours simples</p></div></div>`;
      panel.querySelector('.keleva-quick-view__close')?.addEventListener('click', () => panel.close());
      const selectedSauces = hasSauces ? bindSaucePicker(panel, Number(product.max_sauces) || 2) : null;
      let quickViewQuantity = 1;
      let selectedVariation = null;
      const variationSelects = [...panel.querySelectorAll('[data-velora-quick-attribute]')];
      const variationStatus = panel.querySelector('[data-velora-quick-variation-status]');
      const variationPrice = panel.querySelector('[data-velora-quick-variation-price]');
      const variableButton = panel.querySelector('[data-velora-add-variable]');
      const configuredButton = panel.querySelector('[data-keleva-add-configured-product]');
      const buyNowButton = panel.querySelector('[data-keleva-buy-now]');
      const optionGroups = bindProductOptionGroups(panel);
      const syncQuickVariation = () => {
        if (!isVariable) return;
        const attributes = Object.fromEntries(variationSelects.map((select) => [select.dataset.veloraQuickAttribute, select.value]));
        const complete = variationSelects.every((select) => select.value);
        selectedVariation = complete ? variationMatch(product.variations, attributes) : null;
        if (!selectedVariation) {
          if (variationStatus) {
            variationStatus.className = 'velora-quick-view__variation-status';
            variationStatus.textContent = complete ? 'Cette combinaison est indisponible.' : 'Choisissez vos options.';
            if (complete) variationStatus.classList.add('is-unavailable');
          }
          if (variationPrice) variationPrice.innerHTML = '—';
          if (variableButton) variableButton.disabled = true;
          if (configuredButton) configuredButton.disabled = true;
          if (buyNowButton) buyNowButton.disabled = true;
          return;
        }
        if (variationStatus) {
          variationStatus.className = `velora-quick-view__variation-status ${selectedVariation.can_add ? 'is-ready' : 'is-unavailable'}`;
          variationStatus.textContent = selectedVariation.can_add ? 'Option disponible.' : 'Cette option est momentanément indisponible.';
        }
        if (variationPrice) variationPrice.innerHTML = selectedVariation.price_html || '';
        if (variableButton) variableButton.disabled = !selectedVariation.can_add;
        if (configuredButton) configuredButton.disabled = !selectedVariation.can_add || Boolean(optionGroups && !optionGroups.isValid());
        if (buyNowButton) buyNowButton.disabled = !selectedVariation.can_add || Boolean(optionGroups && !optionGroups.isValid());
        const image = panel.querySelector('.keleva-quick-view__image img');
        if (image && selectedVariation.image?.src) {
          image.src = selectedVariation.image.src;
          image.alt = selectedVariation.image.alt || image.alt;
        }
      };
      variationSelects.forEach((select) => select.addEventListener('change', syncQuickVariation));
      panel.querySelectorAll('[data-keleva-product-option]').forEach((choice) => choice.addEventListener('change', () => {
        if (isVariable) syncQuickVariation();
        else if (configuredButton) configuredButton.disabled = !product.can_add || Boolean(optionGroups && !optionGroups.isValid());
      }));
      syncQuickVariation();
      if (!isVariable && configuredButton) configuredButton.disabled = !product.can_add || Boolean(optionGroups && !optionGroups.isValid());
      if (!isVariable && buyNowButton) buyNowButton.disabled = !product.can_add || Boolean(optionGroups && !optionGroups.isValid());
      panel.querySelectorAll('[data-velora-quick-quantity]').forEach((button) => {
        button.addEventListener('click', () => {
          quickViewQuantity = Math.max(1, quickViewQuantity + Number(button.dataset.veloraQuickQuantity || 0));
          const value = panel.querySelector('[data-velora-quick-quantity-value]');
          if (value) value.textContent = String(quickViewQuantity);
        });
      });
      panel.querySelector('[data-keleva-add-product]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        try {
          await addToCart(product.id, quickViewQuantity);
          button.textContent = config.i18n.added;
        } catch (error) {
          button.textContent = config.i18n.error;
          button.disabled = false;
        }
      });
      variableButton?.addEventListener('click', async (event) => {
        if (!selectedVariation || !selectedVariation.can_add) return;
        const button = event.currentTarget;
        button.disabled = true;
        try {
          await addToCart(selectedVariation.id, quickViewQuantity, variationPayload(selectedVariation.attributes));
          button.textContent = config.i18n.added;
        } catch (error) {
          button.textContent = config.i18n.error;
          button.disabled = false;
        }
      });
      panel.querySelector('[data-keleva-add-configured-product]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (optionGroups && !optionGroups.isValid()) {
          optionGroups.refresh();
          return;
        }
        const sauces = selectedSauces ? selectedSauces() : [];
        if (hasSauces && !sauces.length) {
          const status = panel.querySelector('[data-keleva-sauce-status]');
          if (status) status.textContent = 'Choisissez au moins une sauce pour continuer.';
          return;
        }
        button.disabled = true;
        try {
          if (isVariable && (!selectedVariation || !selectedVariation.can_add)) {
            button.disabled = false;
            return;
          }
          await addConfiguredProductToCart(product.id, {
            variation_id: selectedVariation?.id || 0,
            variation: selectedVariation ? variationPayload(selectedVariation.attributes) : [],
            quantity: quickViewQuantity,
            sauces,
            options: optionGroups ? optionGroups.values() : {},
          });
          button.textContent = config.i18n.added;
        } catch (error) {
          button.textContent = config.i18n.error;
          button.disabled = false;
        }
      });
      buyNowButton?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (optionGroups && !optionGroups.isValid()) { optionGroups.refresh(); return; }
        button.disabled = true;
        try {
          const variation = selectedVariation ? variationPayload(selectedVariation.attributes) : [];
          if (hasSauces || hasProductOptions) {
            const sauces = selectedSauces ? selectedSauces() : [];
            if (hasSauces && !sauces.length) throw new Error('Choisissez au moins une sauce pour continuer.');
            await addConfiguredProductToCart(product.id, { variation_id: selectedVariation?.id || 0, variation, quantity: quickViewQuantity, sauces, options: optionGroups ? optionGroups.values() : {} });
          } else if (selectedVariation) {
            await addToCart(selectedVariation.id, quickViewQuantity, variation);
          } else {
            await addToCart(product.id, quickViewQuantity);
          }
          window.location.assign(config.checkoutUrl);
        } catch (error) { button.textContent = error.message || config.i18n.error; button.disabled = false; }
      });
    } catch (error) {
      panel.innerHTML = `<div class="keleva-quick-view"><div class="keleva-quick-view__content"><button class="keleva-quick-view__close" type="button" aria-label="Fermer">×</button><p>${config.i18n.error}</p></div></div>`;
      panel.querySelector('.keleva-quick-view__close')?.addEventListener('click', () => panel.close());
    }
  };

  document.addEventListener('click', (event) => {
    const removeLine = event.target.closest('[data-velora-cart-remove]');
    if (removeLine) {
      event.preventDefault();
      deleteCartItem(removeLine.dataset.veloraCartRemove).catch(() => { window.location.href = config.cartUrl; });
      return;
    }

    const changeQuantity = event.target.closest('[data-velora-cart-quantity]');
    if (changeQuantity) {
      event.preventDefault();
      const next = Number(changeQuantity.dataset.veloraCartNextQuantity || 0);
      if (next <= 0) deleteCartItem(changeQuantity.dataset.veloraCartQuantity).catch(() => { window.location.href = config.cartUrl; });
      else setCartItemQuantity(changeQuantity.dataset.veloraCartQuantity, next).catch(() => { window.location.href = config.cartUrl; });
      return;
    }

    const trigger = event.target.closest('[data-keleva-quick-view]');
    if (trigger) {
      event.preventDefault();
      openQuickView(trigger.dataset.productId, trigger);
      return;
    }

    const cartTrigger = event.target.closest('[data-keleva-cart-trigger]');
    if (cartTrigger) return;

    const addButton = event.target.closest('[data-keleva-add-product]');
    if (!addButton) return;
    event.preventDefault();
    if (addButton.dataset.loading === 'true') return;
    addButton.dataset.loading = 'true';
    const previousText = addButton.textContent;
    addButton.textContent = 'Ajout…';
    addToCart(addButton.dataset.kelevaAddProduct).then(() => {
      addButton.textContent = config.i18n.added;
      window.setTimeout(() => { addButton.textContent = previousText; }, 1400);
    }).catch(() => {
      addButton.textContent = config.i18n.error;
      window.setTimeout(() => { addButton.textContent = previousText; }, 1800);
    }).finally(() => {
      delete addButton.dataset.loading;
    });
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
    const destination = new URL(link.href, window.location.href);
    if (destination.origin === window.location.origin && destination.pathname !== window.location.pathname && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) document.body.classList.add('keleva-is-navigating');
  }, { capture: true });

  fetchCart().then(updateCartUI).catch(() => {});
  bindCartDrawer();
  bindLiveSearch();
  const quickViewTriggers = [...document.querySelectorAll('[data-keleva-quick-view]')];
  quickViewTriggers.forEach((trigger) => {
    const preload = () => prefetchQuickView(trigger.dataset.productId).then((product) => {
      if (product) trigger.dataset.kelevaQuickPrefetched = 'true';
    });
    trigger.addEventListener('pointerenter', preload, { once: true });
    trigger.addEventListener('focus', preload, { once: true });
  });
  const isMobileQuickViewContext = window.matchMedia('(max-width: 800px), (pointer: coarse)').matches;
  if (isMobileQuickViewContext && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const trigger = entry.target;
        prefetchQuickView(trigger.dataset.productId).then((product) => {
          if (product) trigger.dataset.kelevaQuickPrefetched = 'true';
        });
        observer.unobserve(trigger);
      });
    }, { rootMargin: '240px 0px', threshold: 0.01 });
    quickViewTriggers.forEach((trigger) => observer.observe(trigger));
  }
  document.querySelectorAll('[data-keleva-product-options]').forEach((root) => bindProductOptionGroups(root));
  bindProductVariationForms();
  document.querySelectorAll('.woocommerce-product-gallery').forEach((gallery) => {
    const main = gallery.querySelector('[data-keleva-gallery-main]');
    const image = main?.querySelector('img');
    const link = main?.querySelector('a');
    if (!main || !image || !link) return;
    gallery.querySelectorAll('[data-keleva-gallery-image]').forEach((button) => button.addEventListener('click', () => {
      image.src = button.dataset.src || image.src;
      image.alt = button.dataset.alt || image.alt;
      link.href = button.dataset.src || link.href;
      main.dataset.largeImage = link.href;
      const picture = image.closest('picture');
      if (picture) {
        const avif = picture.querySelector('source[type="image/avif"]');
        const webp = picture.querySelector('source[type="image/webp"]');
        if (avif && button.dataset.avif) avif.srcset = button.dataset.avif;
        if (webp && button.dataset.webp) webp.srcset = button.dataset.webp;
      }
      gallery.querySelectorAll('[data-keleva-gallery-image]').forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
    }));
  });

})();
