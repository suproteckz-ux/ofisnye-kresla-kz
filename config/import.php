<?php
return [
    'xml_url' => env('IMPORT_XML_URL', ''),
    'chunk_size' => env('IMPORT_CHUNK_SIZE', 50),
    'download_images' => env('IMPORT_DOWNLOAD_IMAGES', true),
    'image_max_size' => 10 * 1024 * 1024, // 10MB
    'webp_quality' => 85,
    'image_max_dimension' => 1200,
];
