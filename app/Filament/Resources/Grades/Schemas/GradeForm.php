<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Schemas\Schema;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('school_stage_id')
                    ->label('Etapa educativa')
                    ->relationship('schoolStage', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nombre del curso/nivel')
                    ->required()
                    ->maxLength(60)
                    ->placeholder('1º Primaria'),
                \Filament\Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
