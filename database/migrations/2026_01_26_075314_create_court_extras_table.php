<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * owner_extras: danh mục option thêm do owner tự định nghĩa (dùng chung cho mọi venue/court)
     * court_owner_extra: pivot gắn option vào từng court cụ thể
     */
    public function up(): void
    {
        // Catalog of extras per owner (shared across all their venues/courts)
        Schema::create('owner_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 15, 0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot: which extras are applied to which court
        Schema::create('court_owner_extra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_extra_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['court_id', 'owner_extra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_owner_extra');
        Schema::dropIfExists('owner_extras');
    }
};
