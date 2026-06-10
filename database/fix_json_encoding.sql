-- ================================================================
-- Исправление двойного кодирования JSON в базе данных
-- Запускать через phpMyAdmin или mysql CLI:
--   mysql -u user -p database < fix_json_encoding.sql
-- ================================================================

-- ШАГ 1: ДИАГНОСТИКА (запустите сначала, чтобы увидеть масштаб)
SELECT 
  (SELECT COUNT(*) FROM products WHERE attributes IS NOT NULL AND (attributes LIKE '"{%' OR attributes LIKE '"[%')) AS bad_product_attributes,
  (SELECT COUNT(*) FROM products WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_product_faq,
  (SELECT COUNT(*) FROM categories WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_category_faq,
  (SELECT COUNT(*) FROM seo_filters WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_seofilter_faq,
  (SELECT COUNT(*) FROM seo_pages WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_seopage_faq,
  (SELECT COUNT(*) FROM blog_posts WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_blogpost_faq;

-- ШАГ 2: ИСПРАВЛЕНИЕ (выполнять после диагностики)
-- JSON_UNQUOTE снимает внешние кавычки и обратные слэши

UPDATE products 
SET attributes = JSON_UNQUOTE(attributes)
WHERE attributes IS NOT NULL 
  AND (attributes LIKE '"{%' OR attributes LIKE '"[%');

UPDATE products 
SET faq = JSON_UNQUOTE(faq)
WHERE faq IS NOT NULL 
  AND (faq LIKE '"{%' OR faq LIKE '"[%');

UPDATE categories 
SET faq = JSON_UNQUOTE(faq)
WHERE faq IS NOT NULL 
  AND (faq LIKE '"{%' OR faq LIKE '"[%');

UPDATE seo_filters 
SET faq = JSON_UNQUOTE(faq)
WHERE faq IS NOT NULL 
  AND (faq LIKE '"{%' OR faq LIKE '"[%');

UPDATE seo_pages 
SET faq = JSON_UNQUOTE(faq)
WHERE faq IS NOT NULL 
  AND (faq LIKE '"{%' OR faq LIKE '"[%');

UPDATE blog_posts 
SET faq = JSON_UNQUOTE(faq)
WHERE faq IS NOT NULL 
  AND (faq LIKE '"{%' OR faq LIKE '"[%');

-- ШАГ 3: ПРОВЕРКА (после исправления — должны вернуть 0)
SELECT 
  (SELECT COUNT(*) FROM products WHERE attributes IS NOT NULL AND (attributes LIKE '"{%' OR attributes LIKE '"[%')) AS bad_product_attributes,
  (SELECT COUNT(*) FROM products WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_product_faq,
  (SELECT COUNT(*) FROM categories WHERE faq IS NOT NULL AND (faq LIKE '"{%' OR faq LIKE '"[%')) AS bad_category_faq;
