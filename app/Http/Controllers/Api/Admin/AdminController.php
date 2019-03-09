<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Auth;

class AdminController extends ApiController
{
    public $guard_name = 'api_admin';
    public $pageSize = 10;
    public $currentAdminUser;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // 前置操作
            $this->currentAdminUser = Auth::guard('api_admin')->user();
            
            $response = $next($request);
            // 后置操作
            return $response;
        });
    }

    public function paginatorTransformer(LengthAwarePaginator $paginator){
        return [
            'paginatorTransformer' => [
                'current'     => $paginator->currentPage(),
                'pageSize'    => $paginator->perPage(),
                'total'       => $paginator->total(),
            ],
        ];
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
