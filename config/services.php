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
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'trackdrive' => [
        'key' => env('TRACKDRIVE_AUTH_TOKEN'),
        'url' => env('TRACKDRIVE_URL'),
        'traffic_sources' => [
            'columns' => 'company_name,user_traffic_source_id,id,calls_count,name',
            'collects' => 'traffic_sources',
            'fields' => 'user_traffic_source_id',
            'repository' => 'App\Repositories\Leads\TrafficSourceRepository',
        ],
        'buyers' => [
            'columns' => 'name,user_buyer_id,id,bid_price',
            'collects' => 'buyers',
            'fields' => 'user_buyer_id',
            'repository' => 'App\Repositories\Leads\BuyerRepository',
        ],
        'offers' => [
            'columns' => 'name,user_offer_id,id',
            'collects' => 'offers',
            'fields' => 'user_offer_id',
            'repository' => 'App\Repositories\Leads\OfferRepository',
        ],
        'did_numbers' => [
            'columns' => 'id,number,offer_id,traffic_source_id,description',
            'collects' => 'numbers',
            'fields' => 'traffic_source_id',
            'repository' => 'App\Repositories\Leads\DidNumberRepository',
        ],
        'smid' => ['MULTI-PLAN_SQ8Q9uepaj2024_C', 'MULT-PLAN_SQRKxIUdWJ2024_C', 'MULT-PLAN_SQCz0C8SmU2024_C', 'MULTI-PLAN_SQ2pApSZgV2024_C'],
        'sub_id' => ['OD89', 'OD99', 'OD10', 'OD53', 'OD12', 'OD11', 'OD09', 'OD03', 'OD74', 'OD62'],
        'pub_id_exception' => [113, 114, 115, 116, 117, 118, 119, 120, 125, 126, 127, 128, 129, 130, 131, 133, 134, 136, 137, 138, 139, 140, 141, 142, 143, 145, 146, 147, 148, 149, 150, 153, 154, 155, 156],
    ],
    'campaign' => [
        1 => [
            'legal' => 'Legal Calls',
            'ACA' => 'ACA Calls',
            'debt' => 'Debt',
            'tax_debt' => 'Tax Debt',
            'MC' => 'Medicare Calls',
            'under_65' => 'Under 65',
        ],
        2 => [
            'legal' => 'MVA Calls',
            'ACA' => 'ACA Calls',
            'debt' => 'Unsecured Debt',
            'tax_debt' => 'Tax Debt',
            'MC' => 'Medicare Calls',
            'under_65' => 'Under 65',
        ],
    ],
    'env' => [
        '1' => [
            'TRACK_DRIVE_SERVICE' => [
                'AUTH_TOKEN' => env('1_TRACK_DRIVE_SERVICE_AUTH_TOKEN'),
            ],
        ],

        '2' => [
            'TRACK_DRIVE_SERVICE' => [
                'AUTH_TOKEN' => env('2_TRACK_DRIVE_SERVICE_AUTH_TOKEN'),
            ],
        ],
    ],
];
