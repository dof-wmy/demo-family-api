<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;

class AdminController extends ApiController
{
    public $guard_name = 'api_admin';
    public $pageSize = 10;

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
