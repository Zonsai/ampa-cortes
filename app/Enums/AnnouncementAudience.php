<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementAudience: string implements HasColor, HasLabel
{
    case Public = 'public';
    case Families = 'families';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Público',
            self::Families => 'Familias',
            self::Both => 'Público y familias',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Public => 'info',
            self::Families => 'primary',
            self::Both => 'success',
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

    /** Audiences shown on the public site. */
    public static function publicValues(): array
    {
        return [self::Public->value, self::Both->value];
    }

    /** Audiences shown in the family zone. */
    public static function familyValues(): array
    {
        return [self::Families->value, self::Both->value];
    }
}
