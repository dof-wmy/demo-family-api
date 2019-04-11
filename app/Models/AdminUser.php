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

    public function getPermissions(){
        $permissions = [];
        foreach(__('permission.admin_user') as $permission=>$permissionText){
            if($this->can($permission)){
                $permissions[$permission] = true;
            }
        }
        return $permissions;
    }

    public function getMenuData($permissions = []){
        if(empty($permissions)){
            $permissions = $this->getPermissions();
        }
        $permissions = array_keys($permissions);
        $menuData = [
            [
              'path' => '/index',
              'name' => '首页',
              'icon' => 'home',
            ],
            [
                'path' => '/admin',
                'name' => '后台',
                'icon' => 'tool',
                'children' => [
                    [
                        'path' => '/admin/user',
                        'name' => '管理员',
                        'icon' => 'user',
                        'permissions' => [        
                            'get_admin_user',
                            'post_admin_user',
                            'delete_admin_user',
                        ],
                    ],
                    [
                        'path' => '/admin/group',
                        'name' => '管理组',
                        'icon' => 'team',
                        'permissions' => [        
                            'get_admin_group',
                            'post_permission_of_admin_group',
                            'delete_permission_of_admin_group',
                        ],
                    ],
                ],
            ],
        ];
        $menuData = $this->menuFilter($menuData, $permissions);
        return $menuData;
    }

    public function menuFilter(&$menu, $permissions){
        foreach($menu as $menuItemKey=>$menuItem){
            if(!empty($menuItem['permissions']) && empty(array_intersect($permissions, $menuItem['permissions']))){
                unset($menu[$menuItemKey]);
            }elseif(!empty($menuItem['children'])){
                $menu[$menuItemKey]['children'] = $this->menuFilter($menu[$menuItemKey]['children'], $permissions);
            }else{
                // 
            }
        }

        foreach($menu as $menuItemKey=>$menuItem){
            if(isset($menuItem['children']) && empty($menuItem['children'])){
                unset($menu[$menuItemKey]);
            }
        }
        return $menu;
    }
}
