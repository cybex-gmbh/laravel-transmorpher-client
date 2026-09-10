<?php

return [
    // Chunk size in mb.
    // If the server is configured to use S3-Multi-Part uploads, values lower than 5MiB will automatically be set to 5MiB.
    'chunk_size' => 5 * 1024 * 1024,
];

