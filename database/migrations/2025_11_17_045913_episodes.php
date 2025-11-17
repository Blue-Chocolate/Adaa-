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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();

            // Create column FIRST
            $table->unsignedBigInteger('podcasts_id');

            // Then foreign key
            $table->foreign('podcasts_id')
                  ->references('id')
                  ->on('podcasts')
                  ->onDelete('cascade');

            $table->string('title');
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('release_date');

            $table->string('video_file_path')->nullable();
            $table->string('audio_file_path')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
