<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Cache;

class DingtalkRobot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $robot = array_get($this->data, 'robot', 'robot');
        $jsonData = [
            "at" => [
                'atMobiles' => [
                    // "130xxxx6752", 
                ],
                // 'isAtAll' => false,
            ],
        ];
        $jsonData['msgtype'] = array_get($this->data, 'msgtype', 'text');
        if($jsonData['msgtype'] == 'text'){
            $jsonData['text'] = [
                "content" => implode("\n\n", [
                    now()->format('Y-m-d H:i:s'),
                    array_get($this->data, 'content', '出错了！！！'),
                    now()->format('Y-m-d H:i:s'),
                ]),
            ];
        }else{
            // TODO 其他消息类型处理
        }
        $accessToken = config("dingtalk.access_token.{$robot}");
        if($accessToken){
            $cacheKey = implode(':', [
                'dingtalk',
                'robot',
                md5($accessToken),
            ]);
            $ratelimit = config("dingtalk.ratelimit.{$robot}", 5);
            if(
                empty($ratelimit) ||
                empty(Cache::get($cacheKey))
            ){
                Cache::put($cacheKey, now(), now()->addMinutes($ratelimit));
                // TODO 记录日志？
                app('dingtalk_client')->post(config('dingtalk.path.robot'), [
                    'query' => [
                        'access_token' => $accessToken,
                    ],
                    'json' => $jsonData,
                ]);
            }
        }
    }
}
