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
        // Add geography column
        DB::statement('ALTER TABLE venues ADD COLUMN coordinates geography(POINT, 4326) NULL');
        
        // Migrate existing lat/lng to coordinates
        DB::statement('UPDATE venues SET coordinates = ST_SetSRID(ST_MakePoint(lng, lat), 4326) WHERE lat IS NOT NULL AND lng IS NOT NULL');
        
        // Add GIST index for fast spatial queries
        DB::statement('CREATE INDEX venues_coordinates_idx ON venues USING GIST (coordinates)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
             $table->dropColumn('coordinates');
        });
    }
};
