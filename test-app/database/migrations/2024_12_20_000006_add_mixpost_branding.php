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
        // White label / branding settings
        Schema::create('mixpost_branding', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, color, boolean
            $table->timestamps();
        });

        // Seed default branding settings
        $defaults = [
            ['key' => 'app_name', 'value' => 'Mixpost', 'type' => 'text'],
            ['key' => 'logo_light', 'value' => null, 'type' => 'image'],
            ['key' => 'logo_dark', 'value' => null, 'type' => 'image'],
            ['key' => 'favicon', 'value' => null, 'type' => 'image'],
            ['key' => 'primary_color', 'value' => '#6366f1', 'type' => 'color'],
            ['key' => 'secondary_color', 'value' => '#8b5cf6', 'type' => 'color'],
            ['key' => 'login_background', 'value' => null, 'type' => 'image'],
            ['key' => 'footer_text', 'value' => null, 'type' => 'text'],
            ['key' => 'hide_powered_by', 'value' => '0', 'type' => 'boolean'],
            ['key' => 'custom_css', 'value' => null, 'type' => 'text'],
        ];

        foreach ($defaults as $setting) {
            \DB::table('mixpost_branding')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixpost_branding');
    }
};
