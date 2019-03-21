<?php

namespace App\Listeners;

use App\Jobs\Wechat\WechatUserOpenid;

class WechatUserEventSubscriber
{
    /**
     * 处理获取用户列表。
     */
    public function onUserList($event) {
        foreach($event->openids as $openid){
            WechatUserOpenid::dispatch($event->wechatApp->config, $openid)->onQueue('wechat_user');
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