<script>
window.dataLayer = window.dataLayer || [];
window.okAnalytics = window.okAnalytics || {
  pushed: {},
  push: function(payload) {
    window.dataLayer.push(payload);
  },
  pushOnce: function(key, payload) {
    if (!key || this.pushed[key]) return;
    this.pushed[key] = true;
    window.dataLayer.push(payload);
  }
};

document.addEventListener('click', function(event) {
  var whatsapp = event.target.closest('a[href*="wa.me"], a[href*="api.whatsapp.com"]');
  if (whatsapp) {
    window.okAnalytics.push({
      event: 'click_whatsapp',
      click_location: whatsapp.dataset.analyticsLocation || 'site',
      product_sku: whatsapp.dataset.productSku || undefined,
      product_name: whatsapp.dataset.productName || undefined
    });
  }

  var phone = event.target.closest('a[href^="tel:"]');
  if (phone) {
    window.okAnalytics.push({
      event: 'click_phone',
      click_location: phone.dataset.analyticsLocation || 'site',
      phone: phone.dataset.phone || phone.getAttribute('href').replace('tel:', '')
    });
  }

  var card = event.target.closest('[data-product-card]');
  if (card && !event.target.closest('.product-card__wa')) {
    try {
      var payload = JSON.parse(card.dataset.analyticsSelectItem || '{}');
      if (payload.event) window.okAnalytics.push(payload);
    } catch (error) {}
  }
}, true);

document.addEventListener('submit', function(event) {
  var form = event.target.closest('form[data-analytics-search]');
  if (!form) return;

  var input = form.querySelector('input[name="q"]');
  var value = input ? input.value.trim() : '';
  if (value) {
    window.okAnalytics.push({ event: 'search', search_term: value });
  }
}, true);
</script>
