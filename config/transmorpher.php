<?php

return [
    'client_name' => 'Marco',

    'api' => [
        'url'        => 'http://transmorpher/api',
        'auth_token' => env('TRANSMORPHER_AUTH_TOKEN'),
        'callback_route' => 'transmorpher/callback'
    ],

    'public' => [
        'url' => 'http://transmorpher'
    ],
];
