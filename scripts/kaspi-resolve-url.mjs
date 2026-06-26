import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const productUrl = process.argv[2];
const screenshotDir = process.argv[3] || 'storage/logs/kaspi-screenshots';

function output(payload) {
  process.stdout.write(JSON.stringify(payload));
}

async function screenshot(page, name) {
  try {
    fs.mkdirSync(screenshotDir, { recursive: true });
    await page.screenshot({ path: path.join(screenshotDir, name), fullPage: true });
  } catch {}
}

if (!productUrl) {
  output({ ok: false, error: 'product_url_required' });
  process.exit(2);
}

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch (error) {
  output({ ok: false, error: 'playwright_not_installed', message: error.message });
  process.exit(2);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1366, height: 768 },
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
});

const page = await context.newPage();
let result = {
  ok: false,
  kaspi_button_found: false,
  kaspi_widget_opened: false,
  resolved_kaspi_url: null,
};

try {
  await page.goto(productUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

  const widget = page.locator('.kaspi-button-wrap .ks-widget, .ks-widget').first();
  result.kaspi_button_found = await widget.count() > 0;

  if (!result.kaspi_button_found) {
    await screenshot(page, `no-kaspi-button-${Date.now()}.png`);
    output({ ...result, error: 'no_kaspi_button' });
    await browser.close();
    process.exit(0);
  }

  await widget.scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(2500);

  const candidates = [
    '.kaspi-button-wrap a[href*="kaspi.kz"]',
    '.ks-widget a[href*="kaspi.kz"]',
    'a[href*="kaspi.kz/shop"]',
    'a[href*="kaspi.kz"]',
    '.kaspi-button-wrap button',
    '.ks-widget button',
    '.kaspi-button-wrap',
    '.ks-widget',
  ];

  for (const selector of candidates) {
    const locator = page.locator(selector).first();
    if (await locator.count() === 0) continue;

    const href = await locator.getAttribute('href').catch(() => null);
    if (href && href.includes('kaspi.kz')) {
      result.resolved_kaspi_url = new URL(href, productUrl).toString();
      result.kaspi_widget_opened = true;
      break;
    }

    const popupPromise = context.waitForEvent('page', { timeout: 7000 }).catch(() => null);
    const navPromise = page.waitForURL(/kaspi\.kz/i, { timeout: 7000 }).catch(() => null);
    await locator.click({ timeout: 5000, force: true }).catch(() => {});
    const popup = await popupPromise;
    await navPromise;

    if (popup) {
      await popup.waitForLoadState('domcontentloaded', { timeout: 10000 }).catch(() => {});
      if (popup.url().includes('kaspi.kz')) {
        result.resolved_kaspi_url = popup.url();
        result.kaspi_widget_opened = true;
        break;
      }
    }

    if (page.url().includes('kaspi.kz')) {
      result.resolved_kaspi_url = page.url();
      result.kaspi_widget_opened = true;
      break;
    }
  }

  if (!result.resolved_kaspi_url) {
    const pageLinks = await page.locator('a[href*="kaspi.kz"]').evaluateAll((links) => links.map((link) => link.href));
    result.resolved_kaspi_url = pageLinks.find((href) => /kaspi\.kz/i.test(href)) || null;
  }

  result.ok = Boolean(result.resolved_kaspi_url);
  if (!result.ok) {
    await screenshot(page, `kaspi-url-not-resolved-${Date.now()}.png`);
    result.error = 'kaspi_url_not_resolved';
  }

  output(result);
} catch (error) {
  await screenshot(page, `kaspi-resolve-error-${Date.now()}.png`);
  output({ ...result, ok: false, error: 'resolver_failed', message: error.message });
} finally {
  await browser.close();
}
