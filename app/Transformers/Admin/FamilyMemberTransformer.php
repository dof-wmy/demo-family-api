<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\Family\FamilyMember;

class FamilyMemberTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(FamilyMember $item)
    {
        $itemTransform = $item->only([
            'id',
            'family_id',
            'name',
            'sex',
            'birthday',
            'father_id',
            'mother_id',
            'created_at',
        ]);
        $itemTransform['father_name'] = $item->father ? $item->father->name : '-';
        $itemTransform['mother_name'] = $item->mother ? $item->mother->name : '-';
        return $itemTransform;
    }

}
