<?php

use App\Enums\ConsentResponseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consent_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('consent_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('subject_key');
            $table->string('status')->default(ConsentResponseStatus::Pending->value);
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['consent_type_id', 'subject_key'], 'consent_responses_type_subject_unique');
            $table->index('family_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_responses');
    }
};
