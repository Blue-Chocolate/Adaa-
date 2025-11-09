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
       Schema::create('shield_axes_questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shield_axis_id')->constrained('shield_axes')->onDelete('cascade');
    $table->string('question'); // نص السؤال
    $table->decimal('score', 5, 2)->default(5); // كل سؤال 5 نقاط
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
