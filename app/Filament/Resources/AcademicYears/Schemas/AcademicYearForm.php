<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(20)
                    ->placeholder('2025-2026')
                    ->unique(ignoreRecord: true),
                DatePicker::make('starts_at')
                    ->label('Inicio')
                    ->required()
                    ->displayFormat('d/m/Y'),
                DatePicker::make('ends_at')
                    ->label('Fin')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->label('Curso activo')
                    ->default(false),
            ]);
    }
}
