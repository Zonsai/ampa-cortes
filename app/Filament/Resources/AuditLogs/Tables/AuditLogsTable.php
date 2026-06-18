<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('actor_name')
                    ->label('Actor')
                    ->description(fn (AuditLog $record): ?string => $record->actor_email)
                    ->placeholder('Sistema')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Acción')
                    ->badge()
                    ->formatStateUsing(fn (AuditLog $record): string => $record->actionLabel())
                    ->searchable(),
                TextColumn::make('subject_label')
                    ->label('Sujeto')
                    ->placeholder('—')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('—')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label('Acción')
                    ->options(AuditLog::actionLabels()),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
