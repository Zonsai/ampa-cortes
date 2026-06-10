<?php

namespace App\Filament\Resources\SchoolStages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SchoolStagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Etapa')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('grades_count')
                    ->label('Cursos/niveles')
                    ->counts('grades'),
            ])
            ->defaultSort('sort_order')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
