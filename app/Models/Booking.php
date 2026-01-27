<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_for_transfer' => 'boolean',
        'start_time' => 'datetime:H:i', // Format when serializing
        'end_time' => 'datetime:H:i',
        'pending_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    // Helper to get venue directly
    public function venue()
    {
        return $this->hasOneThrough(Venue::class, Court::class, 'id', 'id', 'court_id', 'venue_id');
    }
}
