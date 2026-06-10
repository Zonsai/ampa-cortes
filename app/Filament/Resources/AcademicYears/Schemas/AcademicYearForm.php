<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(20)
                    ->placeholder('2025-2026'),
                \Filament\Forms\Components\DatePicker::make('starts_at')
                    ->label('Inicio')
                    ->required()
                    ->displayFormat('d/m/Y'),
                \Filament\Forms\Components\DatePicker::make('ends_at')
                    ->label('Fin')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->after('starts_at'),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Curso activo')
                    ->default(false),
            ]);
    }
}
