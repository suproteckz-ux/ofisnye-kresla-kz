import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const kaspiUrl = process.argv[2];
const screenshotDir = process.argv[3] || 'storage/logs/kaspi-screenshots';

function output(payload) {
  process.stdout.write(JSON.stringify(payload));
}

function normalizeImageUrl(url) {
  if (!url || !/^https?:\/\//i.test(url)) return null;
  return url
    .replace(/\/small\//g, '/large/')
    .replace(/\/thumbnail\//g, '/large/')
    .replace(/_small(?=\.)/g, '')
    .replace(/_\d+x\d+(?=\.)/g, '');
}

function collectFromText(text) {
  const urls = [];
  const matches = text.match(/https?:\\?\/\\?\/[^"'\\\s<>]+?\.(?:jpg|jpeg|png|webp)(?:\?[^"'\\\s<>]*)?/gi) || [];
  for (const raw of matches) {
    const clean = normalizeImageUrl(raw.replaceAll('\\/', '/').replaceAll('\\u002F', '/'));
    if (clean && /kaspi|ks\.|kaspi\.kz|kaspi\.shop/i.test(clean)) urls.push(clean);
  }
  return urls;
}

async function screenshot(page, name) {
  try {
    fs.mkdirSync(screenshotDir, { recursive: true });
    await page.screenshot({ path: path.join(screenshotDir, name), fullPage: true });
  } catch {}
}

if (!kaspiUrl) {
  output({ ok: false, error: 'kaspi_url_required', photo_urls: [] });
  process.exit(2);
}

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch (error) {
  output({ ok: false, error: 'playwright_not_installed', message: error.message, photo_urls: [] });
  process.exit(2);
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1366, height: 768 },
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
});
const page = await context.newPage();

try {
  await page.goto(kaspiUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(1500);

  const urls = [];
  const seen = new Set();
  const push = (url) => {
    const normalized = normalizeImageUrl(url);
    if (!normalized || seen.has(normalized)) return;
    seen.add(normalized);
    urls.push(normalized);
  };

  for (const url of collectFromText(await page.content())) push(url);

  const domUrls = await page.evaluate(() => {
    const values = [];
    document.querySelectorAll('img, source, meta[property="og:image"]').forEach((node) => {
      ['src', 'srcset', 'content', 'data-src', 'data-original'].forEach((attr) => {
        const value = node.getAttribute(attr);
        if (!value) return;
        value.split(',').forEach((part) => values.push(part.trim().split(/\s+/)[0]));
      });
    });
    return values;
  });
  domUrls.forEach(push);

  output({
    ok: urls.length > 0,
    kaspi_page_loaded: true,
    photo_urls: urls,
    photos_found: urls.length,
    error: urls.length > 0 ? null : 'photos_not_found',
  });
} catch (error) {
  await screenshot(page, `kaspi-extract-error-${Date.now()}.png`);
  output({ ok: false, kaspi_page_loaded: false, error: 'extractor_failed', message: error.message, photo_urls: [] });
} finally {
  await browser.close();
}
