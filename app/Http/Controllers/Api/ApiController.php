<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use Auth;

class ApiController extends Controller
{
    use Helpers;

    public $pageSize = 10;
    public $guard_name = 'api';
    public $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // 前置操作
            $this->user = Auth::guard($this->guard_name)->user();
 
            $response = $next($request);

            // 后置操作
            return $response;
        });
    }

    public function successMessage($message){
        return $this->response->array([
            'success_message' => $message,
        ]);
    }

    public function errorMessage($message){
        return $this->response->array([
            'error_message' => $message,
        ]);
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
}
