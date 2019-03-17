<?php

namespace App\Models;

class Notice extends Base
{
    protected $fillable = [
        'title',
        'content',
    ];

    public function user(){
        $this->belongsTo(User::class);
    }
}
