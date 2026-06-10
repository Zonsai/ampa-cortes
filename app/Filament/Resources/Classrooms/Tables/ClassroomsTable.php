<?php

namespace App\Filament\Resources\Classrooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ClassroomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Curso escolar')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('grade.schoolStage.name')
                    ->label('Etapa')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('grade.name')
                    ->label('Nivel')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Grupo')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('tutor')
                    ->label('Tutor/a docente')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('students_count')
                    ->label('Alumnos/as')
                    ->counts('students'),
            ])
            ->defaultSort('academic_year_id')
            ->defaultSort('grade_id')
            ->defaultSort('name')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Curso escolar')
                    ->relationship('academicYear', 'name')
                    ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->value('id')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
