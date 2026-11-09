<?php

return [
    'validations' => [
        'max_file_size_mb' => 100,
        'dimensions' => [
            'width' => [
                'min' => null,
                'max' => null,
            ],
            'height' => [
                'min' => null,
                'max' => null,
            ],
            // Width to height ratio as string, e.g. '1:1', '1:2', '16:9', ...
            'ratio' => null,
        ],
        'mimetypes' => 'image/*',
    ],
];

