<?php

return [
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
];

