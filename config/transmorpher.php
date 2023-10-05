<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    'api' => [
        // Optionally specify the Transmorpher API version which should be used. For supported versions check the SupportedApiVersion enum.
        'version' => env('TRANSMORPHER_API_VERSION', 1),
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
        'placeholder_url' => env('TRANSMORPHER_WEB_PLACEHOLDER_URL', '')
    ],

    'upload' => [
        'chunk_size' => 1 * 1024 * 1024,
        'image' => [
            'validations' => [
                'max_file_size' => 100,
                'dimensions' => [
                    'width' => [
                        'min' => null,
                        'max' => null,
                    ],
                    'height' => [
                        'min' => null,
                        'max' => null,
                    ],
                    // Width to height ratio, e.g. 1/1, 1/2, 16/9, ...
                    'ratio' => null,
                ],
                'mimetypes' => 'image/*',
            ],
        ],
        'video' => [
            'validations' => [
                'max_file_size' => 4000,
                'dimensions' => [
                    'width' => [
                        'min' => null,
                        'max' => null,
                    ],
                    'height' => [
                        'min' => null,
                        'max' => null,
                    ],
                    // Width to height ratio, e.g. 1/1, 1/2, 16/9, ...
                    'ratio' => null,
                ],
                // Somehow video/* doesn't contain the .mkv mimetype.
                'mimetypes' => 'video/*,video/x-matroska',
            ],
        ],
    ]
];
