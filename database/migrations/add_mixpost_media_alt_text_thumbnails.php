<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add alt_text column to media table
        if (!Schema::hasColumn('mixpost_media', 'alt_text')) {
            Schema::table('mixpost_media', function (Blueprint $table) {
                $table->string('alt_text', 500)->nullable()->after('conversions');
            });
        }

        // Add thumbnail columns for video thumbnails
        if (!Schema::hasColumn('mixpost_media', 'thumbnail_path')) {
            Schema::table('mixpost_media', function (Blueprint $table) {
                $table->string('thumbnail_path')->nullable()->after('alt_text');
                $table->string('thumbnail_disk')->nullable()->after('thumbnail_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mixpost_media', function (Blueprint $table) {
            $table->dropColumn(['alt_text', 'thumbnail_path', 'thumbnail_disk']);
        });
    }
};
