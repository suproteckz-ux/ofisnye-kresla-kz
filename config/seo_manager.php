<?php

return [
    'write_token_hash' => env('SEO_MANAGER_WRITE_TOKEN_SHA256', ''),
    'read_token_hash' => env('SEO_MANAGER_READ_TOKEN_SHA256', ''),
    'allow_local_http' => (bool) env('SEO_MANAGER_ALLOW_LOCAL_HTTP', false),
    'site_id' => 'office_chairs',
    'public_url' => env('SEO_MANAGER_PUBLIC_URL', 'https://xn----8sbnbmoilhzgg4a5h.kz'),
];
