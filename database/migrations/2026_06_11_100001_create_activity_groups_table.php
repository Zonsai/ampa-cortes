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
        Schema::create('activity_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')
                ->constrained('extracurricular_activities')
                ->restrictOnDelete();
            $table->string('name', 100);
            $table->json('weekdays');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('max_spots');
            $table->decimal('price_member', 8, 2);
            $table->decimal('price_non_member', 8, 2);
            $table->string('provider', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_groups');
    }
};
