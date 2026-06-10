<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use Filament\Schemas\Schema;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('academic_year_id')
                    ->label('Curso escolar')
                    ->relationship('academicYear', 'name')
                    ->required()
                    ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->value('id'))
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('grade_id')
                    ->label('Curso/nivel')
                    ->relationship('grade', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Grupo')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('A'),
                \Filament\Forms\Components\TextInput::make('tutor')
                    ->label('Tutor/a docente')
                    ->maxLength(100),
            ]);
    }
}
