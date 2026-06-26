import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const kaspiUrl = process.argv[2];
const productId = process.argv[3] || '0';
const sku = process.argv[4] || 'unknown';
const debug = process.argv[5] === '1';
const screenshotDir = process.argv[6] || 'storage/logs/kaspi-screenshots';
const debugDir = process.argv[7] || 'storage/logs/kaspi-debug';

const safeSku = sanitize(sku);
const baseName = `kaspi-${sanitize(productId)}-${safeSku}`;

function output(payload) {
  process.stdout.write(JSON.stringify(payload));
}

function sanitize(value) {
  return String(value || 'unknown').replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '') || 'unknown';
}

function decodeUrl(raw) {
  return String(raw || '')
    .replaceAll('\\/', '/')
    .replaceAll('\\u002F', '/')
    .replaceAll('&amp;', '&')
    .trim();
}

function normalizeImageUrl(url) {
  const decoded = decodeUrl(url);
  if (!/^https?:\/\//i.test(decoded)) return null;

  let normalized = decoded
    .replace(/\/(?:small|thumbnail|preview|medium)\//gi, '/large/')
    .replace(/_small(?=\.)/gi, '')
    .replace(/_\d+x\d+(?=\.)/gi, '')
    .replace(/\/\d+x\d+\//g, '/');

  try {
    const parsed = new URL(normalized);
    if (!/\.(?:jpg|jpeg|png|webp)(?:$|\?)/i.test(parsed.pathname + parsed.search)) {
      return null;
    }
    normalized = parsed.toString();
  } catch {
    return null;
  }

  if (!/(kaspi|ks\.|kaspi\.kz|kaspi\.shop|cloudfront|akamai|cdn)/i.test(normalized)) {
    return null;
  }

  return normalized;
}

function pushUrl(bucket, raw) {
  const normalized = normalizeImageUrl(raw);
  if (normalized) bucket.push(normalized);
}

function collectFromText(text) {
  const urls = [];
  const matches = String(text || '').match(/https?:\\?\/\\?\/[^"'\\\s<>]+?\.(?:jpg|jpeg|png|webp)(?:\?[^"'\\\s<>]*)?/gi) || [];
  for (const raw of matches) pushUrl(urls, raw);
  return urls;
}

function walkJson(value, bucket, depth = 0) {
  if (depth > 12 || value == null) return;

  if (typeof value === 'string') {
    collectFromText(value).forEach((url) => bucket.push(url));
    if (/\.(?:jpg|jpeg|png|webp)/i.test(value)) pushUrl(bucket, value);
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((item) => walkJson(item, bucket, depth + 1));
    return;
  }

  if (typeof value === 'object') {
    Object.values(value).forEach((item) => walkJson(item, bucket, depth + 1));
  }
}

function uniqueInOrder(groups) {
  const result = [];
  const seen = new Set();

  for (const group of groups) {
    for (const url of group) {
      const normalized = normalizeImageUrl(url);
      if (!normalized || seen.has(normalized)) continue;
      seen.add(normalized);
      result.push(normalized);
    }
  }

  return result;
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

if (!kaspiUrl) {
  output({ ok: false, error: 'kaspi_url_required', error_message: 'kaspi_url_required', photo_urls: [] });
  process.exit(2);
}

let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch (error) {
  output({ ok: false, error: 'playwright_not_installed', error_message: error.message, photo_urls: [] });
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
  kaspi_url: kaspiUrl,
  kaspi_page_loaded: false,
  photos_found: 0,
  photo_urls: [],
  artifact_paths: {},
  error_message: null,
};

try {
  await page.goto(kaspiUrl, { waitUntil: 'domcontentloaded', timeout: 35000 });
  result.kaspi_page_loaded = true;
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(2500);

  const html = await page.content();
  const stateUrls = [];
  const domUrls = [];
  const ogUrls = [];
  const textUrls = collectFromText(html);

  const scriptPayloads = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('script')).map((script) => ({
      type: script.type || '',
      id: script.id || '',
      text: script.textContent || '',
    }));
  });

  for (const script of scriptPayloads) {
    collectFromText(script.text).forEach((url) => stateUrls.push(url));

    if (/json/i.test(script.type) || script.id === '__NEXT_DATA__') {
      try {
        walkJson(JSON.parse(script.text), stateUrls);
      } catch {}
    }
  }

  const domValues = await page.evaluate(() => {
    const values = { gallery: [], dom: [], og: [] };

    const readAttrs = (node, attrs, bucket) => {
      attrs.forEach((attr) => {
        const value = node.getAttribute(attr);
        if (!value) return;
        value.split(',').forEach((part) => bucket.push(part.trim().split(/\s+/)[0]));
      });
    };

    document.querySelectorAll('[class*="gallery" i] img, [class*="slider" i] img, [class*="thumb" i] img, [data-gallery] img').forEach((node) => {
      readAttrs(node, ['src', 'srcset', 'data-src', 'data-original', 'data-lazy', 'data-image'], values.gallery);
    });

    document.querySelectorAll('img, source').forEach((node) => {
      readAttrs(node, ['src', 'srcset', 'data-src', 'data-original', 'data-lazy', 'data-image'], values.dom);
    });

    document.querySelectorAll('meta[property="og:image"], meta[name="og:image"], link[rel="image_src"]').forEach((node) => {
      readAttrs(node, ['content', 'href'], values.og);
    });

    document.querySelectorAll('[style*="background-image"]').forEach((node) => {
      const style = node.getAttribute('style') || '';
      const match = style.match(/url\(["']?([^"')]+)["']?\)/i);
      if (match) values.dom.push(match[1]);
    });

    return values;
  });

  domValues.gallery.forEach((url) => pushUrl(domUrls, url));
  domValues.dom.forEach((url) => pushUrl(domUrls, url));
  domValues.og.forEach((url) => pushUrl(ogUrls, url));

  const photoUrls = uniqueInOrder([stateUrls, domUrls, textUrls, ogUrls]);

  result.photo_urls = photoUrls;
  result.photos_found = photoUrls.length;
  result.ok = photoUrls.length > 0;

  if (!result.ok) {
    result.error = 'photos_not_found';
    result.error_message = 'Photos not found on Kaspi page';
    result.artifact_paths = await saveArtifacts(page);
  }

  output(result);
} catch (error) {
  result.error = 'extractor_failed';
  result.error_message = error.message;
  result.artifact_paths = await saveArtifacts(page);
  output(result);
} finally {
  await browser.close();
}
