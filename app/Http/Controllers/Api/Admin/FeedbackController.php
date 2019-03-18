<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\GetFeedbackRequest;

use App\Models\Feedback;

use App\Transformers\Admin\FeedbackTransformer;

use Carbon\Carbon;

class FeedbackController extends AdminController
{
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

    public function index(GetUserRequest $request){
        $model = Feedback::with([
            'user',
        ]);
        if($request->order){
            $order = explode(',', $request->order);
            $model->orderBy($order[0], ($order[1] == 'ascend' ? 'asc' : 'desc'));
        }else{
            $model->orderBy('id', 'desc');
        }
        $feedback = $model->paginate($this->pageSize);
        return $this->response
            ->paginator($feedback, new FeedbackTransformer)
            ->setMeta(array_merge(
                $this->paginatorTransformer($feedback),
                []
            ));
    }

}
