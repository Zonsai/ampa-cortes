<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consent_type_id')->constrained()->restrictOnDelete();
            $table->integer('version_number');
            $table->longText('legal_text');
            $table->string('summary')->nullable();
            $table->date('effective_from')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['consent_type_id', 'version_number'], 'consent_versions_type_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_versions');
    }
};
