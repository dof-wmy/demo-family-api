<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        AlibabaCloud::accessKeyClient(config('aliyun.access_key_id'), config('aliyun.access_key_secret'))
            ->regionId(config('aliyun.region_id'))
            ->asGlobalClient();

        $this->app->singleton("alibaba_cloud", function ($laravelApp){
            $app = AlibabaCloud::rpcRequest();
            return $app;
        });
        $this->app->alias("alibaba_cloud", "aliyun");
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
