<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Actions\Enrollments\EnrollStudentAction;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Family;
use App\Models\Student;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(EnrollStudentAction::class)->execute(
                student: Student::findOrFail($data['student_id']),
                group: ActivityGroup::findOrFail($data['activity_group_id']),
                family: Family::findOrFail($data['family_id']),
                academicYear: AcademicYear::findOrFail($data['academic_year_id']),
                familyNotes: $data['family_notes'] ?? null,
                internalNotes: $data['internal_notes'] ?? null,
            );
        } catch (ValidationException $e) {
            Notification::make()
                ->title('No se pudo registrar la inscripción')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->persistent()
                ->send();

            throw new Halt;
        }
    }
}
