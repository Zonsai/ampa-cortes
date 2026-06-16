<?php

namespace App\Filament\Pages;

use App\Services\AppSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Ajustes de marca';

    protected static ?string $title = 'Ajustes de marca';

    protected string $view = 'filament.pages.branding-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(AppSettings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('ampa_name')
                            ->label('Nombre del AMPA')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('school_name')
                            ->label('Nombre del colegio')
                            ->required()
                            ->maxLength(100),
                    ]),
                Section::make('Logos')
                    ->columns(2)
                    ->columnSpanFull()
                    ->description('Formatos admitidos: JPG, PNG, SVG, WebP. Tamaño máximo: 2 MB.')
                    ->schema([
                        FileUpload::make('ampa_logo_path')
                            ->label('Logo del AMPA')
                            ->disk('public')
                            ->directory('branding')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'])
                            ->maxSize(2048)
                            ->nullable(),
                        FileUpload::make('school_logo_path')
                            ->label('Logo del colegio')
                            ->disk('public')
                            ->directory('branding')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'])
                            ->maxSize(2048)
                            ->nullable(),
                    ]),
                Section::make('Colores')
                    ->columns(2)
                    ->columnSpanFull()
                    ->description('Los colores se aplican en la zona familiar y la landing pública.')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Color principal')
                            ->rules(['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']),
                        ColorPicker::make('accent_color')
                            ->label('Color acento')
                            ->rules(['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            AppSettings::set($key, $value);
        }

        Notification::make()
            ->title('Ajustes de marca guardados')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar ajustes')
                ->action('save')
                ->color('primary'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'junta_ampa']) ?? false;
    }
}
