<?php

namespace App\Models;

use Carbon\Carbon;

class WechatUser extends Base
{
    protected $fillable = [
        'openid',
        'unionid',
        'app_id',
        'app_type',
        'detail',
        'user_id',
        'nickname',
        'headimgurl',
    ];

    public function getAvatarAttribute()
    {
        return $this->headimgurl;
    }

    public function getWechatAppAttribute()
    {
        return app("wechat.{$this->app_type}");
    }

    public function getDetailAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDetailAttribute($value)
    {
        $this->attributes['detail'] = json_encode($value);
    }

    /**
     * 获得通过此渠道注册的用户。
     */
    public function registerUser()
    {
        return $this->morphOne(User::class, 'register_source');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function firstByRequest($request, $wechatAppType = '')
    {
        $wechatAppType = $wechatAppType ?: config('wechat.defaults.app_type');
        $wechatApp = app("wechat.{$wechatAppType}");
        if ($wechatAppType == 'official_account') {
            $wechatOauthUser = $wechatApp->oauth->setRequest($request)->user();
            $wechatUserDetail = $wechatOauthUser->getOriginal();
            $wechatUserDetail = array_merge($wechatUserDetail, [
                'openid'      => $wechatOauthUser->getId(),
                'nickname'    => $wechatOauthUser->getNickname(),
                'headimgurl'  => $wechatOauthUser->getAvatar(),
            ]);
            if (!empty($wechatOauthUser['unionid'])) {
                $wechatUserDetail['unionid'] = $wechatOauthUser['unionid'];
            }
        } elseif ($wechatAppType == 'mini_program') {
            $wechatAuthSession = $wechatApp->auth->session($request->code);
            if (!empty($wechatAuthSession['openid'])) {
                $wechatUserDetail = $wechatAuthSession;
            } else {
                // TODO 小程序登录失败
            }
        } elseif ($wechatAppType == 'open_platform') {
            //
        } else {
            //
        }
        if (!empty($wechatUserDetail)) {
            $wechatUser = self::firstOrCreate([
                'openid'    => $wechatUserDetail['openid'],
                'app_id'    => $wechatApp->config->app_id,
                'app_type'  => $wechatAppType,
            ], [
                'nickname'    => array_get($wechatUserDetail, 'nickname', null),
                'headimgurl'  => array_get($wechatUserDetail, 'headimgurl', null),
            ]);
            if (!empty($wechatUser->detail)) {
                $wechatUser->detail = array_merge($wechatUser->detail, $wechatUserDetail);
            } else {
                $wechatUser->detail = $wechatUserDetail;
            }
            $wechatUser->save();
            if (!empty($wechatUser->detail['unionid'])) {
                $wechatUser->unionid = $wechatUser->detail['unionid'];
                $wechatUser->save();
            }
            return $wechatUser;
        } else {
            return null;
        }
    }

    public function getUser()
    {
        $wechatUser = $this;
        $user = $wechatUser->user()->first();
        if (empty($user) && !empty($wechatUser->unionid)) {
            $otherWechatUser = WechatUser::with([
                'user',
            ])->where([
                'unionid' => $wechatUser->unionid,
            ])->whereHas('user')->first();
            if ($otherWechatUser) {
                $user = $otherWechatUser->user;
                $wechatUser->user_id = $otherWechatUser->user_id;
                $wechatUser->save();
            }
        }
        return $user;
    }

    public static function getUserFromOpenid($openid, $wechatAppAccount = 'official_account.default')
    {
        $wechatUserInfo = self::getWechatUserInfoFromOpenid($openid, $wechatAppAccount);

        $wechatAppAccountConfig = config("wechat.{$wechatAppAccount}");
        $wechatUser = self::firstOrCreate([
            'openid'    => $openid,
            'app_id'    => $wechatAppAccountConfig['app_id'],
            'app_type'  => $wechatAppAccountConfig['app_type'],
        ], array_merge(array_only($wechatUserInfo, [
            'unionid',
            'nickname',
            'headimgurl',
        ]), [
            'detail' => $wechatUserInfo,
        ]));
        if (!blank($wechatUser)) {
            $user = $wechatUser->getUser();
            if (empty($user)) {
                $user = $wechatUser->registerUser()->create([
                    'username'  => User::generateUserName('wechat_'),
                ]);
                $wechatUser->user_id = $user->id;
                $wechatUser->save();
            }
            return $user;
        }
    }

    public static function getWechatUserInfoFromOpenid($openid, $wechatAppAccount = 'official_account.default')
    {
        $wechatApp = app("wechat.{$wechatAppAccount}");
        return $wechatApp->user->get($openid);
    }

    public function createUser($request)
    {
        $user = $this->registerUser()->create([
            'username'  => User::generateUserName('wechat_'),
            'inviter_id'=> $request->inviter_id,
        ]);
        $this->user_id = $user->id;
        $this->save();

        $this->updateFromWechat(true);
        return $user;
    }

    public function updateFromWechat($force = false, $wechatUserDetail = null)
    {
        if (
            $force
            || Carbon::now()->gt(Carbon::parse($this->updated_at)->addDays(1))
        ) {
            if (empty($wechatUserDetail)) {
                $wechatUserDetail = $this->detail;
                if ($this->app_type == 'official_account') {
                    $wechatUserDetail = $this->wechat_app->user->get($this->openid);
                    // TODO 判断数据有效性
                }
            }
            if (
                empty($this->unionid)
                && !empty($wechatUserDetail['unionId'])
            ) {
                $this->unionid = $wechatUserDetail['unionId'];
            }
            $this->detail = array_merge($this->detail, $wechatUserDetail);
            $this->save();
            // detail保存之后（以防数据长度引起丢失数据）再更新其他冗余字段
            $this->updateFromDetail();
        }
        return $this;
    }

    public function updateFromDetail()
    {
        foreach ([
            'nickname'      => 'nickname',
            'headimgurl'    => 'headimgurl',
            'unionId'       => 'unionid',
            'nickName'      => 'nickname',
            'avatarUrl'     => 'headimgurl',
            'unionId'       => 'unionid',

        ] as $field => $wechatUserField) {
            if (array_get($this->detail, $field)) {
                $this->$wechatUserField = $this->detail[$field];
            }
        }
        $this->save();
        return $this;
    }
}
