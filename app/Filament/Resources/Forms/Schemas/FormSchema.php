<?php

namespace App\Filament\Resources\Forms\Schemas;

use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\ExtracurricularActivity;
use App\Models\Grade;
use App\Models\SchoolStage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FormSchema
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
                            ->options(FormStatus::class)
                            ->default(FormStatus::Draft->value)
                            ->required(),
                        TextInput::make('title')
                            ->label('Título')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Público objetivo')
                    ->columns(2)
                    ->schema([
                        Select::make('response_scope')
                            ->label('Tipo de respuesta')
                            ->options(FormResponseScope::class)
                            ->default(FormResponseScope::PerFamily->value)
                            ->required(),
                        Select::make('target_type')
                            ->label('Dirigido a')
                            ->options(FormTargetType::class)
                            ->default(FormTargetType::AllFamilies->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('target_item_ids', [])),
                        Select::make('target_item_ids')
                            ->label('Destinatarios específicos')
                            ->multiple()
                            ->searchable()
                            ->options(fn (Get $get): array => static::targetItemOptions($get('target_type')))
                            ->visible(fn (Get $get): bool => static::resolveTargetType($get('target_type'))?->requiresTargetItems() ?? false)
                            ->required(fn (Get $get): bool => static::resolveTargetType($get('target_type'))?->requiresTargetItems() ?? false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Disponibilidad')
                    ->columns(2)
                    ->schema([
                        Toggle::make('allow_edit')
                            ->label('Permitir edición de respuesta')
                            ->default(false),
                        DateTimePicker::make('opens_at')
                            ->label('Fecha de apertura')
                            ->seconds(false),
                        DateTimePicker::make('closes_at')
                            ->label('Fecha de cierre')
                            ->seconds(false),
                    ]),
                Section::make('Notas internas')
                    ->schema([
                        Textarea::make('internal_notes')
                            ->label('Notas internas')
                            ->rows(3),
                    ]),
            ]);
    }

    /**
     * Returns the options array for the target_item_ids select based on target_type.
     *
     * @return array<int, string>
     */
    private static function targetItemOptions(mixed $targetType): array
    {
        return match (static::resolveTargetType($targetType)) {
            FormTargetType::ByStage => SchoolStage::query()->orderBy('sort_order')->pluck('name', 'id')->toArray(),
            FormTargetType::ByGrade => Grade::query()->orderBy('sort_order')->pluck('name', 'id')->toArray(),
            FormTargetType::ByClassroom => Classroom::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            FormTargetType::ByActivity => ExtracurricularActivity::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            FormTargetType::ByGroup => ActivityGroup::query()->orderBy('name')->pluck('name', 'id')->toArray(),
            default => [],
        };
    }

    /**
     * Resolves a target type value that may be either a string (form state) or an already-hydrated enum (model fill).
     */
    private static function resolveTargetType(mixed $value): ?FormTargetType
    {
        if ($value instanceof FormTargetType) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return FormTargetType::tryFrom($value);
        }

        return null;
    }
}
