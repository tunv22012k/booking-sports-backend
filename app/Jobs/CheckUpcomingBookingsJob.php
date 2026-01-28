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

class CheckUpcomingBookingsJob implements ShouldQueue
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
        Log::info('CheckUpcomingBookingsJob started.');

        $now = Carbon::now();
        $dateStr = $now->toDateString();
        // Time range: Now -> Now + 1 hour
        $startTime = $now->format('H:i');
        $endTime = $now->copy()->addHour()->format('H:i');

        // Logic: Find bookings that are 'confirmed', date is today, and start_time is within the next hour
        // AND start_time > now (to avoid marking past bookings as upcoming if they missed the completion job, 
        // though completion job should handle them. But strictly 'upcoming' means future).
        
        $affected = Booking::where('status', 'confirmed')
            ->where('date', $dateStr)
            ->where(function ($query) use ($startTime, $endTime) {
                // Check if start_time is between now and now + 1hr
                $query->whereBetween('start_time', [$startTime, $endTime]);
            })
            ->update(['status' => 'upcoming']);

        Log::info("CheckUpcomingBookingsJob finished. Marked $affected bookings as 'upcoming'.");
    }
}
