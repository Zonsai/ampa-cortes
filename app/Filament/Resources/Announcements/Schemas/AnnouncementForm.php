<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contenido')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->columnSpanFull(),
                    TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'announcements', column: 'slug', ignoreRecord: true)
                        ->helperText('Se genera a partir del título; puedes ajustarlo.')
                        ->columnSpanFull(),
                    Textarea::make('summary')
                        ->label('Resumen')
                        ->rows(2)
                        ->maxLength(255)
                        ->helperText('Texto breve para el listado y vistas previas.')
                        ->columnSpanFull(),
                    Textarea::make('content')
                        ->label('Contenido')
                        ->rows(10)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make('Publicación')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Estado')
                        ->options(AnnouncementStatus::class)
                        ->default(AnnouncementStatus::Draft)
                        ->required(),
                    Select::make('audience')
                        ->label('Audiencia')
                        ->options(AnnouncementAudience::class)
                        ->default(AnnouncementAudience::Public)
                        ->required(),
                    Toggle::make('is_pinned')
                        ->label('Destacar (fijar arriba)')
                        ->default(false),
                    DateTimePicker::make('published_at')
                        ->label('Fecha de publicación')
                        ->helperText('Si es futura, no se mostrará hasta esa fecha.'),
                    DateTimePicker::make('expires_at')
                        ->label('Fecha de caducidad')
                        ->helperText('Tras esta fecha dejará de mostrarse. Opcional.'),
                ]),
        ]);
    }
}
