@php
$settings   = \App\Services\CacheService::settings();
$schemaData = [
    '@context' => 'https://schema.org',
    '@type'    => 'LocalBusiness',
    'name'     => config('app.name'),
    'url'      => config('app.url'),
    'description' => 'Интернет-магазин офисных кресел в Алматы. Кресла для руководителей, эргономичные, компьютерные, игровые кресла. Доставка по Казахстану.',
    'address'  => [
        '@type'           => 'PostalAddress',
        'addressLocality' => 'Алматы',
        'addressCountry'  => 'KZ',
        'streetAddress'   => $settings['address'] ?? 'г. Алматы',
    ],
    'telephone' => $settings['phone'] ?? '',
    'email'     => $settings['email'] ?? '',
    'openingHoursSpecification' => [
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'], 'opens' => '09:00', 'closes' => '18:00'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '10:00', 'closes' => '16:00'],
    ],
    'priceRange' => '₸₸',
    'currenciesAccepted' => 'KZT',
    'image' => asset('img/og-default.jpg'),
    'logo'  => asset('img/logo.png'),
    '@id'   => config('app.url'),
];
$schemaOrg = [
    '@context' => 'https://schema.org',
    '@type'    => 'Organization',
    'name'     => config('app.name'),
    'url'      => config('app.url'),
    'logo'     => asset('img/logo.png'),
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => $settings['phone'] ?? '',
        'contactType' => 'customer service',
        'availableLanguage' => ['Russian', 'Kazakh'],
        'areaServed' => 'KZ',
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($schemaData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}</script>
<script type="application/ld+json">{!! json_encode($schemaOrg,  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
