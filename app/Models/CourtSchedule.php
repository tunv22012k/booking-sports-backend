<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtSchedule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'day_of_week' => 'integer',
        'price' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // ── Relationships ──

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    // ── Scopes ──

    /**
     * Only schedules effective on a given date.
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->where('is_active', true);
    }

    /**
     * Only schedules for a given day of week.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}
