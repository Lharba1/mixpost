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
        // API tokens for external access
        Schema::create('mixpost_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('abilities')->nullable(); // Scopes/permissions
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('token');
            $table->index('is_active');
        });

        // API request logs
        Schema::create('mixpost_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_id')->nullable()->constrained('mixpost_api_tokens')->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->integer('response_code');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->timestamps();
            
            $table->index(['token_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_api_logs');
        Schema::dropIfExists('mixpost_api_tokens');
    }
};
