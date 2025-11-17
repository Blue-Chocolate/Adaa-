<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_schedules', function (Blueprint $table) {
            $table->id();

            // submission
            $table->date('submission_start_date')->nullable();
            $table->date('submission_end_date')->nullable();
            $table->text('submission_note')->nullable();

            // submission_end
            $table->date('submission_end_date_only')->nullable();
            $table->text('submission_end_note')->nullable();

            // evaluation
            $table->date('evaluation_start_date')->nullable();
            $table->date('evaluation_end_date')->nullable();
            $table->text('evaluation_note')->nullable();

            // announcement
            $table->date('announcement_date')->nullable();
            $table->text('announcement_note')->nullable();

            // awarding
            $table->date('awarding_start_date')->nullable();
            $table->date('awarding_end_date')->nullable();
            $table->text('awarding_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_schedules');
    }
};