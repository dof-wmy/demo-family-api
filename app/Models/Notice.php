<?php

namespace App\Models;

class Notice extends Base
{
    protected $fillable = [
        'source_id',
        'source_type',
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
