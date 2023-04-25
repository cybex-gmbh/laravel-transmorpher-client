<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    'api' => [
        // The API URL used when communicating between servers. Might be useful in situations where for example docker containers communicate with each other.
        's2s_url' => env('TRANSMORPHER_S2S_API_BASE_URL', env('TRANSMORPHER_WEB_API_BASE_URL')),
        // The API URL used when making requests to the Transmorpher media server from the web.
        'web_url' => env('TRANSMORPHER_WEB_API_BASE_URL'),
        // The Laravel Sanctum auth token used to authenticate at the Transmorpher media server.
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        // The callback base url to which the Transmorpher media server sends information after transcoding a video. Useful in situations where for example docker containers communicate with each other.
        'callback_base_url' => env('TRANSMORPHER_S2S_CALLBACK_BASE_URL', env('APP_URL')),
        'callback_route' => 'transmorpher/callback'
    ],

    'delivery' => [
        // The URL used for retrieving derivative images.
        'url' => env('TRANSMORPHER_WEB_DELIVERY_BASE_URL'),
        // A placeholder URL which is used when media doesn't have an upload.
        'placeholder_url' => env('TRANSMORPHER_PLACEHOLDER_URL')
    ],

    'dropzone_upload' => [
        'chunk_size' => 1 * 1024 * 1024,
        'image_max_file_size' => 100,
        'video_max_file_size' => 4000,
    ]
];
