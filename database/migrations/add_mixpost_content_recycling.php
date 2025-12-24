<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mixpost_recycling_posts')) {
            Schema::create('mixpost_recycling_posts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('post_id')->constrained('mixpost_posts')->cascadeOnDelete();
                $table->foreignId('workspace_id')->nullable()->constrained('mixpost_workspaces')->cascadeOnDelete();
                
                // Recycling configuration
                $table->enum('interval_type', ['hours', 'days', 'weeks', 'months'])->default('days');
                $table->integer('interval_value')->default(7); // e.g., every 7 days
                $table->integer('max_recycles')->nullable(); // null = unlimited
                $table->integer('recycle_count')->default(0);
                
                // Status and timing
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_recycled_at')->nullable();
                $table->timestamp('next_recycle_at')->nullable();
                
                $table->timestamps();
                
                $table->index(['is_active', 'next_recycle_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mixpost_recycling_posts');
    }
};
