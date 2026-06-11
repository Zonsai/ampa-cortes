<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FormStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Closed => 'Cerrado',
            self::Archived => 'Archivado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Closed => 'warning',
            self::Archived => 'danger',
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

    /** Returns true when families can submit or view responses. */
    public function allowsResponses(): bool
    {
        return $this === self::Published;
    }
}
