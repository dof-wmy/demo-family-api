<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Admin\GetAnnouncementRequest;
use App\Http\Requests\Admin\PostAnnouncementRequest;

use App\Models\Announcement;

use App\Transformers\Admin\AnnouncementTransformer;

use Carbon\Carbon;

class AnnouncementController extends AdminController
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

    public function index(GetAnnouncementRequest $request){
        $model = Announcement::with([
            // 
        ]);
        if($request->order){
            $order = explode(',', $request->order);
            $model->orderBy($order[0], ($order[1] == 'ascend' ? 'asc' : 'desc'));
        }else{
            $model->orderBy('id', 'desc');
        }
        $announcements = $model->paginate($this->pageSize);
        return $this->response
            ->paginator($announcements, new AnnouncementTransformer)
            ->setMeta(array_merge(
                $this->paginatorTransformer($announcements),
                []
            ));
    }

    public function store(PostAnnouncementRequest $request){
        $announcement = new Announcement();
        $announcement->title = $request->title;
        $announcement->content = $request->content;
        $announcement->save();
        $announcement->publish();
        return $this->response->item($announcement, new AnnouncementTransformer);
    }
}
