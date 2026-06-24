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
        Schema::create('activity_group_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_group_id')->constrained()->cascadeOnDelete();
            $table->date('original_date')->nullable();
            $table->string('type');
            $table->date('new_date')->nullable();
            $table->time('new_starts_at')->nullable();
            $table->time('new_ends_at')->nullable();
            $table->string('new_location')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['activity_group_id', 'original_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_group_exceptions');
    }
};
