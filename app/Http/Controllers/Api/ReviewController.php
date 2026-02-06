<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReviewService;
use App\Http\Requests\Review\StoreReviewRequest;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function store(StoreReviewRequest $request, $venueId)
    {
        $review = $this->reviewService->storeReview($request->user(), $venueId, $request->validated());
        return $this->successResponse($review->load('user'), null, 201);
    }
}
