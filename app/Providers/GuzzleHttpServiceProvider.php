<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use GuzzleHttp\Client;

class GuzzleHttpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton("guzzle_http_client", function ($laravelApp){
            $app = new Client();
            return $app;
        });
        $this->app->alias("guzzle_http_client", "http_client");

        $this->app->singleton("guzzle_dingtalk_http_client", function ($laravelApp){
            $app = new Client([
                'base_uri' => config('dingtalk.root_url'),
            ]);
            return $app;
        });
        $this->app->alias("guzzle_dingtalk_http_client", "dingtalk_client");
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        // 
    }
}
