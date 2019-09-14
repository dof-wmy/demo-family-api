<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Auth;
use DB;
use Carbon\Carbon;
use App\Models\Base;

use Image;

class ApiController extends Controller
{
    use Helpers;

    public $pageSize = 10;
    public $guard_name = 'api';
    public $user;
    public $operator;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // 前置操作
            $this->user = Auth::guard($this->guard_name)->user();
            $this->operator = [
                'guard_name' => $this->guard_name,
                'user' => $this->user ? $this->user->only([
                    'id',
                    'username',
                    'name',
                ]) : null,
            ];
            // 保存定位信息
            if (
                $request->longitude
                && $request->latitude
                && is_numeric($request->longitude)
                && is_numeric($request->latitude)
            ) {

                if ($this->user) {
                    // 更新用户定位信息
                    $this->user->longitude = $request->longitude;
                    $this->user->latitude = $request->latitude;
                    $this->user->save();
                }
            }

            if (
                $this->user
                && $this->user->blacklist
            ) {
                return $this->errorMessage('账户异常');
            }

            $response = $next($request);

            // 后置操作
            return $response;
        });
    }

    public function successMessage($message)
    {
        return $this->response->array([
            'success_message' => $message,
        ]);
    }

    public function errorMessage($message, $title = '', $option = [])
    {
        if(array_get($option, 'db_rollback', false)){
            DB::rollback();
        }

        // return $this->response->array([
        //     'error_message' => $message,
        // ]);

        return $this->response->error(json_encode([
            'title' => $title,
            'content' => $message,
        ], JSON_UNESCAPED_UNICODE), 406);
    }

    public function paginatorTransformer(LengthAwarePaginator $paginator)
    {
        return [
            'paginatorTransformer' => [
                'current' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function saveBase64Image($base64Image, $storageConfig = [])
    {
        return Base::saveImageFromUrl($base64Image, $storageConfig = []);
    }

    public function uploadImage(Request $request)
    {
        $imageField = 'image';

        return Base::uploadImageFromUrl($request->$imageField, $request->input('type', 'default'));
    }

    public function dateWhereByRequest($model, $request, $field = 'created_at')
    {
        if ($request->dateStart) {
            $model->where($field, '>=', Carbon::parse($request->dateStart)->format('Y-m-d 00:00:00'));
        }
        if ($request->dateEnd) {
            $model->where($field, '<=', Carbon::parse($request->dateEnd)->format('Y-m-d 23:59:59'));
        }
    }

    public function orderByRequest($model, $request, $default = ['id' => 'desc'])
    {
        if ($request->order) {
            $order = explode(',', $request->order);
            $model->orderBy($order[0], ($order[1] == 'ascend' ? 'asc' : 'desc'));
        } else {
            foreach ($default as $field => $orderByType) {
                $model->orderBy($field, $orderByType);
            }
        }

        return $model;
    }

    public function getQrCode($data){
        $response = app('wechat.mini_program.default')->app_code->getUnlimit(array_get($data, 'scene'), array_get($data, 'optional'));
        if($response instanceof \EasyWeChat\Kernel\Http\StreamResponse){
            return $this->response->array([
                'url' => (string) Image::make($response->getBody())->encode('data-url'),
            ]);
        }else{
            return $this->response->array([
                $response
            ]);
        }
    }
}
