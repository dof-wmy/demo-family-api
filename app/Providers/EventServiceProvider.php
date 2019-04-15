<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        'App\Events\AnnouncementPublished' => [
            'App\Listeners\UserNotice',
        ],

        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            'SocialiteProviders\\Weibo\\WeiboExtendSocialite@handle',
            'SocialiteProviders\\Weixin\\WeixinExtendSocialite@handle',
            'SocialiteProviders\\WeixinWeb\\WeixinWebExtendSocialite@handle',
            'SocialiteProviders\\QQ\\QqExtendSocialite@handle',
        ],

        \App\Events\SocialiteLoginSuccess::class => [
            'App\Listeners\SocialiteLogin',
        ],

    ];
    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        'App\Listeners\WechatUserEventSubscriber',
    ];
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
