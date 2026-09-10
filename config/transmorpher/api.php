<?php

return [
    /** Optionally, specify the Transmorpher API version which should be used. For supported versions, check the {@link \Transmorpher\Enums\SupportedApiVersion} enum. */
    'version' => env('TRANSMORPHER_API_VERSION', 2),

    // The API URL used when communicating between servers. Might be useful in situations where, for example, docker containers communicate with each other.
    's2s' => [
        'url' => env('TRANSMORPHER_S2S_API_BASE_URL', env('TRANSMORPHER_WEB_API_BASE_URL')),
    ],

    // The API URL used when making requests to the Transmorpher media server from the web.
    'web' => [
        'url' => env('TRANSMORPHER_WEB_API_BASE_URL'),
    ],

    // The Laravel Sanctum auth token used to authenticate at the Transmorpher media server.
    'auth' => [
        'token' => env('TRANSMORPHER_AUTH_TOKEN'),
    ],
];

