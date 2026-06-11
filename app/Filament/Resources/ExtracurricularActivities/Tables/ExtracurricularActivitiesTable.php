<?php

namespace App\Filament\Resources\ExtracurricularActivities\Tables;

use App\Enums\ActivityStatus;
use App\Exports\Enrollments\EnrollmentsByActivityExport;
use App\Models\AcademicYear;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class ExtracurricularActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Año académico')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (ActivityStatus $state) => $state->color()),
                IconColumn::make('is_visible_for_families')
                    ->label('Visible')
                    ->boolean(),
                IconColumn::make('requires_ampa_membership')
                    ->label('Solo socios')
                    ->boolean(),
                TextColumn::make('activityGroups_count')
                    ->label('Grupos')
                    ->counts('activityGroups'),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Año académico')
                    ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ActivityStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('exportEnrollments')
                    ->label('Exportar inscritos')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($record) => Excel::download(
                        new EnrollmentsByActivityExport($record->id),
                        'inscritos-'.str($record->name)->slug().'.xlsx',
                    )),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
