<?php

namespace App\Filament\Resources\ConsentTypes\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsentVersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versiones del texto legal';

    protected static ?string $modelLabel = 'versión';

    protected static ?string $pluralModelLabel = 'versiones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->defaultSort('version_number', 'desc')
            ->columns([
                TextColumn::make('version_number')
                    ->label('Versión')
                    ->sortable(),
                TextColumn::make('summary')
                    ->label('Resumen')
                    ->limit(60)
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label('Publicado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->label('Vigente desde')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->placeholder('Sistema'),
            ])
            ->recordActions([
                Action::make('ver_texto')
                    ->label('Ver texto')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->fillForm(fn ($record): array => [
                        'legal_text' => $record->legal_text,
                    ])
                    ->schema([
                        Textarea::make('legal_text')
                            ->label('Texto legal')
                            ->rows(12)
                            ->disabled(),
                    ])
                    ->modalHeading(fn ($record): string => "Versión {$record->version_number} — texto legal")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }
}
