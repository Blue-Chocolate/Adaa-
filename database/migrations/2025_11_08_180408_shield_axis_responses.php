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
       Schema::create('shield_axis_responses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->foreignId('shield_axis_id')->constrained('shield_axes')->onDelete('cascade');
    $table->json('answers')->nullable(); // لتخزين الإجابات لكل سؤال بصيغة {"q1":true,"q2":false,...}
    $table->decimal('admin_score', 5, 2)->nullable(); // النقاط بعد مراجعة الادمن لكل محور
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
