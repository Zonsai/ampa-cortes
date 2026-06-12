<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consent_response_id')->constrained()->restrictOnDelete();
            $table->foreignId('consent_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('consent_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event_type');
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            // Append-only log — only created_at, no updated_at.
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_histories');
    }
};
