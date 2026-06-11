<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('response_key');
            $table->dateTime('submitted_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['form_id', 'response_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_responses');
    }
};
