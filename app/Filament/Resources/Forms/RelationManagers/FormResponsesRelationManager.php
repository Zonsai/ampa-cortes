<?php

namespace App\Filament\Resources\Forms\RelationManagers;

use App\Exports\Forms\FormResponsesExport;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class FormResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'formResponses';

    protected static ?string $title = 'Respuestas';

    protected static ?string $modelLabel = 'respuesta';

    protected static ?string $pluralModelLabel = 'respuestas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->searchable(),
                TextColumn::make('student_name')
                    ->label('Alumno/a')
                    ->getStateUsing(fn ($record) => $record->student
                        ? $record->student->last_name.', '.$record->student->first_name
                        : '—'),
                TextColumn::make('submitted_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('answers_count')
                    ->label('Campos respondidos')
                    ->counts('answers'),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => Excel::download(
                        new FormResponsesExport($this->ownerRecord),
                        'respuestas-'.str($this->ownerRecord->title)->slug().'.xlsx',
                    )),
            ]);
    }
}
