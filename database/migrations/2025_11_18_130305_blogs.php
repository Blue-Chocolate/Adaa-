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
                     Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blogs_category_id')->constrained('blogs_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('author')->nullable();
            $table->string('image')->nullable(); // store path in /storage/blogs/images
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
                Schema::dropIfExists('blogs');

    }
};
