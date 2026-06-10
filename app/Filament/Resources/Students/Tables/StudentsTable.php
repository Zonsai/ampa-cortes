<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('full_name')
                    ->label('Alumno/a')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),
                \Filament\Tables\Columns\TextColumn::make('family.name')
                    ->label('Familia')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('birth_date')
                    ->label('F. nacimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo/a')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('guardians_count')
                    ->label('Tutores/as')
                    ->counts('guardians'),
            ])
            ->defaultSort('last_name')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('family_id')
                    ->label('Familia')
                    ->relationship('family', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('is_active')
                    ->label('Solo activos')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->default(),
                TrashedFilter::make(),
            ])
            ->recordActions([
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
