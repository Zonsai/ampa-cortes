<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'Alumnos/as';

    protected static ?string $modelLabel = 'alumno/a';

    protected static ?string $pluralModelLabel = 'alumnos/as';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->getStateUsing(fn (Student $record): string => $record->last_name.', '.$record->first_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('is_active')
                    ->label('Activo/a')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activo/a' : 'Inactivo/a')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('current_classroom')
                    ->label('Clase actual')
                    ->getStateUsing(function (Student $record): string {
                        $cc = $record->currentClassroom();

                        return $cc ? ($cc->grade?->name.' '.$cc->name) : '—';
                    }),
                TextColumn::make('birth_date')
                    ->label('Nacimiento')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('editar')
                    ->label('Ir al alumno/a')
                    ->icon(Heroicon::PencilSquare)
                    ->color('gray')
                    ->url(fn (Student $record): string => StudentResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
