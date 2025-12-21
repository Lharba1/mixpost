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
        // Available languages
        Schema::create('mixpost_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // e.g., 'en', 'fr', 'es', 'de'
            $table->string('name'); // e.g., 'English', 'French'
            $table->string('native_name')->nullable(); // e.g., 'English', 'Français'
            $table->string('flag_emoji', 10)->nullable(); // e.g., '🇺🇸', '🇫🇷'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_rtl')->default(false); // Right-to-left
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Post translations (different language versions of a post)
        Schema::create('mixpost_post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('mixpost_posts')->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->json('content'); // Same structure as post version content
            $table->boolean('is_auto_translated')->default(false);
            $table->string('translation_provider')->nullable(); // e.g., 'google', 'deepl', 'openai'
            $table->timestamps();
            
            $table->unique(['post_id', 'language_code']);
            $table->index('language_code');
        });

        // Translation memory (cache of previously translated phrases)
        Schema::create('mixpost_translation_memory', function (Blueprint $table) {
            $table->id();
            $table->string('source_language', 10);
            $table->string('target_language', 10);
            $table->text('source_text');
            $table->text('translated_text');
            $table->string('source_hash', 64); // MD5/SHA hash for quick lookup
            $table->timestamps();
            
            $table->index(['source_language', 'target_language', 'source_hash'], 'translation_memory_lookup_idx');
        });

        // Seed default languages
        if (Schema::hasTable('mixpost_languages')) {
            $languages = [
                ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag_emoji' => '🇺🇸', 'is_default' => true, 'sort_order' => 1],
                ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag_emoji' => '🇪🇸', 'sort_order' => 2],
                ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'flag_emoji' => '🇫🇷', 'sort_order' => 3],
                ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'flag_emoji' => '🇩🇪', 'sort_order' => 4],
                ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'flag_emoji' => '🇧🇷', 'sort_order' => 5],
                ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'flag_emoji' => '🇮🇹', 'sort_order' => 6],
                ['code' => 'nl', 'name' => 'Dutch', 'native_name' => 'Nederlands', 'flag_emoji' => '🇳🇱', 'sort_order' => 7],
                ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'flag_emoji' => '🇸🇦', 'is_rtl' => true, 'sort_order' => 8],
                ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'flag_emoji' => '🇨🇳', 'sort_order' => 9],
                ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'flag_emoji' => '🇯🇵', 'sort_order' => 10],
            ];

            foreach ($languages as $lang) {
                \DB::table('mixpost_languages')->insert(array_merge($lang, [
                    'is_active' => true,
                    'is_default' => $lang['is_default'] ?? false,
                    'is_rtl' => $lang['is_rtl'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_translation_memory');
        Schema::dropIfExists('mixpost_post_translations');
        Schema::dropIfExists('mixpost_languages');
    }
};
