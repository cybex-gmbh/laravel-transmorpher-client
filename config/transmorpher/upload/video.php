<?php

return [
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
];

