<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\User;

class UserTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(User $user)
    {
        return $user->only([
            'id',
            'username',
            'name',
            'is_black',
            'created_at',
        ])->toArray();
    }

}