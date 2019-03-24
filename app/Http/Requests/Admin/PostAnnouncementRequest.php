<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PostAnnouncementRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title'          => [
                'required',
            ],
            'content'          => [
                'required',
            ],
        ];
        return $rules;
    }

    public function messages(){
        return [
            'title.required'    => '标题不能为空',
            'content.required'  => '内容不能为空',
        ];
    }
}
