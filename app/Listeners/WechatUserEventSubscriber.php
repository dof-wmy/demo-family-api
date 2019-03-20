<?php

namespace App\Listeners;

use App\Models\WechatUser;

class WechatUserEventSubscriber
{
    /**
     * 处理获取用户列表。
     */
    public function onUserList($event) {
        foreach($event->openids as $openid){
            $wechatUser = WechatUser::firstOrCreate([
                'app_type' => $event->wechatApp->config->app_type,
                'app_id'   => $event->wechatApp->config->app_id,
                'openid'   => $openid,
            ]);
        }
    }

    /**
     * 为订阅者注册监听器
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\Wechat\UserList',
            'App\Listeners\WechatUserEventSubscriber@onUserList'
        );
    }
}