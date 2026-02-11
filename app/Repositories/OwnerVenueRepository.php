<?php

namespace App\Repositories;

use App\Models\Venue;

class OwnerVenueRepository extends BaseRepository
{
    public function getModel()
    {
        return Venue::class;
    }

    /**
     * Get all venues owned by a specific user.
     */
    public function getByOwner(int $ownerId)
    {
        return $this->model->where('owner_id', $ownerId)
            ->with(['courts', 'amenities'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a venue by ID, ensuring it belongs to the given owner.
     */
    public function findByOwner(int $id, int $ownerId)
    {
        return $this->model->where('id', $id)
            ->where('owner_id', $ownerId)
            ->with(['courts.schedules', 'courts.extras', 'amenities'])
            ->first();
    }
}
