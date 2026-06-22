<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Models\AcademicYear;
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
                TextColumn::make('paid_at')
                    ->label('Fecha pago')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method')
                    ->label('Método pago')
                    ->formatStateUsing(fn (?PaymentMethod $state): string => $state?->getLabel() ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('academic_year_id')
                    ->label('Año académico')
                    ->relationship('academicYear', 'name')
                    ->default(fn () => AcademicYear::where('is_active', true)->value('id')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EnrollmentStatus::class),
            ]);
    }
}
