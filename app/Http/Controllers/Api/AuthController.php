<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\WechatUser;

class AuthController extends ApiController
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('api.auth', [
            'except' => [
                'login',
                'loginByWechat',
            ],
        ]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth($this->guard_name)->attempt($credentials)) {
            return $this->response->errorUnauthorized();
        }

        return $this->respondWithToken($token);
    }

    public function loginByWechat(Request $request){
        try{
            $mockOpenid = $request->mock_openid;
            if($mockOpenid && in_array($mockOpenid, config('wechat.mock_openids'))){
                $wechatUser = WechatUser::where('openid', $mockOpenid)->first();
            }
            if(empty($wechatUser)){
                $code = $request->code;
                if(empty($code)){
                    return $this->response->errorUnauthorized('code不能为空');
                }
                $wechatUser = WechatUser::firstByRequest($request);
            }
            if(!blank($wechatUser)){
                $user = $wechatUser->user()->first();
                if(empty($user)){
                    $create = true;
                    if($create){
                        $user = $wechatUser->createUser();
                    }else{
                        // TODO 要求绑定 || 绑定操作
                        return $this->response->errorUnauthorized('敬请期待...');
                    }
                }
                // 更新微信用户信息
                $force = false;
                $wechatUserDetail = null;
                if($wechatUser->app_type == 'mini_program'){
                    if($request->has('user_info')){
                        $userInfo = $request->user_info;
                        $userInfo = json_decode($userInfo, true);
                        $force = true;
                        $wechatUserDetail = $wechatUser->detail;
                        if($avatarUrl = array_get($userInfo, 'avatarUrl')){
                            $wechatUserDetail['headimgurl'] = $avatarUrl;
                        }
                        if($nickName = array_get($userInfo, 'nickName')){
                            $wechatUserDetail['nickname'] = $nickName;
                        }
                    }
                }else{
                    // 
                }
                $wechatUser->updateFromWechat($force, $wechatUserDetail);
            }
        }catch(\Exception $e){
            $exceptionCode = $e->getCode();
            $exceptionMessage = $e->getMessage();
            $errorLog = implode("\n======", [
                "loginByWechat 异常",
                "exceptionCode: {$exceptionCode}",
                "exceptionMessage: {$exceptionMessage}",
                (string) $e,
            ]);
            logger()->error($errorLog);
            // return $this->response->error((config('app.debug') ? $errorLog : '登录异常'), 401);
            return $this->response->errorUnauthorized((config('app.debug') ? $errorLog : '登录异常'));
        }

        if(!empty($user)){
            return $this->respondWithToken(auth($this->guard_name)->login($user));
        }else{
            return $this->response->errorUnauthorized('登录失败');
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->response->array(auth($this->guard_name)->user()->only([
            'username',
            'name',
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