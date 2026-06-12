<?php

namespace App\Filament\Resources\Forms\Tables;

use App\Enums\FormStatus;
use App\Models\AcademicYear;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('response_scope')
                    ->label('Alcance')
                    ->badge(),
                TextColumn::make('target_type')
                    ->label('Público')
                    ->badge(),
                TextColumn::make('formResponses_count')
                    ->label('Respuestas')
                    ->counts('formResponses'),
                TextColumn::make('opens_at')
                    ->label('Apertura')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closes_at')
                    ->label('Cierre')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Año académico')
                    ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(FormStatus::class),
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
