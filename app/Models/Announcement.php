<?php

namespace App\Models;

use Carbon\Carbon;

class Announcement extends Base
{
    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
    ];

    public function users(){
        return $this->belongsToMany(User::class);
    }

    public function notices()
    {
        return $this->morphMany(Notice::class);
    }

    public function publish(){
        $this->publish_at = Carbon::now();
        $this->save();
        event(new \App\Events\AnnouncementPublished($this));
    }
}
