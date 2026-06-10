<?php

namespace App\Filament\Resources\Guardians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Tutor/a legal')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),
                TextColumn::make('family.name')
                    ->label('Familia')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('relationship')
                    ->label('Relación')
                    ->badge(),
                TextColumn::make('students_count')
                    ->label('Alumnos/as')
                    ->counts('students')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name')
            ->filters([
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
