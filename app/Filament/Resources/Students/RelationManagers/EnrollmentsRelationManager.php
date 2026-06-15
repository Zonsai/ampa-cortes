<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Inscripciones a extraescolares';

    protected static ?string $modelLabel = 'inscripción';

    protected static ?string $pluralModelLabel = 'inscripciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Actividad')
                    ->searchable(),
                TextColumn::make('activityGroup.name')
                    ->label('Grupo')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR')
                    ->placeholder('—'),
                TextColumn::make('registered_at')
                    ->label('Fecha solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('waitlist_position')
                    ->label('Pos. espera')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class),
            ]);
    }
}
