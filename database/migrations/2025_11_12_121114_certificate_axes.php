<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_axes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "المسار الاستراتيجي"
            $table->text('description')->nullable();
            $table->enum('path', ['strategic', 'operational', 'hr']);
            $table->decimal('weight', 8, 2)->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_axes');
    }
};
