<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $guarded = [];

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
