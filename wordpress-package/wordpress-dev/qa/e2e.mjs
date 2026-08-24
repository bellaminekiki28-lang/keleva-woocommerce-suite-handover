import { chromium, firefox, webkit } from 'playwright';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const baseUrl = process.env.KELEVA_LOCAL_BASE_URL || 'http://127.0.0.1:8088';
const outputDir = resolve(process.env.KELEVA_QA_OUTPUT_DIR || '../proofs/qa');
const axeSource = await readFile(resolve('node_modules/axe-core/axe.min.js'), 'utf8');
const engines = { chromium, firefox, webkit };
const report = { baseUrl, generatedAt: new Date().toISOString(), engines: {}, axe: null };

await mkdir(outputDir, { recursive: true });

async function validateStorefront(name, browserType) {
  const browser = await browserType.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
  const page = await context.newPage();
  const browserErrors = [];
  page.on('pageerror', (error) => browserErrors.push(error.message));
  await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 45_000 });
  const search = page.locator('#keleva-product-search');
  await search.fill('Vase');
  await page.locator('#keleva-live-search-results:not([hidden])').waitFor({ timeout: 10_000 });
  await page.locator('#keleva-live-search-results a').first().waitFor({ timeout: 10_000 });
  await page.locator('.site-cart[data-keleva-cart-trigger]').click();
  await page.locator('#keleva-cart-drawer[aria-hidden="false"]').waitFor({ timeout: 10_000 });
  await page.screenshot({ path: resolve(outputDir, `cart-drawer-${name}.png`), fullPage: false });
  await page.keyboard.press('Escape');
  await page.locator('#keleva-cart-drawer').waitFor({ state: 'hidden', timeout: 5_000 });
  const mobileContext = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
  const mobilePage = await mobileContext.newPage();
  await mobilePage.goto(baseUrl, { waitUntil: 'networkidle', timeout: 45_000 });
  const mobileTrigger = mobilePage.locator('.site-cart[data-keleva-cart-trigger]');
  await mobileTrigger.click();
  const mobileDrawer = mobilePage.locator('#keleva-cart-drawer');
  await mobileDrawer.locator('.keleva-cart-drawer__panel').dispatchEvent('pointerdown', { pointerId: 71, pointerType: 'touch', clientX: 72, clientY: 360 });
  await mobileDrawer.locator('.keleva-cart-drawer__panel').dispatchEvent('pointerup', { pointerId: 71, pointerType: 'touch', clientX: 184, clientY: 366 });
  await mobileDrawer.waitFor({ state: 'hidden', timeout: 5_000 });
  const swipeFocusRestored = await mobileTrigger.evaluate((trigger) => document.activeElement === trigger);
  if (!swipeFocusRestored) throw new Error('La fermeture par balayage ne restitue pas le focus au déclencheur du tiroir.');
  await mobileContext.close();
  const quickViewPrefetchContext = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
  const quickViewPrefetchPage = await quickViewPrefetchContext.newPage();
  const mobilePrefetchRequests = [];
  quickViewPrefetchPage.on('request', (request) => {
    if (/\/wp-json\/keleva\/v1\/products\/\d+/.test(request.url())) mobilePrefetchRequests.push(request.url());
  });
  await quickViewPrefetchPage.goto(`${baseUrl}/shop/`, { waitUntil: 'networkidle', timeout: 45_000 });
  const mobileQuickViewTrigger = quickViewPrefetchPage.locator('[data-keleva-quick-view]').first();
  await mobileQuickViewTrigger.scrollIntoViewIfNeeded();
  await mobileQuickViewTrigger.waitFor({ timeout: 10_000 });
  await quickViewPrefetchPage.locator('[data-keleva-quick-view][data-keleva-quick-prefetched="true"]').first().waitFor({ timeout: 15_000 });
  await quickViewPrefetchPage.waitForFunction(() => performance.getEntriesByType('resource').some((entry) => /\/wp-json\/keleva\/v1\/products\/\d+/.test(entry.name)));
  if (!mobilePrefetchRequests.length) throw new Error('Le préchargement quick view mobile ne déclenche aucune requête produit.');
  await quickViewPrefetchContext.close();
  const filtersPage = await context.newPage();
  await filtersPage.goto(`${baseUrl}/shop/?keleva_probe=filters-${name}`, { waitUntil: 'networkidle', timeout: 45_000 });
  const filtersForm = filtersPage.locator('form.keleva-product-filters');
  await filtersForm.waitFor({ timeout: 10_000 });
  await filtersForm.locator('select[name="keleva_category"]').selectOption('maison');
  await filtersForm.getByRole('button', { name: 'Appliquer' }).click();
  await filtersPage.waitForURL(/keleva_category=maison/, { timeout: 10_000 });
  await filtersPage.getByRole('link', { name: 'Lampe Halo 01', exact: true }).waitFor({ timeout: 10_000 });
  await filtersPage.getByRole('link', { name: 'Bowl Signature 05', exact: true }).waitFor({ state: 'hidden', timeout: 10_000 });
  await filtersForm.getByRole('link', { name: 'Réinitialiser' }).click();
  await filtersPage.waitForURL((url) => !url.searchParams.has('keleva_category'), { timeout: 10_000 });
  if (await filtersPage.locator('select[name="keleva_category"]').inputValue()) throw new Error('La réinitialisation ne vide pas la catégorie sélectionnée.');
  await filtersPage.locator('input[name="keleva_stock"]').check();
  await filtersForm.getByRole('button', { name: 'Appliquer' }).click();
  await filtersPage.waitForURL(/keleva_stock=instock/, { timeout: 10_000 });
  await filtersPage.getByRole('link', { name: 'Produit stock épuisé QA', exact: true }).waitFor({ state: 'hidden', timeout: 10_000 });
  await filtersPage.locator('select[name="keleva_category"]').selectOption('rupture-qa');
  await filtersForm.getByRole('button', { name: 'Appliquer' }).click();
  await filtersPage.waitForURL(/keleva_category=rupture-qa/, { timeout: 10_000 });
  await filtersPage.getByText('Aucun produit à afficher.', { exact: true }).waitFor({ timeout: 10_000 });
  await filtersPage.close();
  const wishlistPage = await context.newPage();
  await wishlistPage.goto(`${baseUrl}/shop/`, { waitUntil: 'networkidle', timeout: 45_000 });
  const wishlistForm = wishlistPage.locator('form.keleva-product-card__favorite-toggle').first();
  const wishlistButton = wishlistForm.getByRole('button');
  const wishlistProductId = await wishlistForm.locator('input[name="keleva_product_id"]').inputValue();
  if (await wishlistButton.getAttribute('data-velora-favorite-toggle')) throw new Error('Le favori catalogue conserve un gestionnaire décoratif JavaScript.');
  await Promise.all([
    wishlistPage.waitForNavigation({ waitUntil: 'networkidle', timeout: 15_000 }),
    wishlistButton.click(),
  ]);
  await wishlistPage.goto(`${baseUrl}/shop/`, { waitUntil: 'networkidle', timeout: 45_000 });
  const persistedWishlistButton = wishlistPage.locator(`form.keleva-product-card__favorite-toggle:has(input[name="keleva_product_id"][value="${wishlistProductId}"])`).getByRole('button');
  if ((await persistedWishlistButton.getAttribute('aria-pressed')) !== 'true') {
    throw new Error('Le favori ne persiste pas dans la session WooCommerce après son formulaire POST.');
  }
  await wishlistPage.close();
  let crossSell = 'not-run';
  if (process.env.KELEVA_TEST_CROSS_SELL) {
    const crossSellPage = await context.newPage();
    await crossSellPage.goto(`${baseUrl}/shop/`, { waitUntil: 'networkidle', timeout: 45_000 });
    await crossSellPage.locator('[data-keleva-add-product="11"]').click();
    const crossSellRoot = crossSellPage.locator('[data-keleva-cart-cross-sells]:not([hidden])');
    await crossSellRoot.waitFor({ timeout: 15_000 });
    await crossSellRoot.getByRole('heading', { name: 'Compléter votre sélection' }).waitFor({ timeout: 15_000 });
    const recommendation = crossSellRoot.locator('[data-keleva-add-product="12"]');
    if ((await recommendation.count()) !== 1) throw new Error('Le tiroir ne rend pas le produit réellement associé en cross-sell WooCommerce.');
    await recommendation.click();
    await crossSellPage.waitForFunction(() => Number(document.querySelector('[data-velora-header-cart-count]')?.textContent || 0) >= 2);
    crossSell = 'ok';
    await crossSellPage.close();
  }
  const galleryPage = await context.newPage();
  await galleryPage.goto(`${baseUrl}/product/vase-forme-02/`, { waitUntil: 'networkidle', timeout: 45_000 });
  const galleryThumbs = galleryPage.locator('[data-keleva-gallery-image]');
  if ((await galleryThumbs.count()) < 3) throw new Error('La galerie réelle du Vase doit présenter au moins trois miniatures.');
  const initialGallerySource = await galleryPage.locator('[data-keleva-gallery-main] img').getAttribute('src');
  await galleryThumbs.nth(1).click();
  await galleryPage.waitForFunction((initialSource) => document.querySelector('[data-keleva-gallery-main] img')?.getAttribute('src') !== initialSource, initialGallerySource);
  if ((await galleryThumbs.nth(1).getAttribute('aria-pressed')) !== 'true') throw new Error('La miniature de galerie sélectionnée ne reflète pas son état accessible.');
  await galleryPage.screenshot({ path: resolve(outputDir, `product-gallery-${name}.png`), fullPage: false });
  await galleryPage.close();
  const trigger = page.locator('[data-keleva-quick-view]').first();
  await trigger.hover();
  await trigger.click();
  await page.locator('dialog.keleva-quick-view-dialog[open]').waitFor({ timeout: 10_000 });
  const buyNow = page.locator('[data-keleva-buy-now]');
  await buyNow.waitFor({ timeout: 10_000 });
  await page.screenshot({ path: resolve(outputDir, `quick-view-${name}.png`), fullPage: false });
  let buyNowResult = 'not-run';
  if (name === 'chromium') {
    const quickVariationSelects = page.locator('[data-velora-quick-attribute]');
    for (let index = 0; index < await quickVariationSelects.count(); index += 1) {
      await quickVariationSelects.nth(index).selectOption({ index: 1 });
    }
    const requiredGroups = page.locator('[data-keleva-option-group][data-option-required="true"]');
    for (let index = 0; index < await requiredGroups.count(); index += 1) {
      await requiredGroups.nth(index).locator('[data-keleva-product-option]').first().check();
    }
    if (await buyNow.isDisabled()) throw new Error('Acheter maintenant reste désactivé après une variante et les choix requis.');
    await buyNow.click();
    await page.waitForURL(/\/checkout\/?/, { timeout: 15_000 });
    await page.locator('.keleva-checkout-intro').waitFor({ timeout: 15_000 });
    buyNowResult = 'checkout';
  } else {
    await page.locator('.keleva-quick-view__close').click();
    await page.locator('dialog.keleva-quick-view-dialog').waitFor({ state: 'hidden', timeout: 5_000 });
  }
  report.engines[name] = { search: 'ok', drawer: 'ok', drawerSwipe: 'ok', filters: 'ok', wishlist: 'session-post', crossSell, mobileQuickViewPrefetch: 'ok', gallery: 'ok', quickView: 'ok', buyNow: buyNowResult, browserErrors };
  await browser.close();
}

for (const [name, browserType] of Object.entries(engines)) {
  await validateStorefront(name, browserType);
}

if (process.env.KELEVA_LOCAL_MERCHANT_EMAIL && process.env.KELEVA_LOCAL_MERCHANT_PASSWORD) {
  const merchantBrowser = await chromium.launch({ headless: true });
  const merchantPage = await merchantBrowser.newPage({ viewport: { width: 1280, height: 900 } });
  const merchantErrors = [];
  merchantPage.on('pageerror', (error) => merchantErrors.push(error.message));
  await merchantPage.goto(`${baseUrl}/keleva-merchant/`, { waitUntil: 'networkidle', timeout: 45_000 });
  await merchantPage.locator('#mk-email').fill(process.env.KELEVA_LOCAL_MERCHANT_EMAIL);
  await merchantPage.locator('#mk-key').fill(process.env.KELEVA_LOCAL_MERCHANT_PASSWORD);
  let skeleton = 'not-run';
  if (process.env.KELEVA_TEST_SKELETON) {
    let delayedSummary = false;
    await merchantPage.route('**/wp-json/keleva-dashboard/v1/summary?**', async (route) => {
      if (!delayedSummary) {
        delayedSummary = true;
        await new Promise((resolve) => setTimeout(resolve, 650));
      }
      await route.continue();
    });
  }
  await merchantPage.locator('#mk-connect').click();
  if (process.env.KELEVA_TEST_SKELETON) {
    await merchantPage.locator('#mk-skeleton-overlay [role="status"]').waitFor({ timeout: 10_000 });
    if ((await merchantPage.locator('#mk-app').getAttribute('aria-busy')) !== 'true') throw new Error('Le skeleton console ne signale pas son état occupé.');
    await merchantPage.locator('#mk-skeleton-overlay').waitFor({ state: 'hidden', timeout: 15_000 });
    if (await merchantPage.locator('#mk-app').getAttribute('aria-busy')) throw new Error('Le skeleton console ne libère pas l’état occupé après chargement.');
    await merchantPage.unroute('**/wp-json/keleva-dashboard/v1/summary?**');
    skeleton = 'ok';
  }
  await merchantPage.locator('#mk-app').waitFor({ state: 'visible', timeout: 15_000 });
  let catalogPagination = 'not-run';
  if (process.env.KELEVA_TEST_CATALOG_PAGINATION) {
    await merchantPage.locator('#mk-catalog-next').waitFor({ timeout: 15_000 });
    await merchantPage.locator('#mk-catalog-next').click();
    await merchantPage.getByText('Page 2 /').waitFor({ timeout: 15_000 });
    await merchantPage.locator('#mk-search').fill('Vase Forme 02');
    await merchantPage.locator('.mk-product[data-product="11"]').waitFor({ timeout: 15_000 });
    catalogPagination = 'ok';
  }
  await merchantPage.locator('.mk-product[data-product="11"]').click();
  await merchantPage.locator('#mk-open-config').click();
  await merchantPage.locator('.mk-client-preview').first().waitFor({ timeout: 15_000 });
  await merchantPage.locator('.mk-client-preview').filter({ hasText: 'Inclus dans le prix' }).first().waitFor({ timeout: 15_000 });
  const firstOptionLimit = merchantPage.locator('[data-gm]').first();
  await firstOptionLimit.fill('2');
  await firstOptionLimit.dispatchEvent('input');
  const forcedCheckbox = merchantPage.locator('[data-gd]').first();
  if (!(await forcedCheckbox.isDisabled()) || (await forcedCheckbox.inputValue()) !== 'checkbox') {
    throw new Error('Le type de choix n’est pas imposé à « cases à cocher » lorsque la limite dépasse un.');
  }
  await merchantPage.getByText('Jusqu’à 2 choix : les cases à cocher sont imposées pour éviter toute ambiguïté.').first().waitFor({ timeout: 15_000 });
  await merchantPage.screenshot({ path: resolve(outputDir, 'merchant-options-client-preview-chromium.png'), fullPage: false });
  await merchantPage.locator('#mk-back-product').click();
  await merchantPage.locator('#mk-unpublish, #mk-publish').click();
  await merchantPage.locator('.mk-confirm-scrim [role="dialog"]').waitFor({ timeout: 15_000 });
  await merchantPage.getByRole('button', { name: 'Annuler' }).click();
  await merchantPage.locator('.mk-confirm-scrim').waitFor({ state: 'hidden', timeout: 15_000 });
  await merchantPage.locator('#mk-close-product').click();
  await merchantPage.locator('#mk-appearance-entry').click();
  await merchantPage.locator('[data-appearance-palette]').first().waitFor({ timeout: 15_000 });
  if ((await merchantPage.locator('[data-appearance-palette]').count()) !== 5) throw new Error('Les cinq palettes attendues ne sont pas rendues.');
  const onyxPalette = merchantPage.locator('[data-appearance-palette="onyx-gold"]');
  await onyxPalette.hover();
  const storefrontPreview = merchantPage.locator('#mk-storefront-preview');
  await storefrontPreview.waitFor({ timeout: 15_000 });
  const previewSrc = await storefrontPreview.getAttribute('src');
  if (!previewSrc?.includes('keleva_palette_preview=onyx-gold')) throw new Error(`Prévisualisation storefront incorrecte : ${previewSrc || 'absente'}`);
  if ((await merchantPage.locator('[data-appearance-palette="velora"]').getAttribute('aria-pressed')) !== 'true') throw new Error('La prévisualisation a appliqué une palette avant confirmation.');
  await onyxPalette.click();
  await merchantPage.locator('#mk-confirm-appearance').click();
  await merchantPage.getByText('Palette « Onyx Doré » appliquée. Le storefront utilisera désormais ces tokens.').waitFor({ timeout: 15_000 });
  const storefrontPage = await merchantBrowser.newPage({ viewport: { width: 1280, height: 900 } });
  await storefrontPage.goto(baseUrl, { waitUntil: 'networkidle', timeout: 45_000 });
  const onyxBrand = await storefrontPage.locator('.site-brand__mark').first().evaluate((element) => {
    const style = getComputedStyle(element);
    return { background: style.backgroundColor, color: style.color };
  });
  if (onyxBrand.background === onyxBrand.color) throw new Error('Le monogramme Onyx manque de contraste.');
  await storefrontPage.screenshot({ path: resolve(outputDir, 'storefront-onyx-brand-chromium.png'), fullPage: false });
  await storefrontPage.close();
  await merchantPage.locator('[data-appearance-palette="onyx-gold"]').click();
  await merchantPage.locator('#mk-reset-appearance').click();
  await merchantPage.locator('#mk-confirm-reset').click();
  await merchantPage.getByText('Palette Velora restaurée.').waitFor({ timeout: 15_000 });
  await merchantPage.screenshot({ path: resolve(outputDir, 'merchant-appearance-palettes-chromium.png'), fullPage: false });
  await merchantPage.locator('#mk-close-appearance').click();
  await merchantPage.locator('#mk-categories-entry').click();
  await merchantPage.locator('#mk-panel').getByText('Mes catégories').waitFor({ timeout: 15_000 });
  const temporaryCategory = `Atelier couverture QA ${Date.now()}`;
  await merchantPage.locator('[data-category-new]').click();
  await merchantPage.locator('#mk-new-category-name').fill(temporaryCategory);
  await merchantPage.locator('[data-category-create]').click();
  await merchantPage.locator('[data-category-open]', { hasText: temporaryCategory }).waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-category-open]', { hasText: temporaryCategory }).click();
  const categoryCoverInput = merchantPage.locator('[data-category-cover-input]');
  await categoryCoverInput.waitFor({ timeout: 15_000 });
  await categoryCoverInput.setInputFiles('/home/ubuntu/webdev-static-assets/keleva-vase-gallery-unsplash-collection.jpg');
  await merchantPage.locator('.mk-category-cover img').waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-category-back]').click();
  const temporaryCategoryCard = merchantPage.locator('[data-category-open]', { hasText: temporaryCategory });
  await temporaryCategoryCard.waitFor({ timeout: 15_000 });
  await temporaryCategoryCard.locator('xpath=following-sibling::div[contains(@class,"mk-category-tools")][1]//button[@data-category-up]').click();
  await merchantPage.locator('#mk-category-list').waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-category-open]', { hasText: temporaryCategory }).click();
  await merchantPage.locator('[data-category-delete]').click();
  await merchantPage.locator('.mk-confirm-scrim [role="dialog"]').waitFor({ timeout: 15_000 });
  await merchantPage.locator('#mk-dialog-confirm').click();
  await merchantPage.locator('#mk-panel').getByText('Ranger la boutique simplement').waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-category-open]', { hasText: temporaryCategory }).waitFor({ state: 'hidden', timeout: 15_000 });
  await merchantPage.locator('#mk-sales-entry').click();
  await merchantPage.locator('#mk-panel h2').filter({ hasText: 'Suivre ce qui se passe' }).waitFor({ timeout: 15_000 });
  await merchantPage.locator('.mk-sales-insights').waitFor({ timeout: 15_000 });
  await merchantPage.getByText('Cette semaine').waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-sales-filter="processing"]').click();
  await merchantPage.locator('#mk-panel h2').filter({ hasText: 'Suivre ce qui se passe' }).waitFor({ timeout: 15_000 });
  await merchantPage.locator('[data-sales-filter="all"]').click();
  await merchantPage.locator('#mk-panel h2').filter({ hasText: 'Suivre ce qui se passe' }).waitFor({ timeout: 15_000 });
  const couponExpiryInput = merchantPage.locator('#mk-coupon-expires');
  await couponExpiryInput.waitFor({ timeout: 15_000 });
  const couponExpiry = await couponExpiryInput.evaluate((element) => element.getAttribute('type') === 'date' ? 'ok' : 'invalid');
  if (couponExpiry !== 'ok') throw new Error('Champ d’expiration coupon absent ou non typé date.');
  let notificationBadge = 'not-run';
  const salesMetrics = await merchantPage.evaluate(() => window.__kelevaSalesSummary?.metrics || {});
  const attentionCount = Number(salesMetrics.orders_awaiting || 0) + Number(salesMetrics.out_of_stock || 0);
  if (process.env.KELEVA_TEST_PENDING_ORDER || process.env.KELEVA_TEST_STOCKOUT_PRODUCT) {
    if (attentionCount < 1) throw new Error('Le badge de notifications ne reçoit aucune alerte à afficher.');
    const notificationEntry = merchantPage.getByText(`Notifications (${attentionCount})`);
    await notificationEntry.waitFor({ timeout: 15_000 });
    if (process.env.KELEVA_TEST_STOCKOUT_PRODUCT) {
      if (Number(salesMetrics.out_of_stock || 0) < 1) throw new Error('La rupture de stock locale ne remonte pas dans les métriques.');
      const label = await notificationEntry.getAttribute('aria-label');
      if (!label?.includes('produit(s) en rupture de stock')) throw new Error(`Libellé de rupture incomplet : ${label || 'absent'}`);
    }
    notificationBadge = 'ok';
  }
  const stockoutNotification = process.env.KELEVA_TEST_STOCKOUT_PRODUCT ? 'ok' : 'not-run';
  let orderLifecycle = 'not-run';
  let salesDetail = 'not-run';
  if (process.env.KELEVA_TEST_ORDER_ID) {
    const orderId = process.env.KELEVA_TEST_ORDER_ID;
    const statusSelect = merchantPage.locator(`[data-order-status="${orderId}"]`);
    await statusSelect.waitFor({ timeout: 15_000 });
    const orderDetailButton = merchantPage.locator(`[data-order-save="${orderId}"]`).locator('xpath=following-sibling::button[contains(@class,"mk-order-detail")]');
    await orderDetailButton.waitFor({ timeout: 15_000 });
    await merchantPage.waitForTimeout(250);
    await orderDetailButton.click();
    await merchantPage.getByText('Préparer sans erreur').waitFor({ timeout: 15_000 });
    salesDetail = 'ok';
    await merchantPage.locator('[data-order-detail-back]').click();
    await statusSelect.waitFor({ timeout: 15_000 });
    await statusSelect.selectOption('completed');
    await merchantPage.locator(`[data-order-save="${orderId}"]`).click();
    await merchantPage.waitForFunction((id) => document.querySelector(`[data-order-status="${id}"]`)?.value === 'completed', orderId);
    orderLifecycle = 'ok';
  }
  await merchantPage.screenshot({ path: resolve(outputDir, 'merchant-sales-chromium.png'), fullPage: false });
  await merchantPage.locator('#mk-notifications-entry').click();
  await merchantPage.locator('#mk-panel h2').filter({ hasText: 'Ce qui demande votre attention' }).waitFor({ timeout: 15_000 });
  await merchantPage.locator('#mk-close-notifications').click();
  const mobileSheets = [];
  let mobileConfirmation = false;
  for (const width of [360, 390, 430]) {
    const mobileContext = await merchantBrowser.newContext({ viewport: { width, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
    const mobilePage = await mobileContext.newPage();
    await mobilePage.goto(`${baseUrl}/keleva-merchant/`, { waitUntil: 'networkidle', timeout: 45_000 });
    await mobilePage.locator('#mk-email').fill(process.env.KELEVA_LOCAL_MERCHANT_EMAIL);
    await mobilePage.locator('#mk-key').fill(process.env.KELEVA_LOCAL_MERCHANT_PASSWORD);
    await mobilePage.locator('#mk-connect').click();
    await mobilePage.locator('#mk-app').waitFor({ state: 'visible', timeout: 15_000 });
    if (process.env.KELEVA_TEST_CATALOG_PAGINATION) {
      await mobilePage.locator('#mk-search').fill('Vase Forme 02');
      await mobilePage.locator('.mk-product[data-product="11"]').waitFor({ timeout: 15_000 });
    }
    if (width === 360) {
      await mobilePage.locator('.mk-product[data-product="11"]').click();
      await mobilePage.locator('#mk-unpublish, #mk-publish').click();
      await mobilePage.locator('.mk-confirm-scrim [role="dialog"]').waitFor({ timeout: 15_000 });
      const dialogPosition = await mobilePage.locator('.mk-confirm-scrim').evaluate((element) => getComputedStyle(element).position);
      if (dialogPosition !== 'fixed') throw new Error(`Confirmation mobile inactive : ${dialogPosition}`);
      await mobilePage.screenshot({ path: resolve(outputDir, 'merchant-confirmation-mobile-360.png'), fullPage: false });
      await mobilePage.getByRole('button', { name: 'Annuler' }).click();
      await mobilePage.locator('#mk-close-product').click();
      mobileConfirmation = true;
    }
    await mobilePage.locator('#mk-categories-entry').click();
    await mobilePage.locator('#mk-panel .mk-panel').waitFor({ timeout: 15_000 });
    const position = await mobilePage.locator('#mk-panel').evaluate((element) => getComputedStyle(element).position);
    if (position !== 'fixed') throw new Error(`Feuille mobile inactive à ${width}px : ${position}`);
    const focusInsideSheet = await mobilePage.locator('#mk-panel').evaluate((element) => element.contains(document.activeElement));
    if (!focusInsideSheet) throw new Error(`Le focus ne rejoint pas la feuille mobile à ${width}px.`);
    const mobileHeader = await mobilePage.locator('#mk-panel .mk-panel-head').evaluate((element) => {
      const button = element.querySelector('button');
      const title = element.querySelector('h2');
      return { buttonWidth: button ? getComputedStyle(button).minWidth : '', titleWhitespace: title ? getComputedStyle(title).whiteSpace : '' };
    });
    if (mobileHeader.buttonWidth !== '44px' || mobileHeader.titleWhitespace !== 'nowrap') throw new Error(`En-tête mobile incomplet à ${width}px.`);
    await mobilePage.screenshot({ path: resolve(outputDir, `merchant-categories-mobile-${width}.png`), fullPage: false });
    mobileSheets.push(width);
    await mobileContext.close();
  }
  report.merchant = { sessionOnlyLogin: 'ok', skeleton, optionExperience: 'ok', catalogPagination, appearance: { previews: 5, storefrontPreview: 'ok', onyxBrandContrast: 'ok', reset: 'ok' }, categoriesView: 'ok', categoryCoverAndCleanup: 'ok', categoryReorder: 'ok', salesView: 'ok', salesFilters: 'ok', salesDetail, couponExpiry, notifications: 'ok', notificationBadge, stockoutNotification, orderLifecycle, mobileSheets, mobileConfirmation, browserErrors: merchantErrors };
  await merchantBrowser.close();
  if (merchantErrors.length) throw new Error(`Erreurs console marchande : ${JSON.stringify(merchantErrors)}`);
}

const checkoutPath = process.env.KELEVA_CHECKOUT_PATH || '/checkout/';
const checkoutBrowser = await chromium.launch({ headless: true });
const checkoutContext = await checkoutBrowser.newContext({ viewport: { width: 390, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
const checkoutPage = await checkoutContext.newPage();
await checkoutPage.goto(`${baseUrl}/shop/`, { waitUntil: 'networkidle', timeout: 45_000 });
await checkoutPage.locator('[data-keleva-add-product]').first().click();
await checkoutPage.locator('#keleva-cart-drawer[aria-hidden="false"]').waitFor({ timeout: 10_000 });
const quantityValue = checkoutPage.locator('.velora-cart-rail__quantity span').first();
const quantityBefore = await quantityValue.textContent();
await checkoutPage.getByRole('button', { name: 'Augmenter la quantité' }).first().click();
await checkoutPage.waitForFunction((previous) => document.querySelector('.velora-cart-rail__quantity span')?.textContent !== previous, quantityBefore);
await checkoutPage.locator('[data-velora-cart-subtotal]').filter({ hasNotText: '—' }).first().waitFor({ timeout: 10_000 });
await checkoutPage.waitForTimeout(500);
const cartState = await checkoutPage.evaluate(async () => {
  const response = await fetch('/wp-json/wc/store/v1/cart', { credentials: 'same-origin' });
  return response.ok ? response.json() : null;
});
if (!cartState || !Array.isArray(cartState.items) || cartState.items.length === 0) throw new Error('Le panier serveur est vide avant le checkout.');
await checkoutPage.goto(`${baseUrl}${checkoutPath}`, { waitUntil: 'networkidle', timeout: 45_000 });
await checkoutPage.locator('.keleva-checkout-intro').waitFor({ timeout: 15_000 });
await checkoutPage.locator('[data-block-name="woocommerce/checkout"], .wc-block-checkout, form.woocommerce-checkout').waitFor({ timeout: 15_000 });
await checkoutPage.screenshot({ path: resolve(outputDir, 'checkout-guest-mobile.png'), fullPage: false });
report.checkout = { guest: 'ok', blocks: 'ok', persistentCart: 'ok', drawerQuantity: 'ok' };
await checkoutContext.close();
await checkoutBrowser.close();

const browser = await chromium.launch({ headless: true });
let page;
for (const width of [360, 390, 430]) {
  const context = await browser.newContext({ viewport: { width, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
  page = await context.newPage();
  await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 45_000 });
  await page.locator('.site-cart[data-keleva-cart-trigger]').click();
  await page.locator('#keleva-cart-drawer[aria-hidden="false"]').waitFor({ timeout: 10_000 });
  await page.screenshot({ path: resolve(outputDir, `cart-drawer-mobile-${width}.png`), fullPage: false });
  await context.close();
}
const axeContext = await browser.newContext({ viewport: { width: 390, height: 844 }, locale: 'fr-FR', ignoreHTTPSErrors: true });
page = await axeContext.newPage();
await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 45_000 });
await page.locator('.site-cart[data-keleva-cart-trigger]').click();
await page.locator('#keleva-cart-drawer[aria-hidden="false"]').waitFor({ timeout: 10_000 });
await page.addScriptTag({ content: axeSource });
const results = await page.evaluate(async () => {
  const output = await window.axe.run(document, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa'] },
    rules: { 'color-contrast': { enabled: true } },
  });
  return { violations: output.violations.map(({ id, impact, help, nodes }) => ({ id, impact, help, nodes: nodes.map((node) => ({ target: node.target, html: node.html, failureSummary: node.failureSummary })) })), passes: output.passes.length };
});
report.axe = results;
await axeContext.close();
await browser.close();
await writeFile(resolve(outputDir, 'playwright-axe-report.json'), `${JSON.stringify(report, null, 2)}\n`);
if (Object.values(report.engines).some((engine) => engine.browserErrors.length)) throw new Error(`Erreurs navigateur : ${JSON.stringify(report.engines)}`);
if (report.axe.violations.length) throw new Error(`Violations Axe : ${JSON.stringify(report.axe.violations)}`);
console.log(`QA Playwright/Axe terminée : ${Object.keys(report.engines).join(', ')} ; Axe passes=${report.axe.passes}.`);
