<?php

return [
    'export_path' => env('EXPORT_PATH', 'exports'),
    'file_available_in' => env('EXPORT_FILE_AVAILABLE_IN', 86400),
    'chunk_large_collections_size'=> env('EXPORT_CHUNK_COLLECTION', 10000),
];
