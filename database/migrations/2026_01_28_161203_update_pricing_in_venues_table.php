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
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('price_info');
            $table->decimal('price', 15, 0)->nullable();
            $table->string('pricing_type')->default('hour'); // hour, match, person
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('price_info')->nullable();
            $table->dropColumn(['price', 'pricing_type']);
        });
    }
};
