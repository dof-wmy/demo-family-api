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
        $this->middleware('api.auth', ['except' => ['login']]);
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
        return $this->response->array(array_merge(
            $user->only([
                'username',
                'name',
            ]), [
            'can' => [
                'get_admin_user'    => $user->can('get_admin_user'),
                'post_admin_user'   => $user->can('post_admin_user'),
                'delete_admin_user' => $user->can('delete_admin_user'),
            ],
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
            'expires_in' => auth($this->guard_name)->factory()->getTTL() * 60
        ]);
    }
}