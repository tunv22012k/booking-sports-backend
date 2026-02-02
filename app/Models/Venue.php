<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'rating' => 'float',
        'lat' => 'float',
        'lng' => 'float',
        'total_reviews' => 'integer',
        // 'coordinates' field is handled via DB::raw in queries usually
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
                
                // Perform direct update to set PostGIS column
                // We use DB statement to bypass Eloquent's lack of Geography support
                \Illuminate\Support\Facades\DB::statement(
                    "UPDATE venues SET coordinates = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?",
                    [$lng, $lat, $id]
                );
            }
        });
    }

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    public function extras()
    {
        return $this->hasMany(VenueExtra::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
