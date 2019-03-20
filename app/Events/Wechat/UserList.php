<?php

namespace App\Events\Wechat;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserList
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wechatApp;
    public $openids;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($wechatApp, $openids)
    {
        $this->wechatApp = $wechatApp;
        $this->openids = $openids;
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
