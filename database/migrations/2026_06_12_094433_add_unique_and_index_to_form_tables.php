<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE fra FROM form_response_answers fra
                INNER JOIN form_response_answers fra2
                    ON fra.form_response_id = fra2.form_response_id
                    AND fra.form_field_id = fra2.form_field_id
                    AND fra.id > fra2.id
            ');
        }

        Schema::table('form_response_answers', function (Blueprint $table) {
            $table->unique(
                ['form_response_id', 'form_field_id'],
                'form_response_answers_response_field_unique'
            );
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->index(
                ['status', 'academic_year_id'],
                'forms_status_academic_year_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('form_response_answers', function (Blueprint $table) {
            $table->dropUnique('form_response_answers_response_field_unique');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->dropIndex('forms_status_academic_year_index');
        });
    }
};
