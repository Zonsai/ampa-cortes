<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoDataSeeder solo puede ejecutarse en entorno local.');

            return;
        }

        $activeYear = AcademicYear::where('is_active', true)->first();

        // School structure
        $primaria = SchoolStage::firstOrCreate(
            ['name' => 'Educación Primaria'],
            ['sort_order' => 2]
        );

        $infantil = SchoolStage::firstOrCreate(
            ['name' => 'Educación Infantil'],
            ['sort_order' => 1]
        );

        $grades = [
            ['school_stage_id' => $infantil->id, 'name' => '3 años', 'sort_order' => 1],
            ['school_stage_id' => $infantil->id, 'name' => '4 años', 'sort_order' => 2],
            ['school_stage_id' => $infantil->id, 'name' => '5 años', 'sort_order' => 3],
            ['school_stage_id' => $primaria->id, 'name' => '1º Primaria', 'sort_order' => 4],
            ['school_stage_id' => $primaria->id, 'name' => '2º Primaria', 'sort_order' => 5],
            ['school_stage_id' => $primaria->id, 'name' => '3º Primaria', 'sort_order' => 6],
            ['school_stage_id' => $primaria->id, 'name' => '4º Primaria', 'sort_order' => 7],
            ['school_stage_id' => $primaria->id, 'name' => '5º Primaria', 'sort_order' => 8],
            ['school_stage_id' => $primaria->id, 'name' => '6º Primaria', 'sort_order' => 9],
        ];

        foreach ($grades as $gradeData) {
            $grade = Grade::firstOrCreate(
                ['school_stage_id' => $gradeData['school_stage_id'], 'name' => $gradeData['name']],
                ['sort_order' => $gradeData['sort_order']]
            );

            Classroom::firstOrCreate(
                ['academic_year_id' => $activeYear->id, 'grade_id' => $grade->id, 'name' => 'A'],
            );
        }

        // Demo family
        $family = Family::firstOrCreate(
            ['name' => 'García López'],
            [
                'is_ampa_member' => true,
                'ampa_member_since' => '2023-09-01',
            ]
        );

        $guardian = Guardian::firstOrCreate(
            ['email' => 'demo@ampa.test'],
            [
                'family_id' => $family->id,
                'first_name' => 'María',
                'last_name' => 'García López',
                'phone' => '666 111 222',
                'relationship' => 'madre',
            ]
        );

        $grade1 = Grade::where('name', '1º Primaria')->first();
        $classroom = Classroom::where('academic_year_id', $activeYear->id)
            ->where('grade_id', $grade1?->id)
            ->first();

        $student = Student::firstOrCreate(
            ['first_name' => 'Pablo', 'last_name' => 'García López'],
            [
                'family_id' => $family->id,
                'birth_date' => '2018-03-15',
                'is_active' => true,
            ]
        );

        if ($classroom) {
            $student->classrooms()->syncWithoutDetaching([
                $classroom->id => ['enrolled_at' => now()->toDateString()],
            ]);
        }
    }
}
