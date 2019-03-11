<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\AdminUser;
use Spatie\Permission\Models\Role;

class AdminGroupTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Role $role)
    {
        $guardName = (new AdminUser())->guard_name;
        return [
            'id'            => (int) $role->id,
            'name'          => $role->name,
            'text'          => __("role.{$guardName}.{$role->name}"),
            'permissions'   => $role->permissions->map(function($permission) use($guardName){
                                // $permission->text = __("permission.{$guardName}.{$permission->name}");
                                // return $permission->only([
                                //     'id',
                                //     'name',
                                //     'text',
                                // ]);
                                return $permission->id;
                            }),
            'isSuperAdmin'  => $role->name == 'super-admin',
        ];
    }

}