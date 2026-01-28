<?php

namespace App\Jobs;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBookingStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('UpdateBookingStatusJob started.');

        $now = Carbon::now();
        $dateStr = $now->toDateString();
        $timeStr = $now->toTimeString();

        // Need to update bookings that are 'confirmed' or 'pending'
        // AND (date < today OR (date == today AND end_time <= now))
        
        // We fetch potentially expired bookings chunk by chunk to avoid memory issues
        // although for this logic, mass update logic using SQL is more efficient if possible,
        // but let's stick to Eloquent for clarity and safety with accessors if needed.
        // However, raw SQL 'where' with Carbon is standard.

        $affected = Booking::whereIn('status', ['confirmed', 'pending', 'upcoming'])
            ->where(function ($query) use ($dateStr, $timeStr) {
                $query->where('date', '<', $dateStr)
                      ->orWhere(function ($q) use ($dateStr, $timeStr) {
                          $q->where('date', '=', $dateStr)
                            ->where('end_time', '<=', $timeStr);
                      });
            })
            ->update(['status' => 'completed']);

        Log::info("UpdateBookingStatusJob finished. Updated $affected bookings to 'completed'.");
    }
}
