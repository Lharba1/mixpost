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
        // Webhook endpoints
        Schema::create('mixpost_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable(); // For signing payloads
            $table->json('events'); // Array of events to trigger on
            $table->boolean('is_active')->default(true);
            $table->json('headers')->nullable(); // Custom headers
            $table->integer('timeout')->default(30); // Request timeout in seconds
            $table->integer('retry_count')->default(3);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Webhook delivery logs
        Schema::create('mixpost_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('mixpost_webhooks')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('status'); // pending, success, failed
            $table->integer('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['webhook_id', 'status']);
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_webhook_deliveries');
        Schema::dropIfExists('mixpost_webhooks');
    }
};
