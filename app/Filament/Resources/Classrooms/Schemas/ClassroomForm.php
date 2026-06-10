<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use App\Models\AcademicYear;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year_id')
                    ->label('Curso escolar')
                    ->relationship('academicYear', 'name')
                    ->required()
                    ->default(fn () => AcademicYear::where('is_active', true)->value('id'))
                    ->searchable()
                    ->preload(),
                Select::make('grade_id')
                    ->label('Curso/nivel')
                    ->relationship('grade', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label('Grupo')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('A')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get) {
                            return $rule->where('academic_year_id', $get('academic_year_id'))
                                ->where('grade_id', $get('grade_id'));
                        }
                    ),
                TextInput::make('tutor')
                    ->label('Tutor/a docente')
                    ->maxLength(100),
            ]);
    }
}
