<?php

namespace App\Models;

class Notice extends Base
{
    public function user(){
        $this->belongsTo(User::class);
    }
}
