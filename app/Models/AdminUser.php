<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class AdminUser extends Authenticatable implements JWTSubject
{
    use HasRoles;

    protected $guard_name = 'admin_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'password', 'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getGuardNameAttribute(){
        return (new self())->guard_name;
    }

    public function setPasswordAttribute($value){
        $this->attributes['password'] = bcrypt(trim($value));
    }

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'guard_name'    => $this->guard_name,
            'username'      => $this->username,
        ];
    }

    static function fieldExist($fieldValue = '', $fieldName = 'username'){
        $model = new self();
        return self::where($fieldName, $fieldValue)->first([
            $model->primaryKey,
        ]) ? true : false;
    }
}
