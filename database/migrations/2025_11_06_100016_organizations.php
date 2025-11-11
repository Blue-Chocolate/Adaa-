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
Schema::create('organizations', function (Blueprint $table) {
    $table->id();

    // Ownership
    $table->foreignId('user_id')->constrained()->onDelete('cascade');

    // General Info
    $table->string('name');
    $table->string('sector')->nullable();
    $table->date('established_at')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->string('address')->nullable();
    $table->string('license_number')->nullable();
    $table->string('executive_name')->nullable();

    // Honorary Shield Track
    $table->decimal('shield_percentage', 5, 2)->nullable()->comment('Percentage determining the honorary shield level');
    $table->enum('shield_rank', ['bronze', 'silver', 'gold', 'diamond'])->nullable()->comment('Honorary shield rank based on percentage');

    

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
