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
        // Add first_comment column to post_versions table
        Schema::table('mixpost_post_versions', function (Blueprint $table) {
            $table->text('first_comment')->nullable()->after('content');
        });

        // Create table for thread posts (for X, Mastodon, Bluesky threads)
        Schema::create('mixpost_post_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('mixpost_posts')->onDelete('cascade');
            $table->foreignId('account_id')->nullable();
            $table->integer('order')->default(0);
            $table->json('content'); // Same structure as post version content
            $table->text('first_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mixpost_post_versions', function (Blueprint $table) {
            $table->dropColumn('first_comment');
        });

        Schema::dropIfExists('mixpost_post_threads');
    }
};
