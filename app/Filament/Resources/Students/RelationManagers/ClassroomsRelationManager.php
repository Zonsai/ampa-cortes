<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Classroom;
use Carbon\Carbon;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassroomsRelationManager extends RelationManager
{
    protected static string $relationship = 'classrooms';

    protected static ?string $title = 'Historial de clases';

    protected static ?string $modelLabel = 'clase';

    protected static ?string $pluralModelLabel = 'clases';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('enrolled_at', 'desc')
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Año académico')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Clase')
                    ->getStateUsing(fn (Classroom $record): string => $record->full_name),
                TextColumn::make('tutor')
                    ->label('Tutor/a')
                    ->placeholder('—'),
                TextColumn::make('enrolled_at')
                    ->label('Inscrito/a el')
                    ->getStateUsing(fn (Classroom $record): ?string => $record->pivot?->enrolled_at
                        ? Carbon::parse($record->pivot->enrolled_at)->format('d/m/Y')
                        : null)
                    ->placeholder('—'),
            ]);
    }
}
