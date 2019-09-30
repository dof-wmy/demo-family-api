<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;

use App\Models\Family\Family;
use App\Models\Family\FamilyMember;
use App\Transformers\Admin\FamilyMemberTransformer;
use App\Http\Requests\Admin\PostStoreFamilyMemberRequest;
use App\Http\Requests\Admin\PostUpdateFamilyMemberRequest;

class FamilyMemberController extends AdminController
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

    public function index(Request $request){
        $model = FamilyMember::with([
            'father',
            'mother',
        ]);
        if(in_array($request->sex, FamilyMember::availableSexes())){
            $model->where('sex', $request->sex);
        }
        if($request->family_id){
            $model->where('family_id', $request->family_id);
        }
        if($request->keywords){
            $model->whereIn('name', collect(explode(' ', $request->keywords))->map(function($keyword){
                return trim($keyword);
            })->filter(function($keyword){
                return !empty($keyword);
            })->values());
        }
        if($request->dateStart){
            $model->where('birthday', '>=', $request->dateStart);
        }
        if($request->dateEnd){
            $model->where('birthday', '<=', $request->dateEnd);
        }
        $this->orderByRequest($model, $request, [
            'birthday' => 'desc',
        ]);
        $items = (clone $model)->paginate(30);
        $meta = $this->paginatorTransformer($items);
        $meta['families'] = Family::get([
            'id AS value',
            'name AS text',
        ]);
        $meta['sexes'] = FamilyMember::getSexes();
        $meta['fathers'] = FamilyMember::where([
            'sex' => 1,
        ])->orderBy('id', 'desc')->limit(100)->get([
            'id AS value',
            'name AS text',
        ]);
        $meta['mothers'] = FamilyMember::where([
            'sex' => 2,
        ])->orderBy('id', 'desc')->limit(100)->get([
            'id AS value',
            'name AS text',
        ]);
        $meta['tree'] = FamilyMember::with([
            'mother',
        ])->where(function($query){
            $query->where('sex', 1);
            $query->orWhere(function($query){
                $query->whereNotNull('father_id');
            });
        })->get()->toTree();
        return $this->response
            ->paginator($items, new FamilyMemberTransformer)
            ->setMeta($meta);
    }

    public function store(PostStoreFamilyMemberRequest $request){
        if($request->father_id){
            $father = FamilyMember::find($request->father_id);
        }
        $item = FamilyMember::create([
            'family_id' => $request->family_id,
            'name' => $request->name,
            'sex' => $request->sex,
            'birthday' => $request->birthday,
            'father_id' => $request->father_id,
            'mother_id' => $request->mother_id,
        ], !empty($father) ? $father : null);
        return $this->response->item($item, new FamilyMemberTransformer);
    }

    public function update($id, PostUpdateFamilyMemberRequest $request){
        $familyMember = FamilyMember::where('id', $id)->first();
        foreach([
            'family_id',
            'name',
            'sex',
            'birthday',
            'father_id',
            'mother_id',
        ] as $field){
            $familyMember->$field = $request->$field;
        }
        if($familyMember->save()){
            if($familyMember->hasMoved()){
                FamilyMember::fixTree();
            };
        }
        return $this->response->item(FamilyMember::find($id), new FamilyMemberTransformer);
    }

    public function delete(Request $request){
        $familyMembers = FamilyMember::whereIn('id', $request->ids)->get();
        foreach($familyMembers as $familyMember){
            $familyMember->delete();
        }
        return $this->response->array([
            // 'ids' => $request->ids,
        ]);
    }

    public function getMembersRelation(Request $request){
        $familyId = FamilyMember::whereIn('id', $request->ids)
            ->whereNotNull('family_id')
            ->value('family_id');

        FamilyMember::fixTree();

        $familyMembers = FamilyMember::whereIn('id', $request->ids)
            ->where([
                'family_id' => $familyId
            ])
            ->orderBy('birthday', 'desc')
            ->limit(2)
            ->get();
        if($familyMembers->count() !== 2){
            return $this->errorMessage('无法比较');
        }
        $familyRelationShip = $familyMembers->first()->getFamilyRelationShip($familyMembers->last());
        return $this->successMessage(implode('，', $familyRelationShip['messages']), [
            'duration' => 10,
        ]);
    }
}
