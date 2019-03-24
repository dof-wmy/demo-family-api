<?php

namespace App\Listeners;

use App\Events\AnnouncementPublished;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\User;

class UserNotice
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AnnouncementPublished  $event
     * @return void
     */
    public function handle(AnnouncementPublished $event)
    {
        foreach(User::get() as $user){
            \App\Jobs\UserNotice::dispatch($user, [
                'source_id'   => $event->announcement->id,
                'source_type' => 'App\Models\Announcement',
                'title'       => $event->announcement->title,
                'content'     => $event->announcement->content,
            ]);
        }
    }
}
