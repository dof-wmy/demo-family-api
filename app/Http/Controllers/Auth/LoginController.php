<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;

use Socialite;
use App\Models\SocialiteUser;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function socialiteRedirectToProvider($driver, Request $request)
    {
        $with = [];
        return Socialite::driver($driver)
            ->with($with)
            // ->scopes(['read:user', 'public_repo']) // 将所有现有范围与提供的范围合并
            // ->setScopes(['read:user', 'public_repo']) //使用 setScopes 方法覆盖所有现有范围
            ->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function socialiteHandleProviderCallback($driver, Request $request)
    {
        // TODO 无状态条件判断
        $stateless = true;
        if($stateless){
            $user = Socialite::driver($driver)->stateless()->user();
        }else{
            $user = Socialite::driver($driver)->user();
        }
        $socialiteUser = SocialiteUser::getSocialiteUser($driver, $user);
        event(new \App\Events\SocialiteLoginSuccess($socialiteUser, $stateless));
    }

}
