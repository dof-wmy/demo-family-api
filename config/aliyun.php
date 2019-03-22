<?php
return [
    'access_key_id' => env('ALIYUN_ACCESS_KEY_ID', ''),
    'access_key_secret' => env('ALIYUN_ACCESS_KEY_SECRET', ''),
    'region_id' => env('ALIYUN_REGION_ID', ''),

    'log' => [
        'level' => env('ALIYUN_LOG_LEVEL', 'debug'),
    ],

    'product' => [
        // 号码隐私保护(Phone Number Protection)
        'pls' => [
            'name'    => 'Dyplsapi',
            'default' => '95axn',
        ],
    ],
];
