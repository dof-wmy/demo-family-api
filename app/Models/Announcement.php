<?php

namespace App\Models;

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
}
