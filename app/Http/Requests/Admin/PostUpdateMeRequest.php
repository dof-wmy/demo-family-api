<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PostUpdateMeRequest extends AdminRequest
{
    public function authorize()
    {
        return $this->adminUser() ? true : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->adminUser = $this->adminUser();
        $rules = [
            'username'          => [
                'sometimes',
                'required',
                Rule::unique('admin_users')->ignore($this->adminUser->id),
            ],
            'name'              => [
                'sometimes',
                'required',
            ],
            'password'          => [
                'sometimes',
                'required',
                'confirmed',
            ],
        ];
        return $rules;
    }
}
