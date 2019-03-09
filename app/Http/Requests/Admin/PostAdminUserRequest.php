<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PostAdminUserRequest extends AdminRequest
{
    public $permissionName = 'post_admin_user';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'username'          => [
                'required',
                Rule::unique('admin_users'),
            ],
            'name'              => [
                'required',
            ],
            'password'          => [
                'required',
                'confirmed',
            ],
            'password_confirmation'  => [
                'required',
            ],
            'groups'            => [
                // 'required',
                'array',
            ],
        ];
        $id = request()->route('id');
        if($id){
            $rules['username'][1]->ignore($id);
            foreach($rules as $field=>$fieldRules){
                array_unshift($rules[$field], 'sometimes');
            }
        }else{
            // 
        }
        return $rules;
    }

    public function messages(){
        return [
            'groups.required'  => '请选择管理组',
            'groups.array'     => '请选择管理组.',
        ];
    }
}
