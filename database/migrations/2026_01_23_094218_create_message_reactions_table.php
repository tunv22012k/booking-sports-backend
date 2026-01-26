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
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->unsignedBigInteger('user_id'); // Can correspond to users.id OR be a google_id (string/bigint)
            // Since our users table has bigint id and string google_id.
            // But normally reactions are linked to the internal user ID if logged in. 
            // Let's stick to user_id referencing users.id for now as all users have a numeric ID even if google logged in.
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->string('reaction'); // Emoji char
            $table->timestamps();
            
            // Unique constraint: One reaction per user per message? 
            // Usually YES, or at least one of EACH TYPE.
            // Let's enforce 1 reaction per user per message to match "toggle" behavior described in plan.
            // OR if we want multiple reactions (like Discord), we key by (user_id, message_id, reaction).
            // User request implies simple reaction. Let's allow multiple reactions if they are different emojis? 
            // "Toggle" usually means clicking LIKE adds like. Clicking LIKE again removes like.
            // Clicking HEART adds heart.
            // So a user can have Like AND Heart? 
            // Facebook/Messenger allows ONE reaction per message.
            // Slack/Discord allows multiple.
            // Design: "Toggle reaction" -> If I click Like, it adds Like. If I click Love, does it replace Like?
            // "thiết kế lại nút reaction sao cho không bị message đè... thân thiện"
            // Let's mimic Messenger: One reaction per user per message.
            $table->unique(['message_id', 'user_id']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
