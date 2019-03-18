<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\User;

class MeTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(User $user)
    {
        return array_merge(
            $user->only([
                'username',
                'name',
                'mobile',
            ]), [
            'roles' => $user->getRoleNames(),
            'can' => [
                // 
            ],
        ]);
    }

}