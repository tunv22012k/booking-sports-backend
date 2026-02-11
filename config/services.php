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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
        'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Supported providers: "local", "s3", "r2", "google-drive"
    |
    */
    'file_upload' => [
        'provider' => env('FILE_UPLOAD_PROVIDER', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare R2 Configuration
    |--------------------------------------------------------------------------
    */
    'cloudflare' => [
        'r2_public_url' => env('CLOUDFLARE_R2_PUBLIC_URL'),
    ],

];
