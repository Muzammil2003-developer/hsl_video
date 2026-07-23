<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for setting this information for your application.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'streaming' => [
        /*
         * RTMP server URL for OBS Studio to push streams to.
         * For production: rtmp://your-server-ip:1935
         */
        'rtmp_server' => env('RTMP_SERVER', 'rtmp://localhost:1935'),

        /*
         * RTMP application name (default: "live").
         * In Nginx RTMP this is the "application" block name.
         */
        'rtmp_app' => env('RTMP_APP', 'live'),

        /*
         * HLS base URL where the streaming server serves HLS playlists.
         * For production: http://your-server-ip:8080
         * MediaMTX default: http://localhost:8888
         * Nginx RTMP + nginx-ts-module: http://localhost:8080/hls
         */
        'hls_base_url' => env('HLS_BASE_URL', 'http://localhost:8888'),

        /*
         * Secret key for webhook authentication between streaming server and Laravel.
         * Set this to a random string in production.
         */
        'webhook_secret' => env('STREAMING_WEBHOOK_SECRET', ''),
    ],
];