<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminUser extends Authenticatable implements JWTSubject
{
    use HasRoles, SoftDeletes;

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

    /**
     * 需要被转换成日期的属性。
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

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

    public function roleText($role){
        return __("role.{$this->guard_name}.{$role->name}");
    }

    public function permissionText($permission){
        return __("permission.{$this->guard_name}.{$permission->name}");
    }

    static function guardName(){
        return (new self())->guard_name;
    }

    static function roleModel(){
        return Role::where('guard_name', (new self())->guard_name);
    }

    static function permissionModel(){
        return Permission::where('guard_name', (new self())->guard_name);
    }

    static function groupOptions(){
        $guardName = self::guardName();
        return self::roleModel()->get()->map(function($role) use($guardName){
            $role->value = $role->id;
            $role->text = __("role.{$guardName}.{$role->name}");
            return $role->only([
                'value',
                'text'
            ]);
        });
    }

    static function permissionOptions(){
        $guardName = self::guardName();
        return self::permissionModel()->get()->map(function($permission) use($guardName){
            $permission->value = $permission->id;
            $permission->text = __("permission.{$guardName}.{$permission->name}");
            return $permission->only([
                'value',
                'text'
            ]);
        });
    }

}
