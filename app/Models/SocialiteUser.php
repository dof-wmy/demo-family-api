<?php

namespace App\Models;

class SocialiteUser extends Base
{

    public function getDetailAttribute($value){
        return json_decode($value, true);
    }

    public function setDetailAttribute($value){
        $this->attributes['detail'] = json_encode($value);
    }

    public function adminUsers()
    {
        return $this->morphedByMany('App\Models\AdminUser', 'socialitable');
    }

    static function getSocialiteUser($driver, $user, $state = []){
        $socialiteUser = self::where([
            'driver'        => $driver,
            'socialite_id'  => $user->getId(),
        ])->first() ?: new self();
        $socialiteUser->driver = $driver;
        $socialiteUser->socialite_id = $user->getId();
        $socialiteUser->token = $user->token;
        $socialiteUser->refresh_token = $user->refreshToken ?: null;
        $socialiteUser->expires_in = $user->expiresIn;
        $socialiteUser->nickname = $user->getNickname();
        $socialiteUser->name = $user->getName();
        $socialiteUser->email = $user->getEmail();
        $socialiteUser->avatar = $user->getAvatar();
        $socialiteUser->detail = json_encode($user);
        $socialiteUser->save();

        $socialitableId = array_get($state, 'socialitableId');
        if($socialitableId){
            $socialitableRelation = array_get($state, 'socialitableRelation');
            try{
                if($socialiteUser->$socialitableRelation()->where('socialitable_id', $socialitableId)->first()){
                    $socialiteUser->$socialitableRelation()->updateExistingPivot($socialitableId, [
                        'updated_at' => now(),
                    ]);
                }else{
                    $socialiteUser->$socialitableRelation()->attach($socialitableId, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }catch(\Exception $e){
                // TODO 暂时只记录普通错误日志
                logger()->error((string) $e);
                return $socialiteUser;
            }
        }
        event(new \App\Events\SocialiteLoginSuccess($socialiteUser, $state));
        return $socialiteUser;
    }

    static function getSocialites(){
        return [
            'github' => [
                'name'   => 'Github', 
                'icon'   => 'github',
                'logo'   => url('/images/brands/github-fill.png'),
                'url'    => 'https://www.github.com',
                'description' => '全球最大开源社区网站',
            ],
        ];
    }

}
