<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerExtra extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Courts that have this extra applied.
     */
    public function courts()
    {
        return $this->belongsToMany(Court::class, 'court_owner_extra')
            ->withTimestamps();
    }
}
