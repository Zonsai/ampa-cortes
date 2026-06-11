<?php

namespace App\Filament\Resources\ExtracurricularActivities\RelationManagers;

use App\Enums\ActivityGroupStatus;
use App\Exports\Enrollments\EnrollmentsByGroupExport;
use App\Exports\Enrollments\WaitlistExport;
use App\Models\Grade;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del grupo')
                    ->maxLength(100)
                    ->required(),
                CheckboxList::make('weekdays')
                    ->label('Días de la semana')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                    ])
                    ->required()
                    ->columns(5),
                TimePicker::make('starts_at')
                    ->label('Hora inicio')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('ends_at')
                    ->label('Hora fin')
                    ->seconds(false)
                    ->required(),
                TextInput::make('max_spots')
                    ->label('Plazas máximas')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('price_member')
                    ->label('Precio socio (€)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('price_non_member')
                    ->label('Precio no socio (€)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('provider')
                    ->label('Empresa/Proveedor')
                    ->maxLength(100),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->maxLength(150),
                Select::make('status')
                    ->label('Estado')
                    ->options(ActivityGroupStatus::class)
                    ->default(ActivityGroupStatus::Open->value)
                    ->required(),
                Select::make('grades')
                    ->label('Cursos admitidos')
                    ->multiple()
                    ->options(Grade::query()->orderBy('sort_order')->pluck('name', 'id'))
                    ->relationship('grades', 'name'),
            ]);
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
