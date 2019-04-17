<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;

use App\Models\SocialiteUser;

use Illuminate\Support\Str;

class AdminController extends ApiController
{
    public $guard_name = 'api_admin';
    public $pageSize = 10;

    public function getConfig(){
        $socialite = [];
        foreach(SocialiteUser::getSocialites() as $socialiteDriver=>$socialiteDriverConfig){
            $pusherChannelName = 'socialite-' . md5(implode('~', [
                $this->guard_name,
                $socialiteDriver,
                Str::uuid(),
            ]));
            $socialite[] = array_merge($socialiteDriverConfig, [
                'driver' => $socialiteDriver,
                'pusherChannelName' => $pusherChannelName,
                'oauthUrl'    => route('socialite.url', [
                    'driver' => $socialiteDriver,
                    'state'  => encrypt([
                        'socialitableRelation' => 'adminUsers',
                        'pusherChannelName' => $pusherChannelName,
                        'stateless' => true,
                    ]),
                ]),
            ]);
        }
        return $this->response->array([
            'socialite' => $socialite,
        ]);
    }

    public function trashOptions(){
        return [
            'trashOptions' => [
                [
                    'value' => 'withTrashed',
                    'text'  => '全部',
                ],
                [
                    'value' => '',
                    'text'  => '正常',
                ],
                [
                    'value' => 'onlyTrashed',
                    'text'  => '已删除',
                ],
            ],
        ];
    }

    public function trashValues(){
        return array_pluck($this->trashOptions()['trashOptions'], 'value');
    }
}
