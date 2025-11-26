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
       Schema::create('certificate_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->enum('path', ['strategic', 'operational', 'hr']);
    
    // Workflow tracking
    $table->boolean('submitted')->default(false);
    $table->timestamp('submitted_at')->nullable();
    
    $table->boolean('approved')->default(false);
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->text('admin_notes')->nullable();
    
    $table->timestamps();
    
    $table->unique(['organization_id', 'path']);
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
