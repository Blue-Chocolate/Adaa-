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
        Schema::create('issued_certificates', function (Blueprint $table) {
    $table->id();
    $table->string('certificate_number')->unique();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    
    $table->enum('path', ['strategic', 'operational', 'hr']);
    
    // Snapshot at issuance (so changes to org don't affect certificate)
    $table->string('organization_name');
    $table->string('organization_logo_path')->nullable();
    $table->decimal('score', 8, 2);
    $table->enum('rank', ['bronze', 'silver', 'gold', 'diamond']);
    
    $table->timestamp('issued_at');
    $table->foreignId('issued_by')->constrained('users');
    $table->string('pdf_path')->nullable();
    
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
