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
        }elseif($wechatAppType == 'mini_program'){
            $wechatAuthSession = $wechatApp->auth->session($request->code);
            if(!empty($wechatAuthSession['openid'])){
                $wechatUserDetail = [
                    'openid'      => $wechatAuthSession['openid'],
                ];
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
                'detail'      => $wechatUserDetail,
                'nickname'    => array_get($wechatUserDetail, 'nickname', null),
                'headimgurl'  => array_get($wechatUserDetail, 'headimgurl', null),
            ]);
            return $wechatUser;
        }else{
            return null;
        }
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
            $this->detail = $wechatUserDetail;
            $this->save();
            // detail保存之后（以防数据长度引起丢失数据）再更新其他冗余字段
            $this->updateFromDetail();
        }
        return $this;
    }

    public function updateFromDetail(){
        foreach([
            'nickname',
            'headimgurl',
        ] as $field){
            if(array_get($this->detail, $field)){
                $this->$field = $this->detail[$field];
            }
        }
        $this->save();
        return $this;
    }
}
