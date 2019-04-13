<?php
return [
    // https://open-doc.dingtalk.com/microapp/serverapi2
    'root_url' => 'https://oapi.dingtalk.com',
    'path' => [
        'robot' => 'robot/send',
    ],
    'access_token' => [
        'robot' => env('DINGTALK_ROBOT_ACCESS_TOKEN'),
    ],
];