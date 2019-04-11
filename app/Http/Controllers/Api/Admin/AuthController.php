<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Requests\Admin\PostUpdateMeRequest;

class AuthController extends AdminController
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
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
        })->except([
            'login',
        ]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['username', 'password']);

        if (! $token = auth($this->guard_name)->attempt($credentials)) {
            return $this->response->errorUnauthorized();
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth($this->guard_name)->user();
        $permissions = [];
        foreach(__('permission.admin_user') as $permission=>$permissionText){
            if($user->can($permission)){
                $permissions[$permission] = true;
            }
        }
        return $this->response->array(array_merge(
            $user->only([
                'username',
                'name',
            ]), [
            'permissions' => $permissions,
        ]));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth($this->guard_name)->logout();

        return $this->response->noContent();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth($this->guard_name)->refresh());
    }

    public function updateMe(PostUpdateMeRequest $request)
    {
        $user = auth($this->guard_name)->user();
        foreach([
            'username',
            'name',
            'password',
        ] as $field){
            if($request->$field){
                $user->$field = $request->$field;
            }
        }
        $user->save();
        return $this->successMessage('更新成功');
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($this->guard_name)->factory()->getTTL() * 60,
            'success_message' => '登录成功',
        ]);
    }
}