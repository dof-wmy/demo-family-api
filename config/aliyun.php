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
            'product' => 'Dyplsapi',
            'scheme'  => 'https', //http
            'version' => '2017-05-25',
            'action'  => 'BindAxn',
            'method'  => 'POST',
            'options'   => [
                'query' => [
                    'PoolKey'    => env('ALIYUN_PLS_POOL_KEY'), // 必填：号池Key
                    'PhoneNoA'   => '', // 必填：AXN中的A号码，A号码支持固话(区号后面不需要连字符”-“)
                    'Expiration' => '', // 必填：绑定关系的过期时间
                    // 'PhoneNoB'   => '',	// 选填：AXN中的默认的B号码 ，B号码支持固话(区号后面不需要连字符”-“)
                    'PhoneNoX'   => env('ALIYUN_PLS_PHONE_NO_X'), // 选填：指定X号码进行绑定
                    // 'ExpectCity' => '', // 选填：不需要带地市的后缀，指定城市进行X号码的选号,如果当前号池中没有该城市的可用号码将随机分配其他城市的号码,也可以配置成严格模式，不存在符合条件的号码时提示分配错误
                    // 'IsRecordingEnabled' => false, // 选填：true和false	是否需要针对该绑定关系产生的所有通话录制通话录音
                    // 'OutId' => '', // 选填：外部业务扩展字段
                ],
            ],
            'request' => null,
        ],
    ],
];
