<?php

namespace App\Jobs\Wechat;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\WechatUser;

class WechatUserInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    protected $wechatAppConfig;
    protected $wechatUserInfo;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($wechatAppConfig, $wechatUserInfo)
    {
        $this->wechatAppConfig = $wechatAppConfig;
        $this->wechatUserInfo = $wechatUserInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $wechatUser = WechatUser::firstOrCreate([
            'app_type' => $this->wechatAppConfig->app_type, // config/wechat.php 文件里每个账户里要单独配置 app_type，否则根据环境变量取值
            'app_id'   => $this->wechatAppConfig->app_id,
            'openid'   => $this->wechatUserInfo['openid'],
        ]);
        foreach([
            'unionid',
        ] as $field){
            if(empty($wechatUser->$field)){
                $wechatUser->$field = array_get($this->wechatUserInfo, $field);
            }
        }
        foreach([
            'nickname',
            'headimgurl',
        ] as $field){
            if(!empty($fieldValue = array_get($this->wechatUserInfo, $field))){
                $wechatUser->$field = $fieldValue;
            }
        }
        $wechatUser->detail = $this->wechatUserInfo;
        $wechatUser->save();
    }
}
