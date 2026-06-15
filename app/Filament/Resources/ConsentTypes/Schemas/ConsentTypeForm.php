<?php

namespace App\Filament\Resources\ConsentTypes\Schemas;

use App\Enums\ConsentScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConsentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),
                        Select::make('scope')
                            ->label('Alcance')
                            ->options(ConsentScope::class)
                            ->default(ConsentScope::PerFamily->value)
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
                Section::make('Comportamiento')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_rejectable')
                            ->label('Permite rechazo')
                            ->default(true),
                        Toggle::make('is_revocable')
                            ->label('Permite revocación')
                            ->default(true),
                        Toggle::make('requires_image_review')
                            ->label('Requiere revisión de imagen al revocar')
                            ->default(false)
                            ->helperText('Si está activado, se dejará una nota interna en el histórico cuando se revoque este consentimiento.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Descripción')
                    ->schema([
                        Textarea::make('purpose')
                            ->label('Propósito / descripción interna')
                            ->rows(3),
                    ]),
            ]);
    }
}
