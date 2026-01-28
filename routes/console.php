<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new \App\Jobs\UpdateBookingStatusJob)->cron('0,30 * * * *');
Schedule::job(new \App\Jobs\CheckUpcomingBookingsJob)->cron('*/15 * * * *');

