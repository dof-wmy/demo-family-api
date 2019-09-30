<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use App\Models\Family\FamilyMember;

class PostUpdateFamilyMemberRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = PostStoreFamilyMemberRequest::getRules();
        $rules['name'][1] = $rules['name'][1]->ignore(request()->route('id'));
        return $rules;
    }
}
