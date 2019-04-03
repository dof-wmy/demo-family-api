<?php

namespace App\Models;
use Carbon\Carbon;

class WechatUser extends Base
{

    protected $fillable = [
        'openid',
        'app_id',
        'app_type',
        'detail',
        'user_id',
        'nickname',
        'headimgurl',
    ];

    public function getAvatarAttribute(){
        return $this->headimgurl;
    }

    public function getWechatAppAttribute(){
        return app("wechat.{$this->app_type}");
    }

    public function getDetailAttribute($value){
        return json_decode($value, true);
    }

    public function setDetailAttribute($value){
        $this->attributes['detail'] = json_encode($value);
    }

    /**
     * 获得通过此渠道注册的用户。
     */
    public function registerUser()
    {
        return $this->morphOne(User::class, 'register_source');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    static function firstByRequest($request, $wechatAppType = ''){
        $wechatAppType = $wechatAppType ?: config('wechat.defaults.app_type');
        $wechatApp = app("wechat.{$wechatAppType}");
        if($wechatAppType == 'official_account'){
            $wechatOauthUser = $wechatApp->oauth->setRequest($request)->user();
            $wechatUserDetail = [
                'openid'      => $wechatOauthUser->getId(),
                'nickname'    => $wechatOauthUser->getNickname(),
                'headimgurl'  => $wechatOauthUser->getAvatar(),
            ];
            if(!empty($wechatOauthUser['unionid'])){
                $wechatUserDetail['unionid'] = $wechatOauthUser['unionid'];
            }
        }elseif($wechatAppType == 'mini_program'){
            $wechatAuthSession = $wechatApp->auth->session($request->code);
            if(!empty($wechatAuthSession['openid'])){
                $wechatUserDetail = [
                    'openid'      => $wechatAuthSession['openid'],
                    'session_key' => $wechatAuthSession['session_key'],
                ];
                if(!empty($wechatAuthSession['unionid'])){
                    $wechatUserDetail['unionid'] = $wechatAuthSession['unionid'];
                }
            }else{
                // TODO 小程序登录失败
            }
        }else{
            // 
        }
        if(!empty($wechatUserDetail)){
            $wechatUser = self::firstOrCreate([
                'openid'    => $wechatUserDetail['openid'],
                'app_id'    => $wechatApp->config->app_id,
                'app_type'  => $wechatAppType,
            ], [
                'nickname'    => array_get($wechatUserDetail, 'nickname', null),
                'headimgurl'  => array_get($wechatUserDetail, 'headimgurl', null),
            ]);
            if(!empty($wechatUser->detail)){
                $wechatUser->detail = array_merge($wechatUser->detail, $wechatUserDetail);
            }else{
                $wechatUser->detail = $wechatUserDetail;
            }
            $wechatUser->save();
            if(!empty($wechatUser->detail['unionid'])){
                $wechatUser->unionid = $wechatUser->detail['unionid'];
                $wechatUser->save();
            }
            return $wechatUser;
        }else{
            return null;
        }
    }

    public function getUser(){
        $wechatUser = $this;
        $user = $wechatUser->user()->first();
        if(empty($user) && !empty($wechatUser->unionid)){
            $otherWechatUser = WechatUser::with([
                'user',
            ])->where([
                'unionid' => $wechatUser->unionid,
            ])->whereHas('user')->first();
            if($otherWechatUser){
                $user = $otherWechatUser->user;
                $wechatUser->user_id = $otherWechatUser->user_id;
                $wechatUser->save();
            }
        }
        return $user;
    }

    public function createUser(){
        $user = $this->registerUser()->create([
            'username'  => User::generateUserName('wechat_'),
        ]);
        $this->user_id = $user->id;
        $this->save();

        $this->updateFromWechat(true);
        return $user;
    }

    public function updateFromWechat($force = false, $wechatUserDetail = null){
        if(
            $force
            || Carbon::now()->gt(Carbon::parse($this->updated_at)->addDays(1))
        ){
            if(empty($wechatUserDetail)){
                $wechatUserDetail = $this->detail;
                if($this->app_type == 'official_account'){
                    $wechatUserDetail = $this->wechat_app->user->get($this->openid);
                    // TODO 判断数据有效性
                }
            }
            $this->detail = array_merge($this->detail, $wechatUserDetail);
            $this->save();
            // detail保存之后（以防数据长度引起丢失数据）再更新其他冗余字段
            $this->updateFromDetail();
        }
        return $this;
    }

    public function updateFromDetail(){
        foreach([
            'nickname'      => 'nickname',
            'headimgurl'    => 'headimgurl',
            'unionId'       => 'unionid',
            'nickName'      => 'nickname',
            'avatarUrl'     => 'headimgurl',
            'unionId'       => 'unionid',

        ] as $field => $wechatUserField){
            if(array_get($this->detail, $field)){
                $this->$wechatUserField = $this->detail[$field];
            }
        }
        $this->save();
        return $this;
    }
}
