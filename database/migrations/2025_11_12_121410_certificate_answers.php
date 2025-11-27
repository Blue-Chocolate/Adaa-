<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
 Schema::create('certificate_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('certificate_question_id')->constrained('certificate_questions')->onDelete('cascade');
            $table->string('selected_option');
            $table->decimal('points', 8, 2)->nullable(); // Base points from mapping
            $table->decimal('final_points', 8, 2)->nullable(); // Points × weight
            $table->string('attachment_path')->nullable();
            $table->string('attachment_url')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_answers');
    }
};
