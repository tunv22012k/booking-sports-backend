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
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('type'); // badminton, football, tennis, pickleball, basketball, swimming, gym, complex
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('operating_hours')->nullable(); // e.g. "06:00-23:00"
            $table->string('image')->nullable();
            $table->float('rating')->default(0);
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // PostGIS coordinates column
        DB::statement('ALTER TABLE venues ADD COLUMN coordinates geography(POINT, 4326) NULL');
        DB::statement('CREATE INDEX venues_coordinates_idx ON venues USING GIST (coordinates)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
