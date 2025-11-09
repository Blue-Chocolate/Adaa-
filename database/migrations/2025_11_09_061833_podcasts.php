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
    Schema::create('podcasts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('short_description')->nullable();
    $table->text('description')->nullable();
    $table->string('cover_image')->nullable();
    $table->string('audio_path'); // مكان الملف
    $table->string('video_path')->nullable();
    $table->string('cover')->nullable();
    $table->timestamp('published_at')->nullable(); // add this
    $table->timestamps();
    $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcasts');
    }
};
