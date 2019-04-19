<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Cache;

class SocialiteLoginSuccess implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $socialiteUser;
    private $state;
    public $code;
    public $users;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($socialiteUser, $state)
    {
        $this->socialiteUser = $socialiteUser;
        $this->state = $state;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $socialitableRelation = array_get($this->state, 'socialitableRelation');
        $socialitableId = array_get($this->state, 'socialitableId');
        $socialiteUserReflector = new \ReflectionObject($this->socialiteUser); 
        if($socialitableRelation && $socialiteUserReflector->hasMethod($socialitableRelation)){
            if($socialitableId){
                $socialitableRelationUser = $this->socialiteUser->$socialitableRelation()->where([
                    'socialitable_id' => $socialitableId,
                ])->first();
                if($socialitableRelationUser){
                    $channelName = $socialitableRelationUser->pusherChannelName();
                    return new Channel($channelName);
                }
            }
            $users = $this->socialiteUser->$socialitableRelation()->orderBy('id', 'desc')->get();
            $this->users = $users->map(function($user){
                return $user->only([
                    'id',
                    'username',
                    'name',
                ]);
            });
            $encryptedData = encrypt([
                'users' => $users,
            ]);
            $this->code = 'socialite-' . md5($encryptedData);
            Cache::remember($this->code, 120, function() use($encryptedData){
                return $encryptedData;
            });
        }
        return new Channel(array_get($this->state, 'pusherChannelName', time()));
        // return new PrivateChannel(array_get($this->state, 'pusherChannelName', time()));
    }
}
