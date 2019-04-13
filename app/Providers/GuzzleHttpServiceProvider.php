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
