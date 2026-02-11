<?php

namespace App\Repositories;

use App\Models\CourtSchedule;

class CourtScheduleRepository extends BaseRepository
{
    public function getModel()
    {
        return CourtSchedule::class;
    }

    /**
     * Get all schedules for a court.
     */
    public function getByCourt(int $courtId)
    {
        return $this->model->where('court_id', $courtId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get schedules for a specific court and day of week.
     */
    public function getByCourtAndDay(int $courtId, int $dayOfWeek)
    {
        return $this->model->where('court_id', $courtId)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check if a time slot overlaps with existing schedules.
     */
    public function hasOverlap(
        int $courtId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?int $excludeId = null
    ): bool {
        $query = $this->model->where('court_id', $courtId)
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($startTime, $endTime) {
                // Time overlap: new start < existing end AND new end > existing start
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->where(function ($q) use ($effectiveFrom, $effectiveTo) {
                // Date range overlap
                $q->where(function ($inner) use ($effectiveFrom, $effectiveTo) {
                    $inner->where('effective_from', '<=', $effectiveTo ?? '9999-12-31')
                          ->where(function ($dateEnd) use ($effectiveFrom) {
                              $dateEnd->whereNull('effective_to')
                                      ->orWhere('effective_to', '>=', $effectiveFrom);
                          });
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
