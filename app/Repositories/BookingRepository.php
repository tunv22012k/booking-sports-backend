<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingRepository extends BaseRepository
{
    public function getModel()
    {
        return Booking::class;
    }

    public function getByUser($userId, $limit = 10, $status = null)
    {
        $query = $this->model->with(['court.venue'])
            ->where('user_id', $userId);

        if ($status) {
            $statuses = explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($limit);
    }

    public function findPendingOrConfirmed($courtId, $date, $startTime, $endTime)
    {
        return $this->model->where('court_id', $courtId)
            ->where('date', $date)
            ->where(function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($sub) {
                        // Confirmed/completed bookings
                        $sub->whereIn('status', ['confirmed', 'completed'])
                            // OR pending bookings that haven't expired
                            ->orWhere(function ($pending) {
                                $pending->where('status', 'pending')
                                    ->where('pending_expires_at', '>', now());
                            });
                    });
            })
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    public function getPendingExpired()
    {
        return $this->model->where('status', 'pending')
            ->where('pending_expires_at', '<', now())
            ->get();
    }

    public function findPendingByIdAndUser($id, $userId)
    {
        return $this->model->where('id', $id)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();
    }

    public function findByIdAndUser($id, $userId)
    {
        return $this->model->with(['court.venue', 'user'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function getPendingSlotsForVenue($venueId, $date)
    {
        return $this->model->whereHas('court', function ($q) use ($venueId) {
            $q->where('venue_id', $venueId);
        })
            ->where('date', $date)
            ->where('status', 'pending')
            ->where('pending_expires_at', '>', now())
            ->get(['id', 'court_id', 'user_id', 'start_time', 'end_time', 'pending_expires_at']);
    }

    public function getConfirmedBookingsForVenue($venueId, $date)
    {
        return $this->model->whereHas('court', function($q) use ($venueId) {
            $q->where('venue_id', $venueId);
        })
        ->where('date', $date)
        ->whereIn('status', ['confirmed', 'completed'])
        ->get(['court_id', 'start_time', 'end_time']);
    }
}
