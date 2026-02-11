<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueAmenity extends Model
{
    protected $guarded = [];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
