<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    'api' => [
        's2s_url' => env('TRANSMORPHER_S2S_API_URL', env('TRANSMORPHER_WEB_API_URL')),
        'web_url' => env('TRANSMORPHER_WEB_API_URL'),
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        'callback_base_url' => env('TRANSMORPHER_CALLBACK_BASE_URL', env('APP_URL')),
        'callback_route' => 'transmorpher/callback'
    ],

    'delivery' => [
        'url' => env('TRANSMORPHER_DELIVERY_URL'),
        'placeholder_url' => env('TRANSMORPHER_PLACEHOLDER_URL')
    ],

    'dropzone_upload' => [
        'chunk_size' => 1 * 1024 * 1024,
        'image_max_file_size' => 100,
        'video_max_file_size' => 4000,
    ]
];
