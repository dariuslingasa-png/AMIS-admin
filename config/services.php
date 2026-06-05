<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'microsoft' => [
        'client_id'      => env('MICROSOFT_CLIENT_ID'),
        'client_secret'  => env('MICROSOFT_CLIENT_SECRET'),
        'tenant_id'      => env('MICROSOFT_TENANT_ID'),
        'admin_upn'      => env('MICROSOFT_ADMIN_UPN'),
        'admin_password' => env('MICROSOFT_ADMIN_PASSWORD'),
        'redirect_uri'   => env('MICROSOFT_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/microsoft/callback'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/settings/google/callback'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    ],

    'enrollment_storage_url' => env('ENROLLMENT_STORAGE_URL'),
    'student_portal_url' => env('STUDENT_PORTAL_URL', env('APP_URL')),

    'school' => [
        'year'                  => env('SCHOOL_YEAR', '2026-2027'),
        'previous_year'         => env('SCHOOL_PREVIOUS_YEAR', '2025-2026'),
        'enrollment_fee'        => (float) env('SCHOOL_ENROLLMENT_FEE', 4000),
        'finance_reviewer_name' => env('FINANCE_REVIEWER_NAME', 'Finance Office'),
        'finance_checked_by'    => env('FINANCE_CHECKED_BY', 'System / Finance'),
        'address'               => env('SCHOOL_ADDRESS', 'Bugac Ma-a Road, Davao City'),
        'email'                 => env('SCHOOL_EMAIL', 'almunawwaraislamicschool@gmail.com'),
        'soa_preview_date'      => env('SOA_PREVIEW_DATE'),
        'invoice_id_offset'     => (int) env('INVOICE_ID_OFFSET', 203),
    ],

];
