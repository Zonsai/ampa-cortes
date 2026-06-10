<?php

namespace App\Filament\Resources\Guardians\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuardianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
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
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(150),
                        TextInput::make('phone')
                            ->label('Teléfono principal')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('phone_alt')
                            ->label('Teléfono alternativo')
                            ->tel()
                            ->maxLength(20),
                        Select::make('relationship')
                            ->label('Relación con el alumno/a')
                            ->options([
                                'madre' => 'Madre',
                                'padre' => 'Padre',
                                'tutor/a' => 'Tutor/a legal',
                                'otro' => 'Otro',
                            ]),
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
