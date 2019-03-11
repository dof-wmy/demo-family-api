<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\GetAdminGroupRequest;
use App\Http\Requests\Admin\PostPermissionOfAdminGroupRequest;
use App\Http\Requests\Admin\DeletePermissionOfAdminGroupRequest;

use App\Models\AdminUser;

use App\Transformers\Admin\AdminGroupTransformer;

use Carbon\Carbon;

class AdminGroupController extends AdminController
{
    public function index(GetAdminGroupRequest $request){
        $mod = AdminUser::roleModel();
        $mod->with([
            'permissions',
        ]);
        $adminGroups = $mod->get();
        return $this->response
            ->collection($adminGroups, new AdminGroupTransformer)
            ->setMeta([
                // 'groupOptions' => AdminUser::groupOptions(),
                'permissionOptions' => AdminUser::permissionOptions(),
            ]);
    }

    public function givePermission($groupId, $permissionId, PostPermissionOfAdminGroupRequest $request){
        $adminGroup = AdminUser::roleModel()->find($groupId);
        $permission = AdminUser::permissionModel()->find($permissionId);
        if($adminGroup && $permission){
            $adminGroup->givePermissionTo($permission);
        }
        return $this->item($adminGroup, new AdminGroupTransformer);
    }

    public function revokePermission($groupId, $permissionId, DeletePermissionOfAdminGroupRequest $request){
        $adminGroup = AdminUser::roleModel()->find($groupId);
        $permission = AdminUser::permissionModel()->find($permissionId);
        if($adminGroup && $permission){
            $adminGroup->revokePermissionTo($permission);
        }
        return $this->item($adminGroup, new AdminGroupTransformer);
    }

}
