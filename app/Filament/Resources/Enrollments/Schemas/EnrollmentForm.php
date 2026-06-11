<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alumno/a y familia')
                    ->columns(2)
                    ->schema([
                        Select::make('family_id')
                            ->label('Familia')
                            ->options(Family::query()->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('student_id')
                            ->label('Alumno/a')
                            ->options(fn (Get $get): array => Student::query()
                                ->when($get('family_id'), fn ($q, $familyId) => $q->where('family_id', $familyId))
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn ($s) => [$s->id => $s->last_name.', '.$s->first_name])
                                ->all()
                            )
                            ->required()
                            ->searchable(),
                    ]),
                Section::make('Actividad')
                    ->columns(2)
                    ->schema([
                        Select::make('academic_year_id')
                            ->label('Año académico')
                            ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('activity_id')
                            ->label('Actividad')
                            ->options(fn (Get $get): array => ExtracurricularActivity::query()
                                ->when($get('academic_year_id'), fn ($q, $yearId) => $q->where('academic_year_id', $yearId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('activity_group_id')
                            ->label('Grupo')
                            ->options(fn (Get $get): array => ActivityGroup::query()
                                ->when($get('activity_id'), fn ($q, $actId) => $q->where('activity_id', $actId))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($g) => [$g->id => $g->name.' ('.$g->weekdaysLabel().' '.substr((string) $g->starts_at, 0, 5).')'])
                                ->all()
                            )
                            ->required()
                            ->searchable(),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('family_notes')
                            ->label('Notas de la familia')
                            ->rows(3),
                        Textarea::make('internal_notes')
                            ->label('Notas internas')
                            ->rows(3),
                    ]),
            ]);
    }
}
