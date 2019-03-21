<?php

namespace App\Events\Wechat;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserInfoList
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wechatApp;
    public $wechatUsers;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($wechatApp, $wechatUsers)
    {
        $this->wechatApp = $wechatApp;
        $this->wechatUsers = $wechatUsers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
