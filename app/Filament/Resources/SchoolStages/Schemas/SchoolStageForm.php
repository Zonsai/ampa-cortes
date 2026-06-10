<?php

namespace App\Filament\Resources\SchoolStages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolStageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la etapa')
                    ->required()
                    ->maxLength(60)
                    ->placeholder('Infantil')
                    ->unique(ignoreRecord: true),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
