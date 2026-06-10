<?php

namespace App\Filament\Resources\Families\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FamilyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->schema([
                        TextInput::make('name')
                            ->label('Apellidos de la familia')
                            ->maxLength(150)
                            ->placeholder('García Martínez'),
                    ]),
                Section::make('AMPA')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_ampa_member')
                            ->label('Socio/a AMPA')
                            ->default(false),
                        DatePicker::make('ampa_member_since')
                            ->label('Socio/a desde')
                            ->displayFormat('d/m/Y'),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ]),
            ]);
    }
}
