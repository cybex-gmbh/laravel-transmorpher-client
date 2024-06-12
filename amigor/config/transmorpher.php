<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    // The middleware applied to routes provided by this package. The 'web' middleware is necessary and applied by default.
    'routeMiddleware' => [
//        'auth'
    ],

    'api' => [
        // Optionally, specify the Transmorpher API version which should be used. For supported versions, check the SupportedApiVersion enum.
        'version' => env('TRANSMORPHER_API_VERSION', 1),
        // The API URL used when communicating between servers. Might be useful in situations where, for example, docker containers communicate with each other.
        's2s_url' => env('TRANSMORPHER_S2S_API_BASE_URL', env('TRANSMORPHER_WEB_API_BASE_URL')),
        // The API URL used when making requests to the Transmorpher media server from the web.
        'web_url' => env('TRANSMORPHER_WEB_API_BASE_URL'),
        // The Laravel Sanctum auth token used to authenticate at the Transmorpher media server.
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        // The route the Transmorpher server can use to send signed notifications to.
        'notifications_route' => 'transmorpher/notifications'
    ],

    'delivery' => [
        // The URL used for retrieving derivative images.
        'url' => env('TRANSMORPHER_WEB_DELIVERY_BASE_URL'),
        // A placeholder URL which is used when media doesn't have an upload.
        'placeholder_url' => env('TRANSMORPHER_WEB_PLACEHOLDER_URL', ''),
        'thumbnail' => [
            'transformations' => [
                // Currently only height is configurable.
                'height' => 300,
            ],
        ],
    ],

    'upload' => [
        // Chunk size in mb.
        'chunk_size' => 1 * 1024 * 1024,
        'image' => [
            'validations' => [
                // Max file size in mb.
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
                    // Width to height ratio, e.g. '1:1', '1:2', '16:9', ...
                    // Only integers are allowed.
                    'ratio' => null,
                ],
                'mimetypes' => 'image/*',
            ],
        ],
        'video' => [
            'validations' => [
                // Max file size in mb.
                'max_file_size' => 10000,
                'dimensions' => [
                    'width' => [
                        'min' => null,
                        'max' => null,
                    ],
                    'height' => [
                        'min' => null,
                        'max' => null,
                    ],
                    // Width to height ratio, e.g. '1:1', '1:2', '16:9', ...
                    // Only integers are allowed.
                    'ratio' => null,
                ],
                // Somehow video/* doesn't contain the .mkv mimetype.
                'mimetypes' => 'video/*,video/x-matroska',
            ],
        ],
    ]
];
