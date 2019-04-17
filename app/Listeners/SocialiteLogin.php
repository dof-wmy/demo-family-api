<?php

namespace App\Listeners;

use App\Events\SocialiteLoginSuccess;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SocialiteLogin
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
     * @param  SocialiteLoginSuccess  $event
     * @return void
     */
    public function handle(SocialiteLoginSuccess $event)
    {
        //
    }
}
