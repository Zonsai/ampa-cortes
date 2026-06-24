<?php

namespace App\Filament\Resources\ActivityGroups\Schemas;

use App\Enums\ActivityGroupStatus;
use App\Models\Grade;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ActivityGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del grupo')
                    ->maxLength(100)
                    ->required(),
                CheckboxList::make('weekdays')
                    ->label('Días de la semana')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                    ])
                    ->required()
                    ->columns(5),
                TimePicker::make('starts_at')
                    ->label('Hora inicio')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('ends_at')
                    ->label('Hora fin')
                    ->seconds(false)
                    ->required(),
                DatePicker::make('effective_from')
                    ->label('Inicio efectivo')
                    ->helperText('Si se deja vacío, se hereda el inicio del curso académico de la actividad.')
                    ->displayFormat('d/m/Y'),
                DatePicker::make('effective_until')
                    ->label('Fin efectivo')
                    ->helperText('Si se deja vacío, se hereda el fin del curso académico de la actividad.')
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('effective_from'),
                TextInput::make('max_spots')
                    ->label('Plazas máximas')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('price_member')
                    ->label('Precio socio (€)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('price_non_member')
                    ->label('Precio no socio (€)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('provider')
                    ->label('Empresa/Proveedor')
                    ->maxLength(100),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->maxLength(150),
                Select::make('status')
                    ->label('Estado')
                    ->options(ActivityGroupStatus::class)
                    ->default(ActivityGroupStatus::Open->value)
                    ->required(),
                Select::make('grades')
                    ->label('Cursos admitidos')
                    ->multiple()
                    ->options(Grade::query()->orderBy('sort_order')->pluck('name', 'id'))
                    ->relationship('grades', 'name'),
            ]);
    }
}
