<?php

namespace App\Jobs\Wechat;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\WechatUser;

class WechatUserOpenid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    protected $wechatApp;
    protected $openid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($wechatApp, $openid)
    {
        $this->wechatApp = $wechatApp;
        $this->openid = $openid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $wechatUser = WechatUser::firstOrCreate([
            'app_type' => $this->wechatApp->config->app_type, // config/wechat.php 文件里每个账户里要单独配置 app_type，否则根据环境变量取值
            'app_id'   => $this->wechatApp->config->app_id,
            'openid'   => $this->openid,
        ]);
    }
}
