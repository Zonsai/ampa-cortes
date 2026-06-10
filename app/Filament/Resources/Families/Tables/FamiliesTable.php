<?php

namespace App\Filament\Resources\Families\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Familia')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('guardians_count')
                    ->label('Tutores/as')
                    ->counts('guardians'),
                \Filament\Tables\Columns\TextColumn::make('students_count')
                    ->label('Alumnos/as')
                    ->counts('students'),
                \Filament\Tables\Columns\IconColumn::make('is_ampa_member')
                    ->label('Socio/a AMPA')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('ampa_member_since')
                    ->label('Socio/a desde')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Filter::make('is_ampa_member')
                    ->label('Solo socios AMPA')
                    ->query(fn (Builder $query) => $query->where('is_ampa_member', true)),
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
