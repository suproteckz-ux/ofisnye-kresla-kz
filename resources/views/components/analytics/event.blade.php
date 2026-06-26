@props(['payload', 'onceKey' => null])
<script>
window.dataLayer = window.dataLayer || [];
window.okAnalytics = window.okAnalytics || {
  pushed: {},
  push: function(payload) { window.dataLayer.push(payload); },
  pushOnce: function(key, payload) {
    if (!key || this.pushed[key]) return;
    this.pushed[key] = true;
    window.dataLayer.push(payload);
  }
};
window.okAnalytics.pushOnce(@json($onceKey ?: (($payload['event'] ?? 'event') . ':' . request()->fullUrl())), @json($payload));
</script>
