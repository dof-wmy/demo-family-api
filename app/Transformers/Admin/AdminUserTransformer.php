<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\AdminUser;

class AdminUserTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(AdminUser $adminUser)
    {
        return [
            'id'            => (int) $adminUser->id,
            'username'      => $adminUser->username,
            'name'          => $adminUser->name,
            'phone'         => $adminUser->phone,
            'created_at'    => $adminUser->created_at,
            'groups'        => $adminUser->roles->map(function($role) use($adminUser){
                                $role->text = $adminUser->roleText($role);
                                return $role->only([
                                    'id',
                                    'name',
                                    'text',
                                ]);
                            }),
            'permissions'   => $adminUser->permissions->map(function($permission) use($adminUser){
                                $permission->text = $adminUser->permissionText($permission);
                                return $permission->only([
                                    'id',
                                    'name',
                                    'text',
                                ]);
                            }),
            'isSuperAdmin'  => $adminUser->hasRole('super-admin'),
            // 'directPermissions'   => $adminUser->getDirectPermissions(),
            // 'permissionsViaRoles' => $adminUser->getPermissionsViaRoles(),
            // 'allPermissions'      => $adminUser->getAllPermissions(),
            'deleted_at'    => $adminUser->deleted_at,
        ];
    }

}