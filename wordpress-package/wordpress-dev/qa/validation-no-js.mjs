import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

const baseUrl = process.env.KELEVA_STAGING_BASE_URL || 'https://darkblue-spoonbill-498612.hostingersite.com';
const validationUrl = `${baseUrl}/keleva-validation-widgets/?widgets=no-js-proof&add-to-cart=49`;
const cartUrl = `${baseUrl}/panier/?cleanup=no-js-proof`;

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  javaScriptEnabled: false,
  ignoreHTTPSErrors: true,
  viewport: { width: 1280, height: 900 },
  locale: 'fr-FR',
});
const page = await context.newPage();
let report;

try {
  await page.goto(validationUrl, { waitUntil: 'domcontentloaded', timeout: 45_000 });
  const tabs = page.locator('.keleva-product-tabs');
  const firstPanel = tabs.locator('details').first();
  const firstSummary = firstPanel.locator('summary');
  const checkout = page.locator('.keleva-checkout-shell');
  const checkoutForm = checkout.locator('form.checkout, form.woocommerce-checkout');

  await tabs.waitFor({ timeout: 15_000 });
  await checkout.waitFor({ timeout: 15_000 });
  await checkoutForm.waitFor({ timeout: 15_000 });
  if (await firstPanel.isVisible()) {
    await firstSummary.focus();
    const initiallyOpen = await firstPanel.evaluate((element) => element.open);
    await firstSummary.press('Space');
    const afterFirstToggle = await firstPanel.evaluate((element) => element.open);
    if (afterFirstToggle === initiallyOpen) {
      throw new Error('Le panneau Product Tabs ne bascule pas au clavier sans JavaScript.');
    }
    if (!afterFirstToggle) {
      await firstSummary.press('Space');
      if (!(await firstPanel.evaluate((element) => element.open))) {
        throw new Error('Le panneau Product Tabs ne se rouvre pas au clavier sans JavaScript.');
      }
    }
  }

  const firstName = checkout.locator('#billing_first_name');
  const lastName = checkout.locator('#billing_last_name');
  await firstName.focus();
  await page.keyboard.press('Tab');
  const activeField = await page.evaluate(() => document.activeElement?.id || '');
  if (activeField !== 'billing_last_name') {
    throw new Error(`La navigation clavier checkout sans JavaScript est incomplète : ${activeField || 'aucun focus'}.`);
  }

  const cartCount = page.locator('.site-cart b, [data-velora-header-cart-count], [data-keleva-header-cart-count]').first();
  report = {
    javascriptEnabled: false,
    productTabs: await tabs.count(),
    productTabsKeyboard: 'ok',
    checkoutForms: await checkoutForm.count(),
    checkoutKeyboard: 'ok',
    cartCountDuringValidation: await cartCount.count() ? await cartCount.textContent() : 'not-exposed',
  };
} finally {
  try {
    await page.goto(cartUrl, { waitUntil: 'domcontentloaded', timeout: 45_000 });
    const remove = page.locator('a.remove').first();
    if (await remove.count()) {
      const removeUrl = await remove.getAttribute('href');
      if (!removeUrl) throw new Error('URL de suppression panier introuvable.');
      await page.goto(new URL(removeUrl, baseUrl).href, { waitUntil: 'domcontentloaded', timeout: 45_000 });
    }
    await page.goto(`${cartUrl}&verified=1`, { waitUntil: 'domcontentloaded', timeout: 45_000 });
    if (!(await page.getByText('Votre panier est actuellement vide.', { exact: true }).count())) {
      throw new Error('Le panier de recette sans JavaScript n’a pas été nettoyé.');
    }
    if (report) {
      const finalReport = { ...report, cartCleanup: 'empty' };
      const outputDir = process.env.KELEVA_STAGING_QA_OUTPUT_DIR;
      if (outputDir) {
        await mkdir(outputDir, { recursive: true });
        await writeFile(join(outputDir, 'validation-no-js.json'), `${JSON.stringify(finalReport, null, 2)}\n`);
      }
      console.log(JSON.stringify(finalReport));
    }
  } finally {
    await browser.close();
  }
}
