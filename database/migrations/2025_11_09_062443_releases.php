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
        Schema::create('releases', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('file_path');
    $table->string('excel_path')->nullable();
    $table->string('powerbi_path')->nullable();
    $table->text('description')->nullable();
    $table->string('image', 255)->nullable();
    $table->foreignId('release_category_id')->constrained('releases_categories')->onDelete('cascade');
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
