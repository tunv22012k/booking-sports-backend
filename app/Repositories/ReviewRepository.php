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

    public function getReviewsByVenueId($venueId, $filters = [])
    {
        $query = $this->model->where('venue_id', $venueId)->with('user');

        // Filter by rating
        if (isset($filters['rating']) && in_array($filters['rating'], [1, 2, 3, 4, 5])) {
            $query->where('rating', $filters['rating']);
        }

        // Sort by created_at
        $sortDirection = isset($filters['sort_by']) && $filters['sort_by'] === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sortDirection);

        // Pagination
        $perPage = isset($filters['per_page']) ? $filters['per_page'] : 5;
        return $query->paginate($perPage);
    }
}
