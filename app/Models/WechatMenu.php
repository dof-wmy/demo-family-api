<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WechatMenu extends Model
{
    public function getDataAttribute($value){
        return json_decode($value, true);
    }

    public function setDataAttribute($value){
        $this->attributes['data'] = json_encode($value);
    }
}
