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
        Schema::create('school_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);    // Infantil, Primaria, Secundaria
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_stages');
    }
};
