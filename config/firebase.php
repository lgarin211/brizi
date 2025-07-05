<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Firebase settings for your application.
    |
    */

    'database_url' => env('FIREBASE_DATABASE_URL', ''),
    'service_account' => env('FIREBASE_SERVICE_ACCOUNT_PATH', ''),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', ''),

    // Alternatif konfigurasi jika menggunakan credentials JSON
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', ''),
    ],
];
