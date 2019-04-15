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

    static function getSocialiteUser($driver, $user){
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
        return $socialiteUser;
    }
}
