<?php

namespace App\Filament\Resources\Forms\RelationManagers;

use App\Enums\FormFieldType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'formFields';

    protected static ?string $title = 'Campos del formulario';

    protected static ?string $modelLabel = 'campo';

    protected static ?string $pluralModelLabel = 'campos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo de campo')
                    ->options(FormFieldType::class)
                    ->required()
                    ->live(),
                TextInput::make('label')
                    ->label('Etiqueta')
                    ->maxLength(255)
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción/Ayuda')
                    ->rows(2),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_required')
                    ->label('Obligatorio')
                    ->default(false),
                Textarea::make('options')
                    ->label('Opciones (una por línea)')
                    ->rows(4)
                    ->hint('Introduce una opción por línea')
                    ->visible(fn (Get $get): bool => FormFieldType::tryFrom($get('type') ?? '')?->requiresOptions() ?? false)
                    ->required(fn (Get $get): bool => FormFieldType::tryFrom($get('type') ?? '')?->requiresOptions() ?? false)
                    ->formatStateUsing(fn (mixed $state): ?string => is_array($state) ? implode("\n", $state) : $state)
                    ->dehydrateStateUsing(fn (?string $state): ?array => $state !== null
                        ? array_values(array_filter(array_map('trim', explode("\n", $state))))
                        : null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('label')
                    ->label('Etiqueta')
                    ->searchable(),
                IconColumn::make('is_required')
                    ->label('Obligatorio')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Añadir campo'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
