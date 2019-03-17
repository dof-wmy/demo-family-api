<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminController extends ApiController
{
    public $guard_name = 'api_admin';
    public $pageSize = 10;

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
