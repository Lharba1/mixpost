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
        Schema::create('mixpost_post_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('mixpost_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // created, updated, scheduled, published, failed, restored, deleted, etc.
            $table->text('description')->nullable();
            $table->json('data')->nullable(); // Additional data about the activity
            $table->json('changes')->nullable(); // What changed (before/after)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['post_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_post_activities');
    }
};
