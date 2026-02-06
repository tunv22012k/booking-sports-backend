<?php

namespace App\Services;

use App\Repositories\ReviewRepository;
use App\Models\Venue;

class ReviewService
{
    protected $reviewRepository;

    public function __construct(ReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function storeReview($user, $venueId, $data)
    {
        // Check if venue exists
        $venue = Venue::findOrFail($venueId);

        $reviewData = [
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ];

        return $this->reviewRepository->createReview($reviewData);
    }
}
