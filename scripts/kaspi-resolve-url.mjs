import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const productUrl = process.argv[2];
const productId = process.argv[3] || '0';
const sku = process.argv[4] || 'unknown';
const debug = process.argv[5] === '1';
const screenshotDir = process.argv[6] || 'storage/logs/kaspi-screenshots';
const debugDir = process.argv[7] || 'storage/logs/kaspi-debug';

const safeSku = sanitize(sku);
const baseName = `product-${sanitize(productId)}-${safeSku}`;

function output(payload) {
  process.stdout.write(JSON.stringify(payload));
}

function sanitize(value) {
  return String(value || 'unknown').replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '') || 'unknown';
}

function isKaspiUrl(url) {
  return Boolean(url && /https?:\/\/([^/]+\.)?kaspi\.kz/i.test(url));
}

function absoluteUrl(url, baseUrl) {
  try {
    return new URL(url, baseUrl).toString();
  } catch {
    return null;
  }
}

async function saveArtifacts(page, prefix = baseName) {
  const paths = {};

  try {
    fs.mkdirSync(debugDir, { recursive: true });
    const htmlPath = path.join(debugDir, `${prefix}.html`);
    fs.writeFileSync(htmlPath, await page.content(), 'utf8');
    paths.html = htmlPath;
  } catch {}

  try {
    fs.mkdirSync(screenshotDir, { recursive: true });
    const screenshotPath = path.join(screenshotDir, `${prefix}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });
    paths.screenshot = screenshotPath;
  } catch {}

  return paths;
}

async function countLocator(page, selector) {
  return page.locator(selector).count().catch(() => 0);
}

async function countText(page, pattern) {
  return page.getByText(pattern).count().catch(() => 0);
}

async function collectDiagnostics(page) {
  const diagnostics = {
    '.kaspi-button-wrap': await countLocator(page, '.kaspi-button-wrap'),
    '.ks-widget': await countLocator(page, '.ks-widget'),
    'iframe[src*="kaspi"]': await countLocator(page, 'iframe[src*="kaspi"]'),
    'script[src*="kaspi"]': await countLocator(page, 'script[src*="kaspi"]'),
    'a[href*="kaspi"]': await countLocator(page, 'a[href*="kaspi"]'),
    'button/text "В рассрочку"': await countText(page, /В\s*рассрочку/i),
    'text "Kaspi"': await countText(page, /Kaspi/i),
    'attributes containing kaspi': 0,
  };

  diagnostics['attributes containing kaspi'] = await page.evaluate(() => {
    let count = 0;
    for (const element of document.querySelectorAll('*')) {
      for (const attr of element.getAttributeNames()) {
        const value = element.getAttribute(attr) || '';
        if (/kaspi/i.test(attr) || /kaspi/i.test(value)) {
          count++;
          break;
        }
      }
    }
    return count;
  }).catch(() => 0);

  return diagnostics;
}

async function firstKaspiHref(page) {
  const hrefs = await page.locator('a[href*="kaspi"]').evaluateAll((links) => links.map((link) => link.href)).catch(() => []);
  return hrefs.find((href) => /kaspi\.kz\/shop\/p\//i.test(href))
    || hrefs.find((href) => /kaspi\.kz/i.test(href))
    || null;
}

async function firstKaspiIframe(page) {
  const urls = await page.locator('iframe[src*="kaspi"]').evaluateAll((frames) => frames.map((frame) => frame.src)).catch(() => []);
  return urls.find((url) => /kaspi\.kz\/shop\/p\//i.test(url))
    || urls.find((url) => /kaspi\.kz/i.test(url))
    || null;
}

async function clickAndResolve(context, page, locator, selector, result) {
  await locator.scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => {});

  const href = await locator.getAttribute('href').catch(() => null);
  const absoluteHref = href ? absoluteUrl(href, productUrl) : null;
  if (isKaspiUrl(absoluteHref)) {
    result.selector_used = selector;
    result.click_success = false;
    result.resolved_kaspi_url = absoluteHref;
    result.kaspi_widget_opened = true;
    return true;
  }

  const popupPromise = context.waitForEvent('page', { timeout: 9000 }).catch(() => null);
  const navPromise = page.waitForURL(/kaspi\.kz/i, { timeout: 9000 }).catch(() => null);

  try {
    await locator.click({ timeout: 7000, force: true });
    result.click_success = true;
    result.selector_used = selector;
  } catch (error) {
    result.error_message = error.message;
    return false;
  }

  const popup = await popupPromise;
  await navPromise;
  await page.waitForTimeout(1800);

  if (popup) {
    result.popup_opened = true;
    await popup.waitForLoadState('domcontentloaded', { timeout: 12000 }).catch(() => {});
    await popup.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    result.new_page_url = popup.url();

    if (isKaspiUrl(result.new_page_url)) {
      result.resolved_kaspi_url = result.new_page_url;
      result.kaspi_widget_opened = true;
      return true;
    }
  }

  if (isKaspiUrl(page.url())) {
    result.resolved_kaspi_url = page.url();
    result.kaspi_widget_opened = true;
    return true;
  }

  const iframeUrl = await firstKaspiIframe(page);
  if (iframeUrl) {
    result.iframe_url = iframeUrl;
    if (isKaspiUrl(iframeUrl)) {
      result.resolved_kaspi_url = iframeUrl;
      result.kaspi_widget_opened = true;
      return true;
    }
  }

  const linkUrl = await firstKaspiHref(page);
  if (linkUrl) {
    result.resolved_kaspi_url = linkUrl;
    result.kaspi_widget_opened = true;
    return true;
  }

  return false;
}

if (!productUrl) {
  output({ ok: false, error: 'product_url_required', error_message: 'product_url_required' });
  process.exit(2);
}

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch (error) {
  output({ ok: false, error: 'playwright_not_installed', error_message: error.message });
  process.exit(2);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1366, height: 768 },
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
});

const page = await context.newPage();
const result = {
  ok: false,
  product_id: productId,
  sku,
  product_url: productUrl,
  page_loaded: false,
  found_elements: {},
  kaspi_button_found: false,
  selector_used: null,
  click_success: false,
  popup_opened: false,
  new_page_url: null,
  iframe_url: null,
  kaspi_widget_opened: false,
  resolved_kaspi_url: null,
  artifact_paths: {},
  error_message: null,
};

try {
  await page.goto(productUrl, { waitUntil: 'domcontentloaded', timeout: 35000 });
  result.page_loaded = true;
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3500);

  result.found_elements = await collectDiagnostics(page);
  result.kaspi_button_found = Object.values(result.found_elements).some((count) => Number(count) > 0);

  const iframeUrl = await firstKaspiIframe(page);
  if (iframeUrl) {
    result.iframe_url = iframeUrl;
  }

  const directHref = await firstKaspiHref(page);
  if (directHref && /kaspi\.kz\/shop\/p\//i.test(directHref)) {
    result.resolved_kaspi_url = directHref;
    result.kaspi_widget_opened = true;
    result.ok = true;
    output(result);
    await browser.close();
    process.exit(0);
  }

  const candidates = [
    ['.kaspi-button-wrap a[href*="kaspi"]', () => page.locator('.kaspi-button-wrap a[href*="kaspi"]').first()],
    ['.ks-widget a[href*="kaspi"]', () => page.locator('.ks-widget a[href*="kaspi"]').first()],
    ['a[href*="kaspi.kz/shop"]', () => page.locator('a[href*="kaspi.kz/shop"]').first()],
    ['a[href*="kaspi"]', () => page.locator('a[href*="kaspi"]').first()],
    ['.kaspi-button-wrap button', () => page.locator('.kaspi-button-wrap button').first()],
    ['.ks-widget button', () => page.locator('.ks-widget button').first()],
    ['button:has-text("В рассрочку")', () => page.locator('button').filter({ hasText: /В\s*рассрочку/i }).first()],
    ['text "В рассрочку"', () => page.getByText(/В\s*рассрочку/i).first()],
    ['text "Kaspi"', () => page.getByText(/Kaspi/i).first()],
    ['.kaspi-button-wrap *', () => page.locator('.kaspi-button-wrap *').last()],
    ['.ks-widget *', () => page.locator('.ks-widget *').last()],
    ['.kaspi-button-wrap', () => page.locator('.kaspi-button-wrap').first()],
    ['.ks-widget', () => page.locator('.ks-widget').first()],
  ];

  for (const [selector, getLocator] of candidates) {
    const locator = getLocator();
    if (await locator.count().catch(() => 0) === 0) continue;
    if (await clickAndResolve(context, page, locator, selector, result)) break;
  }

  if (!result.resolved_kaspi_url && result.iframe_url && isKaspiUrl(result.iframe_url)) {
    result.resolved_kaspi_url = result.iframe_url;
  }

  if (!result.resolved_kaspi_url) {
    const href = await firstKaspiHref(page);
    if (href) result.resolved_kaspi_url = href;
  }

  result.ok = Boolean(result.resolved_kaspi_url);
  if (!result.ok) {
    result.error = 'kaspi_url_not_resolved';
    result.error_message ||= 'Kaspi URL not resolved';
    result.artifact_paths = await saveArtifacts(page);
  }

  output(result);
} catch (error) {
  result.error = 'resolver_failed';
  result.error_message = error.message;
  result.artifact_paths = await saveArtifacts(page);
  output(result);
} finally {
  await browser.close();
}
