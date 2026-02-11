<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('court_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 15, 0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable(); // null = indefinite
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate schedules for same court, day, time range, effective date
            $table->unique(
                ['court_id', 'day_of_week', 'start_time', 'effective_from'],
                'court_day_time_effective_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_schedules');
    }
};
