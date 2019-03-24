<?php

namespace App\Models;

class Notice extends Base
{
    protected $fillable = [
        'title',
        'content',
    ];

    public function source()
    {
        return $this->morphTo();
    }

    public function user(){
        $this->belongsTo(User::class);
    }
}
