<?php

namespace App\Http\Requests;

class PostFeedbackRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'content' => [
                'bail',
                'required',
            ],
        ];
    }

    public function messages(){
        return [
            'content.required'  => '内容不能为空',
        ];
    }
}
