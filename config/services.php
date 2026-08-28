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

    'qaqueue' => [
        'base_url' => env('QAQUEUE_BASE_URL', 'http://host.docker.internal:8008'),
        'timeout_seconds' => env('QAQUEUE_TIMEOUT_SECONDS', 10),
    ],

    'projects_registry' => [
        'base_url' => env('PROJECTS_API_BASE_URL', 'http://localhost:8083'),
        'token' => env('PROJECTS_API_TOKEN'),
        'timeout_seconds' => env('PROJECTS_API_TIMEOUT_SECONDS', 15),
        'webhook_token' => env('PROJECTS_REGISTRY_WEBHOOK_TOKEN'),
        'sync_lag_warning_minutes' => env('PROJECTS_SYNC_LAG_WARNING_MINUTES', 20),
    ],

    'writersroom_sync' => [
        'token' => env('DEVBACKLOG_SYNC_TOKEN'),
    ],

    'chatprojects' => [
        'webhook_token' => env('CHATPROJECTS_WEBHOOK_TOKEN'),
    ],

];
