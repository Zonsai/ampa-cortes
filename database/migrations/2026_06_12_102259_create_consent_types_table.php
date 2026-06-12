<?php

use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('purpose')->nullable();
            $table->string('scope')->default(ConsentScope::PerFamily->value);
            $table->boolean('is_rejectable')->default(true);
            $table->boolean('is_revocable')->default(true);
            $table->string('status')->default(ConsentTypeStatus::Draft->value);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_types');
    }
};
