<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

use Auth;

class AdminRequest extends FormRequest
{
    public $adminUser;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function adminUser($field = null){
        if(!$this->adminUser){
            $this->adminUser = Auth::guard('api_admin')->user();
        }
        return $field ? $this->adminUser->$field : $this->adminUser;
    }
}
