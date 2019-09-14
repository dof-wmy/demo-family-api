<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Requests\Admin\PostUpdateMeRequest;

use Cache;
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
        $request = request();
        if($request->type == "account"){
            $credentials = request(['username', 'password']);
            if (! $token = auth($this->guard_name)->attempt($credentials)) {
                return $this->response->errorUnauthorized();
            }
            return $this->respondWithToken($token);
        }elseif($request->type == "socialite"){
            if(empty($request->code)){
                return $this->response->errorUnauthorized('登录失败：code');
            }
            if(empty($request->user)){
                return $this->response->errorUnauthorized('登录失败：user');
            }
            $encryptedData = Cache::get($request->code);
            if(empty($encryptedData)){
                return $this->response->errorUnauthorized('登录失败：encryptedData');
            }
            $decryptedData = decrypt($encryptedData);
            if(empty($decryptedData)){
                return $this->response->errorUnauthorized('登录失败：decryptedData');
            }
            foreach($decryptedData['users'] as $user){
                if($user['id'] == $request->user['id']){
                    Cache::forget($request->code);
                    return $this->respondWithToken(auth($this->guard_name)->login($user));
                }
            }
            return $this->response->errorUnauthorized('登录失败');
        }else{
            return $this->response->errorUnauthorized('登录方式异常');
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me($data = [])
    {
        $user = auth($this->guard_name)->user();
        $permissions = $user->getPermissions();
        return $this->response->array(array_merge(
            $user->only([
                'username',
                'name',
            ]), [
                'roles' => $user->roles()->pluck('name'),
                'permissions' => $permissions,
                'menuData' => $user->getMenuData($permissions),
                'socialiteUsers' => $user->allSocialiteUsers(),
                'pusherChannelName' => $user->pusherChannelName(),
            ],
            $data
        ));
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
        if($request->socialiteUsers){
            $user->socialiteUsers()->detach($request->socialiteUsers['detach']);
            return $this->me();
        }
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