<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use App\Models\Family\FamilyMember;

class PostStoreFamilyMemberRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = self::getRules();
        return $rules;
    }

    public static function getRules(){
        return [
            'family_id' => [
                'required',
                Rule::exists('families', 'id'),
            ],
            'name' => [
                'required',
                Rule::unique('family_members')->where('family_id', request()->family_id),
            ],
            'sex' => [
                'required',
                Rule::in(FamilyMember::availableSexes()),
            ],
            'birthday' => [
                'required',
                'date',
            ],
            'father_id' => [
                Rule::requiredIf(function (){
                    return !empty(request()->mother_id);
                }),
                self::getParentExistsRule(1),
            ],
            'mother_id' => [
                'sometimes',
                self::getParentExistsRule(2),
            ],
        ];
    }

    public static function getParentExistsRule($sex){
        return Rule::exists('family_members', 'id')->where(function($query) use($sex){
            $query->where([
                'family_id' => request()->family_id,
                'sex' => $sex,
            ]);
            $query->where('birthday', '<', request()->birthday);
            // 若选择的母亲已存在配偶则，父亲必须是其已存在的配偶
            $motherId = request()->mother_id;
            if($sex === 1 && $motherId){
                $fatherId = FamilyMember::where([
                    'mother_id' => $motherId,
                ])->whereNotNull('father_id')->value('father_id');
                if($fatherId){
                    $query->where('id', $fatherId);
                }
            }
            // TODO 其他伦理关系条件限制检测
        });
    }
}
