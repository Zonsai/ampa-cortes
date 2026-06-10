<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del alumno/a')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('last_name')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(100),
                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->displayFormat('d/m/Y'),
                        Toggle::make('is_active')
                            ->label('Activo/a')
                            ->default(true),
                    ]),
                Section::make('Familia')
                    ->schema([
                        Select::make('family_id')
                            ->label('Familia')
                            ->relationship('family', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Apellidos de la familia')
                                    ->maxLength(150),
                            ]),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3),
                    ]),
            ]);
    }
}
