<?php
return [
    'key' => env('B2_PHOTO_KEY_ID', env('B2_KEY_ID')),
    'secret' => env('B2_PHOTO_APPLICATION_KEY', env('B2_APPLICATION_KEY')),
    'bucket' => env('B2_PHOTO_BUCKET_NAME'),
    'preview_bucket' => env('B2_PHOTO_PREVIEW_BUCKET_NAME', env('B2_PHOTO_BUCKET_NAME')),
    'endpoint' => env('B2_ENDPOINT'),
    'region' => env('B2_REGION'),
    'part_bytes' => 64 * 1024 * 1024,
    'single_put_bytes' => 16 * 1024 * 1024,
    'max_bytes' => 100 * 1024 * 1024 * 1024,
];
