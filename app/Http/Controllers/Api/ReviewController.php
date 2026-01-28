<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, $venueId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $venue = Venue::findOrFail($venueId);

        $review = Review::create([
            'user_id' => Auth::id(),
            'venue_id' => $venue->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review->load('user'), 201);
    }
}
