<?php

return [
    'client_name' => env('TRANSMORPHER_CLIENT_NAME'),

    'api' => [
        'url' => env('TRANSMORPHER_API_URL'),
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        'callback_route' => 'transmorpher/callback'
    ],

    'public' => [
        'url' => env('TRANSMORPHER_PUBLIC_URL')
    ],
];
