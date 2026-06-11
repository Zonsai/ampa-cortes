<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->string('response_scope')->default('per_family');
            $table->string('target_type')->default('all_families');
            $table->boolean('allow_edit')->default(false);
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
