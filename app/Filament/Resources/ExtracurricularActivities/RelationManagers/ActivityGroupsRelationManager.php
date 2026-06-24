<?php

namespace App\Filament\Resources\ExtracurricularActivities\RelationManagers;

use App\Enums\ActivityGroupStatus;
use App\Exports\Enrollments\EnrollmentsByGroupExport;
use App\Exports\Enrollments\WaitlistExport;
use App\Filament\Resources\ActivityGroups\ActivityGroupResource;
use App\Filament\Resources\ActivityGroups\Schemas\ActivityGroupForm;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class ActivityGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityGroups';

    protected static ?string $title = 'Grupos';

    protected static ?string $modelLabel = 'grupo';

    protected static ?string $pluralModelLabel = 'grupos';

    public function form(Schema $schema): Schema
    {
        return ActivityGroupForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Grupo')
                    ->searchable(),
                TextColumn::make('weekdaysLabel')
                    ->label('Días')
                    ->getStateUsing(fn ($record) => $record->weekdaysLabel()),
                TextColumn::make('starts_at')
                    ->label('Horario')
                    ->formatStateUsing(fn ($record) => substr((string) $record->starts_at, 0, 5).' – '.substr((string) $record->ends_at, 0, 5)),
                TextColumn::make('max_spots')
                    ->label('Plazas'),
                TextColumn::make('price_member')
                    ->label('Socio')
                    ->money('EUR'),
                TextColumn::make('price_non_member')
                    ->label('No socio')
                    ->money('EUR'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (ActivityGroupStatus $state) => $state->color()),
            ])
            ->headerActions([
                CreateAction::make()->label('Añadir grupo'),
            ])
            ->recordActions([
                Action::make('exportEnrolled')
                    ->label('Exportar inscritos')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($record) => Excel::download(
                        new EnrollmentsByGroupExport($record->id),
                        'inscritos-grupo-'.str($record->name)->slug().'.xlsx',
                    )),
                Action::make('exportWaitlist')
                    ->label('Exportar lista espera')
                    ->icon('heroicon-o-queue-list')
                    ->color('warning')
                    ->action(fn ($record) => Excel::download(
                        new WaitlistExport($record->id),
                        'lista-espera-'.str($record->name)->slug().'.xlsx',
                    )),
                Action::make('exceptions')
                    ->label('Excepciones')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->url(fn ($record) => ActivityGroupResource::getUrl('edit', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
