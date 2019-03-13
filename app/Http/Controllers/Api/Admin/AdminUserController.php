<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\GetAdminUserRequest;
use App\Http\Requests\Admin\PostAdminUserRequest;
use App\Http\Requests\Admin\DeleteAdminUserRequest;

use App\Models\AdminUser;
use Spatie\Permission\Models\Role;

use App\Transformers\Admin\AdminUserTransformer;

use Carbon\Carbon;

class AdminUserController extends AdminController
{
    public function __construct()
    {
        $this->middleware('api.auth');
    }

    public function index(GetAdminUserRequest $request){
        $mod = AdminUser::with([
            'roles',
            'permissions',
        ]);
        $trash = $request->input('trash', '');
        if($trash && in_array($trash, $this->trashValues())){
            $mod->$trash();
        }
        if($request->keywords){
            $mod->where(function($query) use($request){
                $query->where('username', "LIKE", "%{$request->keywords}%");
                $query->orWhere('name', "LIKE", "%{$request->keywords}%");
            });
        }
        if($request->dateStart){
            $mod->where('created_at', '>=', Carbon::parse($request->dateStart)->format('Y-m-d 00:00:00'));
        }
        if($request->dateEnd){
            $mod->where('created_at', '<=', Carbon::parse($request->dateEnd)->format('Y-m-d 23:59:59'));
        }
        if(
            $request->groups
            && ($groupIds = explode(',', $request->groups))
            && !empty($groupIds)
        ){
            $mod->whereHas('roles', function($query) use($groupIds){
                $query->whereIn('role_id', $groupIds);
            });
        }
        if($request->order){
            $order = explode(',', $request->order);
            $mod->orderBy($order[0], ($order[1] == 'ascend' ? 'asc' : 'desc'));
        }else{
            $mod->orderBy('id', 'desc');
        }
        $adminUsers = $mod->paginate($this->pageSize);
        return $this->response
            ->paginator($adminUsers, new AdminUserTransformer)
            ->setMeta(array_merge(
                $this->paginatorTransformer($adminUsers),
                $this->trashOptions(),
                [
                    'groupOptions' => AdminUser::groupOptions(),
                    'permissionOptions' => AdminUser::permissionOptions(),
                ]
            ));
    }

    public function saveAdminUser($id = null, PostAdminUserRequest $request){
        $adminUser = $id ? AdminUser::withTrashed()->find($id) : new AdminUser();
        if(is_null($adminUser)){
            return $this->errorMessage('管理员不存在');
        }
        foreach([
            'username',
            'name',
            'password',
        ] as $field){
            if($request->has($field)){
                $adminUser->$field = $request->$field;
            }
        }
        $adminUser->save();
        foreach([
            'groups',
            'permissions',
        ] as $field){
            if($request->has($field)){
                if($field == 'groups'){
                    $adminUser->syncRoles(Role::whereIn('id', $request->$field)->pluck('name')->toArray());
                }elseif($field == 'permissions'){
                    $adminUser->syncPermissions(AdminUser::permissionModel()->whereIn('id', $request->$field)->pluck('name')->toArray());
                }else{
                    $adminUser->$field()->sync($request->$field);
                }
            }
        }
        return $this->successMessage(($id ? '编辑管理员成功' : '新增管理员成功'));
    }

    public function deleteAdminUser(DeleteAdminUserRequest $request){
        $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
        $ids = array_diff($ids, [
            $this->currentAdminUser->id,
        ]);
        $mod = AdminUser::whereIn('id', $ids);
        if($request->undo){
            $mod->onlyTrashed()->restore();
        }else{
            $mod->delete();
        }
        return $this->successMessage(($request->undo ? '恢复' : '删除') . '管理员成功');
    }
}
