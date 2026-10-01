<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Spark Plan (Free Tier) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi integrasi Firebase versi gratis (Spark Plan):
    | - Firebase Cloud Messaging (FCM): 100% gratis tanpa batasan kuota pesan push.
    | - Cloud Firestore: Kuota gratis 1 GB storage, 50k reads, 20k writes per hari.
    | - Cloud Storage: Kuota gratis 5 GB storage, 1 GB/hari transfer download.
    | - Firebase Hosting: Kuota gratis 10 GB storage, 360 MB/hari transfer data.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', 'layanan-publik-warga'),

    'client' => [
        'api_key' => env('FIREBASE_API_KEY', ''),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', ''),
        'database_url' => env('FIREBASE_DATABASE_URL', ''),
        'project_id' => env('FIREBASE_PROJECT_ID', 'layanan-publik-warga'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', ''),
        'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', ''),
        'app_id' => env('FIREBASE_APP_ID', ''),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID', ''),
        'vapid_key' => env('FIREBASE_VAPID_KEY', ''),
    ],

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
    ],

    'fcm' => [
        'http_v1_endpoint' => 'https://fcm.googleapis.com/v1/projects/'.env('FIREBASE_PROJECT_ID', 'layanan-publik-warga').'/messages:send',
    ],

];
