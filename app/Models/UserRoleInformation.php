<?php

namespace App\Models;

class UserRoleInformation extends Base
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'key',
        'value',
    ];
}
