<?php

namespace App\Repositories;

use App\Models\OwnerExtra;

class OwnerExtraRepository extends BaseRepository
{
    public function getModel()
    {
        return OwnerExtra::class;
    }

    /**
     * Get all extras belonging to an owner.
     */
    public function getByOwner(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }
}
