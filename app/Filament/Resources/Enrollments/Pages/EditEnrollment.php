<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->record->wasChanged(['attendance_from', 'attendance_until'])) {
            return;
        }

        app(AuditLogger::class)->log(
            AuditLog::ENROLLMENT_ATTENDANCE_UPDATED,
            $this->record,
            'Fechas de asistencia actualizadas',
            [
                'attendance_from' => $this->record->attendance_from?->toDateString(),
                'attendance_until' => $this->record->attendance_until?->toDateString(),
            ],
            subjectLabel: EnrollmentAuditData::label($this->record),
        );
    }
}
