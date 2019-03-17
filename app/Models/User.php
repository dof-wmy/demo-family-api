<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasRoles;

    protected $guard_name = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'register_source_id',
        'register_source_type',
        'email',
        'mobile',
        'password',
        'name',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getGuardNameAttribute(){
        return (new self())->guard_name;
    }

    public function setPasswordAttribute($value){
        $this->attributes['password'] = bcrypt(trim($value));
    }

    /**
     * 获得用户注册来源的模型。
     */
    public function register_source()
    {
        return $this->morphTo();
    }
    public function registerSource()
    {
        return $this->register_source();
    }

    public function roleInformation($roleName = ''){
        $model = $this->hasMany(UserRoleInformation::class);
        if($roleName){
            $role = self::getRole($roleName);
            if($role){
                $model->where('role_id', $role->id);
            }
        }
        return $model;
    }

    public function roleAttributes($roleName = ''){
        $model = $this->belongsToMany(RoleAttribute::class);
        if($roleName){
            $role = self::getRole($roleName);
            if($role){
                $model->whereIn('role_attribute_id', RoleAttribute::where('role_id', $role)->pluck('id')->toArray());
            }
        }
        return $model;
    }

    public function feedback(){
        return $this->hasMany(Feedback::class);
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

    static function generateUserName($prefix = ''){
        $username = config('prefix.user');
        $username .= $prefix ?: '';
        $username .= Str::uuid();
        return $username;
    }

    static function getRole($roleName){
        return Role::where([
            'name'       => $roleName,
            'guard_name' => (new self())->guard_name,
        ])->first();
    }
}
