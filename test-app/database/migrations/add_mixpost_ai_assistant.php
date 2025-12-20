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
        // Add AI service configuration to the services table (uses existing mixpost_services)
        // Also create a table for AI usage tracking
        Schema::create('mixpost_ai_usage', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // openai, anthropic
            $table->string('model');
            $table->string('action'); // generate, rewrite, translate, summarize
            $table->unsignedInteger('tokens_used');
            $table->decimal('cost', 10, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_ai_usage');
    }
};
