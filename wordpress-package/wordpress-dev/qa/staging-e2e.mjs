import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const baseUrl = process.env.KELEVA_STAGING_BASE_URL || 'https://darkblue-spoonbill-498612.hostingersite.com';
const outputDir = resolve(process.env.KELEVA_STAGING_QA_OUTPUT_DIR || '../proofs/staging-e2e');
const report = { baseUrl, generatedAt: new Date().toISOString(), engine: 'chromium', checks: {} };

await mkdir(outputDir, { recursive: true });
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1280, height: 900 }, locale: 'fr-FR' });
const page = await context.newPage();
const browserErrors = [];
page.on('pageerror', (error) => browserErrors.push(error.message));

async function cleanCart() {
  await page.goto(`${baseUrl}/panier/?cleanup=staging-e2e`, { waitUntil: 'domcontentloaded', timeout: 45_000 });
  const remove = page.locator('a.remove').first();
  if (await remove.count()) {
    const href = await remove.getAttribute('href');
    if (!href) throw new Error('Lien de suppression panier absent.');
    await page.goto(new URL(href, baseUrl).href, { waitUntil: 'domcontentloaded', timeout: 45_000 });
  }
  await page.goto(`${baseUrl}/panier/?cleanup=staging-e2e-final`, { waitUntil: 'domcontentloaded', timeout: 45_000 });
  await page.getByText('Votre panier est actuellement vide.', { exact: true }).waitFor({ timeout: 15_000 });
}

let runError;
try {
  await page.goto(`${baseUrl}/boutique/?e2e=chromium`, { waitUntil: 'networkidle', timeout: 45_000 });
  const favoriteForm = page.locator('form.keleva-product-card__favorite-toggle').first();
  const favoriteButton = favoriteForm.getByRole('button');
  const productId = await favoriteForm.locator('input[name="keleva_product_id"]').inputValue();
  if (await favoriteButton.getAttribute('data-velora-favorite-toggle')) throw new Error('Le favori staging utilise encore un gestionnaire décoratif JavaScript.');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle', timeout: 20_000 }), favoriteButton.click()]);
  await page.goto(`${baseUrl}/boutique/?e2e=chromium-favorite`, { waitUntil: 'networkidle', timeout: 45_000 });
  const persistedFavorite = page.locator(`form.keleva-product-card__favorite-toggle:has(input[name="keleva_product_id"][value="${productId}"])`).getByRole('button');
  if ((await persistedFavorite.getAttribute('aria-pressed')) !== 'true') throw new Error('Le favori staging ne persiste pas après POST.');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle', timeout: 20_000 }), persistedFavorite.click()]);
  report.checks.catalogFavorite = 'add-persist-remove';

  await page.goto(`${baseUrl}/keleva-validation-widgets/?e2e=chromium`, { waitUntil: 'networkidle', timeout: 45_000 });
  const robots = await page.locator('meta[name="robots"]').getAttribute('content');
  if (!robots?.includes('noindex')) throw new Error('La page de validation staging n’est plus noindex.');
  for (const selector of ['.keleva-wishlist', '.keleva-compare', '.keleva-mega-menu', '.keleva-product-tabs', '.keleva-checkout-shell']) {
    if (!(await page.locator(selector).count())) throw new Error(`Widget staging absent : ${selector}.`);
  }
  const summary = page.locator('.keleva-product-tabs summary').first();
  const panel = page.locator('.keleva-product-tabs details').first();
  await summary.focus();
  const initiallyOpen = await panel.evaluate((element) => element.open);
  await summary.press('Space');
  const afterFirstToggle = await panel.evaluate((element) => element.open);
  if (afterFirstToggle === initiallyOpen) {
    throw new Error('Product Tabs ne bascule pas au clavier sur staging.');
  }
  if (!afterFirstToggle) {
    await summary.press('Space');
    if (!(await panel.evaluate((element) => element.open))) {
      throw new Error('Product Tabs ne se rouvre pas au clavier sur staging.');
    }
  }
  const megaMenuLink = page.locator('.keleva-mega-menu a').first();
  if ((await page.locator('.keleva-mega-menu a').count()) < 1 || !(await megaMenuLink.getAttribute('href'))) throw new Error('Mega Menu staging ne fournit pas de lien clavier.');
  report.checks.widgetsSsrKeyboard = 'wishlist-compare-mega-tabs-checkout';

  await page.goto(`${baseUrl}/keleva-validation-widgets/?e2e=chromium-checkout&add-to-cart=49`, { waitUntil: 'networkidle', timeout: 45_000 });
  const checkoutForm = page.locator('.keleva-checkout-shell form.checkout, .keleva-checkout-shell form.woocommerce-checkout');
  await checkoutForm.waitFor({ timeout: 20_000 });
  const total = await page.locator('.keleva-checkout-shell .order-total').textContent();
  if (!/53,00/.test(total || '')) throw new Error(`Total checkout staging inattendu : ${total || 'absent'}.`);
  const firstName = checkoutForm.locator('#billing_first_name');
  await firstName.focus();
  await page.keyboard.press('Tab');
  if ((await page.evaluate(() => document.activeElement?.id)) !== 'billing_last_name') throw new Error('Navigation Tab checkout staging incomplète.');
  report.checks.checkoutWithoutPayment = 'form-ssr-53-mad-keyboard';

  report.browserErrors = browserErrors;
  if (browserErrors.length) throw new Error(`Erreurs navigateur staging : ${JSON.stringify(browserErrors)}`);
} catch (error) {
  runError = error;
}

let cleanupError;
try {
  await cleanCart();
  report.checks.cartCleanup = 'empty';
} catch (error) {
  cleanupError = error;
} finally {
  await browser.close();
}

if (runError && cleanupError) {
  throw new AggregateError([runError, cleanupError], 'La suite E2E et le nettoyage du panier ont échoué.');
}
if (runError) throw runError;
if (cleanupError) throw cleanupError;

await writeFile(resolve(outputDir, 'staging-e2e-chromium.json'), `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify(report));
