<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'resend' => [
        'key' => env('RESEND_KEY'),
    ],
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'marketradar' => [
        'feed_url' => env('MARKETRADAR_FEED_URL', 'https://feed.marketradar.kz/satu/bF2rBm3rBUmZwUW6HHBHU7lyWTlo60gv/0c72f008-e286-4785-a903-2941d6360943.xml'),
    ],
];
