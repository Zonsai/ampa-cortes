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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->foreignId('activity_id')
                ->constrained('extracurricular_activities')
                ->restrictOnDelete();
            $table->foreignId('activity_group_id')
                ->constrained('activity_groups')
                ->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('enrolled');
            $table->dateTime('registered_at');
            $table->dateTime('enrolled_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('price_type', 20)->nullable();
            $table->unsignedSmallInteger('waitlist_position')->nullable();
            $table->text('family_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
