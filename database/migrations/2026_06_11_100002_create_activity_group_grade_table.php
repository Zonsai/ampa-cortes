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
        Schema::create('activity_group_grade', function (Blueprint $table) {
            $table->foreignId('activity_group_id')
                ->constrained('activity_groups')
                ->cascadeOnDelete();
            $table->foreignId('grade_id')
                ->constrained('grades')
                ->restrictOnDelete();

            $table->primary(['activity_group_id', 'grade_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_group_grade');
    }
};
