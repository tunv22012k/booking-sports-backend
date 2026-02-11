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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_price', 15, 0);
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->boolean('is_paid')->default(false);
            $table->string('payment_code')->nullable();
            $table->timestamp('pending_expires_at')->nullable();
            $table->json('extras')->nullable();

            // Marketplace / Transfer
            $table->boolean('is_for_transfer')->default(false);
            $table->string('transfer_status')->nullable(); // available, transferred, requested
            $table->decimal('transfer_price', 15, 0)->nullable();
            $table->string('transfer_note')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
