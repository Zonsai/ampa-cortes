<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_target_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('targetable_type');
            $table->unsignedBigInteger('targetable_id');
            $table->timestamps();

            $table->index(['targetable_type', 'targetable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_target_items');
    }
};
