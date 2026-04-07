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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_PHONE_NUMBER'),
        'template_sid' => env('TWILIO_TEMPLATE_SID'),
    ],
    'netsuite' => [
        'account'         => env('NETSUITE_ACCOUNT'),
        'base_url'        => env('NETSUITE_BASE_URL'),
        'consumer_key'    => env('NETSUITE_CONSUMER_KEY'),
        'consumer_secret' => env('NETSUITE_CONSUMER_SECRET'),
        'token_key'       => env('NETSUITE_TOKEN_KEY'),
        'token_secret'    => env('NETSUITE_TOKEN_SECRET'),
        'script_payment'  => env('NETSUITE_SCRIPT_ID_PAYMENT'),
    ],
    'hikcentral' => [
        'app_key'    => env('HIKVISION_APP_KEY'),
        'app_secret' => env('HIKVISION_APP_SECRET'),
        'host'       => env('HIKVISION_HOST'),
    ],
    'ai' => [
        'api_key' => env('AI_API_KEY'),
        'api_url' => env('AI_API_URL'),
        'model' => env('AI_MODEL'),
    ],

];
