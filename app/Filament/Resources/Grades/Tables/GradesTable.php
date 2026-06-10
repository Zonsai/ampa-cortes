<?php

namespace App\Filament\Resources\Grades\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class GradesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('schoolStage.name')
                    ->label('Etapa')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Curso/nivel')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('school_stage_id')
                    ->label('Etapa')
                    ->relationship('schoolStage', 'name'),
            ])
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
