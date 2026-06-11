<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained()->restrictOnDelete();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->index(['form_response_id', 'form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_response_answers');
    }
};
