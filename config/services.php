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

    'datafast' => [
        'website_id' => env('DATAFAST_WEBSITE_ID', 'dfid_ptn1lUVSeioDs7nnO9l1e'),
        'domain' => env('DATAFAST_DOMAIN', 'treinprikker.nl'),
        // Server-side reporting of AI crawlers and search bots; on by default in production.
        'bot_tracking' => env('DATAFAST_BOT_TRACKING', env('APP_ENV') === 'production'),
        'bot_token' => env('DATAFAST_BOT_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
