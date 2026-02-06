<?php

namespace App\Repositories;

use App\Models\Booking;

class MarketplaceRepository extends BaseRepository
{
    public function getModel()
    {
        return Booking::class;
    }

    public function getAvailableListings()
    {
        return $this->model->with(['court.venue'])
            ->where('is_for_transfer', true)
            ->where('transfer_status', 'available')
            ->where('date', '>=', now()->toDateString()) // Only future/today
            ->orderBy('date', 'asc')
            ->get();
    }
}
