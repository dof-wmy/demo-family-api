<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Channels\NoticeChannel;

class UserNotice extends Notification
{
    use Queueable;

    public $user;
    public $data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [
            UserNotice::class,
        ];
    }

    public function toNotice($notifiable){
        $this->user->notices()->create([
            'title'   => array_get($this->data, 'title', '系统提醒'),
            'content' => array_get($this->data, 'content', ''),
        ]);
    }
}
