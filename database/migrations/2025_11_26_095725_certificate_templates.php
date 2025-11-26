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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Modern, Classic, Elegant
            $table->string('style'); // modern, classic, elegant
            $table->boolean('is_active')->default(true);
            
            // Background settings
            $table->string('background_color')->default('#ffffff');
            $table->string('background_image')->nullable();
            
            // Border settings (JSON for each rank)
            $table->json('borders'); // {diamond: {color, width, style}, gold: {...}, ...}
            
            // Logo settings
            $table->json('logo_settings'); // {position: 'top-center', size: 80}
            
            // Text elements (JSON array)
            $table->json('elements'); // Array of text elements with position, font, etc.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
