<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PriceType: string implements HasColor, HasLabel
{
    case Member = 'member';
    case NonMember = 'non_member';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Socio AMPA',
            self::NonMember => 'No socio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Member => 'success',
            self::NonMember => 'gray',
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
