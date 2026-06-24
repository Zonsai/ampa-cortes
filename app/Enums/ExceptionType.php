<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExceptionType: string implements HasColor, HasLabel
{
    case Cancelled = 'cancelled';
    case Modified = 'modified';
    case Extra = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::Cancelled => 'Cancelada',
            self::Modified => 'Modificada',
            self::Extra => 'Clase extra',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cancelled => 'danger',
            self::Modified => 'warning',
            self::Extra => 'info',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }
}
