<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    protected static function booted()
    {
        static::created(function ($review) {
            $review->updateVenueRating();
        });

        static::updated(function ($review) {
            $review->updateVenueRating();
        });

        static::deleted(function ($review) {
            $review->updateVenueRating();
        });
    }

    public function updateVenueRating()
    {
        if ($this->venue) {
            $venue = $this->venue;
            // Recalculate from DB
            $avgRating = $venue->reviews()->avg('rating') ?? 0;
            $count = $venue->reviews()->count();
            
            $venue->update([
                'rating' => round($avgRating, 1),
                'total_reviews' => $count
            ]);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
