<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Models\Guardian;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';

    protected static ?string $title = 'Tutores/as legales';

    protected static ?string $modelLabel = 'tutor/a';

    protected static ?string $pluralModelLabel = 'tutores/as';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->getStateUsing(fn (Guardian $record): string => $record->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('relationship')
                    ->label('Parentesco')
                    ->placeholder('—'),
                TextColumn::make('user_id')
                    ->label('Usuario')
                    ->badge()
                    ->getStateUsing(fn (Guardian $record): string => $record->user_id ? 'Vinculado' : '—')
                    ->color(fn (Guardian $record): string => $record->user_id ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
