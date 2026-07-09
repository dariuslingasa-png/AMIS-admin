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
        'faculty_sku_id' => env('AZURE_FACULTY_SKU_ID', '94763226-9b3c-4e75-a931-5c89701abe66'),
        'faculty_a3_sku_id' => env('AZURE_FACULTY_A3_SKU_ID', 'e578b273-6db4-4691-bba0-8d691f4da603'),
        'faculty_a5_sku_id' => env('AZURE_FACULTY_A5_SKU_ID', '0287b9a5-aa37-4a9f-a42f-dc90ec7f3334'),
        'student_sku_id' => env('AZURE_STUDENT_SKU_ID', '314c4481-f395-4525-be8b-2ec4bb1e9d91'),
    ],

    'enrollment_storage_url' => env('ENROLLMENT_STORAGE_URL'),
    'student_portal_url' => env('STUDENT_PORTAL_URL', env('APP_URL')),

    'ebook' => [
        'url' => env('EBOOK_PORTAL_URL', 'http://127.0.0.1:8003'),
        'sso_secret' => env('SSO_SECRET'),
    ],

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
        'or_prefix'             => env('SCHOOL_OR_PREFIX', 'OR-'),
        'or_excess_suffix'      => env('SCHOOL_OR_EXCESS_SUFFIX', 'OR-EXCESS'),
        'academic_maintenance'  => env('ACADEMIC_MAINTENANCE', true),
    ],

    'facebook' => [
        'page_access_token' => env('MESSENGER_PAGE_ACCESS_TOKEN'),
        'verify_token'      => env('MESSENGER_VERIFY_TOKEN'),
    ],

    'google_drive' => [
        'client_id'     => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_id'     => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],

];
