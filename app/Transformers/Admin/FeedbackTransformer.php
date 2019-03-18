<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\User;

class FeedbackTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Feedback $feedback)
    {
        $result = $feedback->only([
            'id',
            'title',
            'content',
            'created_at',
        ]);
        $result['user'] = $feedback->user->only([
            'id',
            'username'
        ]);
        return $result;
    }

}