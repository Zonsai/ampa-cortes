<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConsentScope: string implements HasLabel
{
    case PerFamily = 'per_family';
    case PerStudent = 'per_student';

    public function label(): string
    {
        return match ($this) {
            self::PerFamily => 'Por familia',
            self::PerStudent => 'Por alumno/a',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
