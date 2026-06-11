<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityGroupStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Closed = 'closed';
    case Full = 'full';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Closed => 'Cerrado',
            self::Full => 'Completo',
            self::Archived => 'Archivado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'warning',
            self::Full => 'danger',
            self::Archived => 'gray',
        };
    }

    /** Only Open and Full are managed automatically based on spot count. */
    public function isAutoManaged(): bool
    {
        return in_array($this, [self::Open, self::Full]);
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
