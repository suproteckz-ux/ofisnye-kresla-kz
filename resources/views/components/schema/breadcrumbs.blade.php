@props(['items' => []])

@php
    $crumbs = array_merge(
        [['name' => 'Главная', 'url' => url('/')]],
        $items
    );
    $crumbs = array_values(array_filter($crumbs, fn($c) => !empty($c['name'])));

    // Собираем JSON программно — избегаем "@context","@type" внутри Blade
    if (count($crumbs) > 1) {
        $schemaItems = [];
        foreach ($crumbs as $index => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $crumb['name'],
            ];
            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $schemaItems[] = $item;
        }
        $schemaData = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $schemaItems,
        ];
    }
@endphp

@if(count($crumbs) > 1)
<script type="application/ld+json">
{!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
