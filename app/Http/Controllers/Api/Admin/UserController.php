<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\GetUserRequest;
use App\Http\Requests\Admin\PostUserBlacklistRequest;   
use App\Http\Requests\Admin\DeleteUserBlacklistRequest;

use App\Models\User;

use App\Transformers\Admin\UserTransformer;

use Carbon\Carbon;

class UserController extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {
            // 前置操作
            if(empty($this->user)){
                return $this->response->errorUnauthorized('请先登录...');
            }
            $response = $next($request);

            // 后置操作
            return $response;
        });
    }

    public function index(GetUserRequest $request){
        $model = User::with([
            // 
        ]);
        if($request->order){
            $order = explode(',', $request->order);
            $model->orderBy($order[0], ($order[1] == 'ascend' ? 'asc' : 'desc'));
        }else{
            $model->orderBy('id', 'desc');
        }
        $users = $model->paginate($this->pageSize);
        return $this->response
            ->paginator($users, new UserTransformer)
            ->setMeta(array_merge(
                []
            ));
    }

    public function blacklist($ids, PostUserBlacklistRequest $request){
        $ids = explode(',', $ids);
        User::whereIn('id', $ids)->update([
            'blacklist' => 1,
        ]);
        return $this->usersBlacklist($ids);
    }

    public function unblacklist($ids, DeleteUserBlacklistRequest $request){
        $ids = explode(',', $ids);
        User::whereIn('id', $ids)->update([
            'blacklist' => null,
        ]);
        return $this->usersBlacklist($ids);
    }

    public function usersBlacklist($ids){
        return $this->response->array(
            User::whereIn('id', $ids)->get([
                'id',
                'blacklist',
            ]));
    }

}
