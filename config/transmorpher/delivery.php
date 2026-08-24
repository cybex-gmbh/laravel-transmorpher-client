<?php

return [
    // The URL used for retrieving derivative images.
    'url' => env('TRANSMORPHER_WEB_DELIVERY_BASE_URL'),

    // A placeholder URL to an image which is used when media doesn't have an upload.
    'placeholder' => [
        'url' => env('TRANSMORPHER_WEB_PLACEHOLDER_URL', ''),
    ],

    'thumbnail' => [
        'transformations' => [
            // Currently only height is configurable.
            'height' => 300,
        ],
    ],
];

