<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;

class Base extends Model
{
    public function getCreatedAtAttribute($value){
        return $value ? Carbon::parse($value)->format('Y-m-d H:i') : null;
    }
}
