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
        Schema::create('care_requests', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('entity_type', ['individual', 'organization','company']);
            $table->text('message');
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
