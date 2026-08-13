<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => env('FILESYSTEM_SERVE', false),
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Parent-uploaded payment proofs are created by the Family Payment
         * application. Keep this disk read-only in practice: the Admin Portal
         * only streams the original file for Finance review.
         */
        'family_payment_receipts' => [
            'driver' => 'local',
            'root' => env('FAMILY_PAYMENT_RECEIPTS_PATH')
                ?: (is_dir('/home2/amisdavc/afps.amis.edu.ph/storage/app')
                    ? '/home2/amisdavc/afps.amis.edu.ph/storage/app'
                    : base_path('../amis_payment/storage/app')),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'afps_storage' => [
            'driver' => 'local',
            'root' => env('AFPS_STORAGE_PATH')
                ?: (is_dir('/home2/amisdavc/afps.amis.edu.ph/storage/app')
                    ? '/home2/amisdavc/afps.amis.edu.ph/storage/app'
                    : base_path('../amis_payment/storage/app')),
            'throw' => false,
            'report' => false,
        ],

        'ebook_private' => [
            'driver' => 'local',
            'root' => env(
                'EBOOK_STORAGE_PATH',
                is_dir(base_path('../ebook.amis.edu.ph/storage/app/private'))
                    ? base_path('../ebook.amis.edu.ph/storage/app/private')
                    : base_path('../amis_ebook/storage/app/private')
            ),
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
