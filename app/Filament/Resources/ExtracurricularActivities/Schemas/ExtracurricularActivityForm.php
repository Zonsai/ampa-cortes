<?php

namespace App\Filament\Resources\ExtracurricularActivities\Schemas;

use App\Enums\ActivityStatus;
use App\Models\AcademicYear;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExtracurricularActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        Select::make('academic_year_id')
                            ->label('Año académico')
                            ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(ActivityStatus::class)
                            ->default(ActivityStatus::Draft->value)
                            ->required(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->maxLength(150)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Descripción')
                    ->schema([
                        TextInput::make('short_description')
                            ->label('Descripción corta')
                            ->maxLength(255)
                            ->required(),
                        Textarea::make('long_description')
                            ->label('Descripción larga')
                            ->rows(4),
                    ]),
                Section::make('Configuración')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_visible_for_families')
                            ->label('Visible para familias')
                            ->default(false),
                        Toggle::make('requires_ampa_membership')
                            ->label('Requiere ser socio AMPA')
                            ->default(false),
                    ]),
                Section::make('Notas internas')
                    ->schema([
                        Textarea::make('internal_notes')
                            ->label('Notas internas')
                            ->rows(3),
                    ]),
            ]);
    }
}
