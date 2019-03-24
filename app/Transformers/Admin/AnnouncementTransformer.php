<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Models\Announcement;

class AnnouncementTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(Announcement $announcement)
    {
        return $announcement->only([
            'id',
            'title',
            'content',
            'created_at',
        ]);
    }

}