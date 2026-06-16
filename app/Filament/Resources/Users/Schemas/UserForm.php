<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Guardian;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de acceso')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'users', column: 'email', ignoreRecord: true),
                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->minLength(8)
                        ->same('password_confirmation')
                        ->dehydrated(fn ($state) => filled($state))
                        ->columnSpanFull(),
                    TextInput::make('password_confirmation')
                        ->label('Confirmar contraseña')
                        ->password()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Roles y acceso')
                ->columns(2)
                ->schema([
                    Select::make('roles_input')
                        ->label('Roles')
                        ->multiple()
                        ->options(function () {
                            $options = [
                                'junta_ampa' => 'Junta AMPA',
                                'admin_extraescolares' => 'Admin Extraescolares',
                                'admin_formularios' => 'Admin Formularios',
                                'familia' => 'Familia',
                            ];
                            if (auth()->user()?->hasRole('super_admin')) {
                                $options = ['super_admin' => 'Super Admin'] + $options;
                            }

                            return $options;
                        })
                        ->afterStateHydrated(function (Select $component, $record) {
                            $component->state($record?->roles->pluck('name')->toArray() ?? []);
                        })
                        ->live(),
                    Toggle::make('is_active')
                        ->label('Cuenta activa')
                        ->default(true)
                        ->helperText('Los usuarios inactivos no pueden iniciar sesión.'),
                    Toggle::make('is_email_verified')
                        ->label('Email verificado')
                        ->default(true)
                        ->afterStateHydrated(function (Toggle $component, $record) {
                            $component->state($record?->email_verified_at !== null);
                        }),
                ]),

            Section::make('Vinculación familiar')
                ->description('Solo aplicable a usuarios con rol Familia.')
                ->schema([
                    Select::make('guardian_id')
                        ->label('Tutor/a legal vinculado/a')
                        ->placeholder('Seleccionar tutor/a...')
                        ->options(function ($record) {
                            return Guardian::query()
                                ->where(function ($q) use ($record) {
                                    $q->whereNull('user_id');
                                    if ($record?->id) {
                                        $q->orWhere('user_id', $record->id);
                                    }
                                })
                                ->with('family')
                                ->get()
                                ->mapWithKeys(fn (Guardian $g) => [
                                    $g->id => "{$g->full_name} — {$g->family?->name}",
                                ]);
                        })
                        ->afterStateHydrated(function (Select $component, $record) {
                            $component->state($record?->guardian?->id);
                        })
                        ->searchable()
                        ->nullable(),
                ])
                ->visible(fn (Get $get) => in_array('familia', $get('roles_input') ?? [])),
        ]);
    }
}
