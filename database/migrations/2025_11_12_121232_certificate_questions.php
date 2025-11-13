<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_axis_id')->constrained('certificate_axes')->onDelete('cascade');
            $table->text('question_text');
            $table->json('options'); // ["قبل شهر 3", "بعد شهر 3", ...]
            $table->json('points_mapping'); // {"قبل شهر 3": 15, "بعد شهر 3": 10, ...}
            $table->boolean('attachment_required')->default(false);
            $table->enum('path', ['strategic', 'operational', 'hr']);
            $table->decimal('weight', 8, 2)->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_questions');
    }
};
