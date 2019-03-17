<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class Announcementcontroller extends ApiController
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
        });
    }

    public function read(Request $request){
        $ids = $request->ids;
        $ids = is_string($ids) ? explode(',', $ids) : $ids;
        $this->user->announcements()->attach($ids);
        return $this->response->noContent();
    }
}