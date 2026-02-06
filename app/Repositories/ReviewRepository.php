<?php

namespace App\Repositories;

use App\Models\Review;

class ReviewRepository extends BaseRepository
{
    public function getModel()
    {
        return Review::class;
    }

    public function createReview($data)
    {
        return $this->create($data);
    }
}
