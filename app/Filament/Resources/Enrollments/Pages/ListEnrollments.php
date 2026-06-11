<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Exports\Enrollments\EnrollmentsByClassroomExport;
use App\Exports\Enrollments\GlobalEnrollmentsExport;
use App\Exports\Enrollments\PendingPaymentsExport;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Maatwebsite\Excel\Facades\Excel;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('exportGlobal')
                ->label('Exportar global')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Año académico')
                        ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(fn (array $data) => Excel::download(
                    new GlobalEnrollmentsExport($data['academic_year_id']),
                    'inscripciones-global-'.$data['academic_year_id'].'.xlsx',
                )),

            Action::make('exportPendingPayments')
                ->label('Exportar pagos pendientes')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Año académico')
                        ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(fn (array $data) => Excel::download(
                    new PendingPaymentsExport($data['academic_year_id']),
                    'pagos-pendientes-'.$data['academic_year_id'].'.xlsx',
                )),

            Action::make('exportByClassroom')
                ->label('Exportar por clase')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Año académico')
                        ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                        ->required()
                        ->live(),
                    Select::make('classroom_id')
                        ->label('Clase')
                        ->options(fn (Get $get): array => Classroom::query()
                            ->when($get('academic_year_id'), fn ($q, $yearId) => $q->where('academic_year_id', $yearId))
                            ->with('grade')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->full_name])
                            ->all()
                        )
                        ->required()
                        ->searchable(),
                ])
                ->action(fn (array $data) => Excel::download(
                    new EnrollmentsByClassroomExport($data['classroom_id'], $data['academic_year_id']),
                    'inscripciones-clase-'.$data['classroom_id'].'.xlsx',
                )),
        ];
    }
}
