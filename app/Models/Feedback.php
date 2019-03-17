<?php

namespace App\Models;

class Feedback extends Base
{
    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'content',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}