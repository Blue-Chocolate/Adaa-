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
            $table->enum('status', ['approved', 'pending', 'decline'])->default('pending');
                      $table->string('website')->nullable();

            // Honorary Shield Track
            $table->decimal('shield_percentage', 5, 2)->nullable()->comment('Percentage determining the honorary shield level');
            $table->enum('shield_rank', ['bronze', 'silver', 'gold', 'diamond'])->nullable()->comment('Honorary shield rank based on percentage');

            $table->decimal('certificate_final_score', 5, 2)->nullable();
            $table->enum('certificate_final_rank', ['bronze', 'silver', 'gold', 'diamond'])->nullable();

            $table->decimal('certificate_strategic_score', 8, 2)->nullable();
            $table->decimal('certificate_operational_score', 8, 2)->nullable();
            $table->decimal('certificate_hr_score', 8, 2)->nullable();
$table->boolean('certificate_strategic_submitted')->default(false)
                ->comment('Whether strategic path has been submitted for evaluation');
            $table->boolean('certificate_operational_submitted')->default(false)
                ->comment('Whether operational path has been submitted for evaluation');
            $table->boolean('certificate_hr_submitted')->default(false)
                ->comment('Whether HR path has been submitted for evaluation');
            
            // Add submission timestamps
            $table->timestamp('certificate_strategic_submitted_at')->nullable()
                ->comment('When strategic path was submitted');
            $table->timestamp('certificate_operational_submitted_at')->nullable()
                ->comment('When operational path was submitted');
            $table->timestamp('certificate_hr_submitted_at')->nullable()
                ->comment('When HR path was submitted');
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
