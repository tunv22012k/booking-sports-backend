<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'rating' => 'float',
        'total_reviews' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($venue) {
            if ($venue->isDirty(['lat', 'lng']) && $venue->lat && $venue->lng) {
                $id = $venue->id;
                $lat = $venue->lat;
                $lng = $venue->lng;

                \Illuminate\Support\Facades\DB::statement(
                    "UPDATE venues SET coordinates = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?",
                    [$lng, $lat, $id]
                );
            }
        });
    }

    // ── Relationships ──

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    public function amenities()
    {
        return $this->hasMany(VenueAmenity::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
