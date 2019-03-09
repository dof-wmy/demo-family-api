<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

use Auth;

class AdminRequest extends BaseRequest
{
    public $adminUser;
    public $permissionName;

    public function __construct()
    {
        parent::__construct();

        if(!$this->adminUser){
            $this->adminUser = Auth::guard('api_admin')->user();
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if(!$this->adminUser){
            return false;
        }elseif(!$this->permissionName){
            return true;
        }else{
            return $this->adminUser->can($this->permissionName);
        }
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
        return $field ? $this->adminUser->$field : $this->adminUser;
    }
}
