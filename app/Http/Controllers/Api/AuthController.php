<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\WechatUser;

use App\Transformers\MeTransformer;

use Image;
use Storage;

class AuthController extends ApiController
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
            'loginByWechat',
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
        try {
            $mockOpenid = $request->mock_openid;
            if ($mockOpenid && in_array($mockOpenid, config('wechat.mock_openids'))) {
                $wechatUser = WechatUser::where('openid', $mockOpenid)->first();
            }
            if (empty($wechatUser)) {
                if (empty($request->code)) {
                    return $this->response->errorUnauthorized('code不能为空');
                }
                $wechatUser = WechatUser::firstByRequest($request, $request->header('App-Type'));
            }
            if (!blank($wechatUser)) {
                $user = $wechatUser->getUser();
                if (empty($user)) {
                    $create = true;
                    if ($create) {
                        $user = $wechatUser->createUser($request);
                    } else {
                        // TODO 要求绑定 || 绑定操作
                        return $this->response->errorUnauthorized('敬请期待...');
                    }
                }
                // 更新微信用户信息
                $force = true;
                $wechatUserDetail = null;
                $wechatUser->updateFromWechat($force, $wechatUserDetail);
            }
        } catch (\Exception $e) {
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

        if (!empty($user)) {
            return $this->respondWithToken(auth($this->guard_name)->login($user));
        } else {
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
        $user = auth($this->guard_name)->user();
        return $this->response->item($user, new MeTransformer);
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

    public function updateMe(Request $request)
    {
        $user = auth($this->guard_name)->user();
        if ($request->has('encryptedData') && $request->has('iv')) {
            $wechatUser = WechatUser::where([
                'user_id'  => $user->id,
                'app_type' => 'mini_program',
                'app_id'   => config('wechat.mini_program.default.app_id')
            ])->first();
            $decryptedData = $wechatUser->wechat_app->encryptor->decryptData($wechatUser->detail['session_key'], $request->iv, $request->encryptedData);
            $wechatUser->updateFromWechat(true, $decryptedData);
            if (!empty($decryptedData['phoneNumber'])) {
                if(
                    User::where([
                        'mobile' => $decryptedData['phoneNumber'],
                    ])
                    ->where('id', '!=', $user->id)
                    ->first(['id'])
                ){
                    return $this->errorMessage("手机号 {$decryptedData['phoneNumber']} 已被占用");
                }
                $user->mobile = $decryptedData['phoneNumber'];
            }
        }
        if ($request->avatar) {
            $user->avatar = $this->saveBase64Image($request->avatar, [
                'sub_path' => [
                    'user',
                    'avatar',
                    md5($user->id),
                ],
                'disk'     => 'public',
            ]);
        }
        $user->save();
        return $this->me();
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