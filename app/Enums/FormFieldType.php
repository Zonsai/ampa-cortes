<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormFieldType: string implements HasLabel
{
    case TextShort = 'text_short';
    case TextLong = 'text_long';
    case Number = 'number';
    case Date = 'date';
    case Email = 'email';
    case Phone = 'phone';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Checkboxes = 'checkboxes';
    case YesNo = 'yes_no';
    case InfoText = 'info_text';

    public function label(): string
    {
        return match ($this) {
            self::TextShort => 'Texto corto',
            self::TextLong => 'Texto largo',
            self::Number => 'Número',
            self::Date => 'Fecha',
            self::Email => 'Email',
            self::Phone => 'Teléfono',
            self::Select => 'Lista desplegable',
            self::Radio => 'Opción única (radio)',
            self::Checkbox => 'Casilla única',
            self::Checkboxes => 'Casillas múltiples',
            self::YesNo => 'Sí / No',
            self::InfoText => 'Texto informativo',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /** Returns true when this field type stores a value (i.e. not purely informational). */
    public function storesAnswer(): bool
    {
        return $this !== self::InfoText;
    }

    /** Returns true when options must be defined for this field type. */
    public function requiresOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkboxes]);
    }
}
