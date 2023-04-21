<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    'api' => [
        's2s_url' => env('TRANSMORPHER_S2S_API_URL', env('TRANSMORPHER_WEB_API_URL')),
        'web_url' => env('TRANSMORPHER_WEB_API_URL'),
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        'callback_route' => 'transmorpher/callback'
    ],

    'delivery' => [
        'url' => env('TRANSMORPHER_DELIVERY_URL'),
        'placeholder_url' => env('TRANSMORPHER_PLACEHOLDER_URL')
    ],
];
