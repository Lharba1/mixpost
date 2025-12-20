<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table for posting schedule slots (the queue)
        Schema::create('mixpost_posting_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->default('Default Schedule');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table for time slots within a schedule
        Schema::create('mixpost_posting_schedule_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('mixpost_posting_schedules')->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0-6 (Sunday-Saturday)
            $table->time('time'); // 24-hour format
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['schedule_id', 'day_of_week']);
        });

        // Table for queue items (posts waiting to be published)
        Schema::create('mixpost_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained('mixpost_posts')->onDelete('cascade');
            $table->foreignId('schedule_time_id')->nullable()->constrained('mixpost_posting_schedule_times')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'published', 'failed'])->default('pending');
            $table->integer('position')->default(0); // For manual ordering
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_queue');
        Schema::dropIfExists('mixpost_posting_schedule_times');
        Schema::dropIfExists('mixpost_posting_schedules');
    }
};
