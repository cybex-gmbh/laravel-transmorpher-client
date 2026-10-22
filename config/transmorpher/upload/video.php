<?php

return [
    'validations' => [
        'max_file_size_mb' => 10000,
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
        // Somehow video/* doesn't contain the .mkv mimetype.
        'mimetypes' => 'video/*,video/x-matroska',
    ],
];

