<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormTargetType: string implements HasLabel
{
    case AllFamilies = 'all_families';
    case AmpaMembers = 'ampa_members';
    case ByStage = 'by_stage';
    case ByGrade = 'by_grade';
    case ByClassroom = 'by_classroom';
    case ByActivity = 'by_activity';
    case ByGroup = 'by_group';

    public function label(): string
    {
        return match ($this) {
            self::AllFamilies => 'Todas las familias',
            self::AmpaMembers => 'Solo socios AMPA',
            self::ByStage => 'Por etapa',
            self::ByGrade => 'Por curso/nivel',
            self::ByClassroom => 'Por clase',
            self::ByActivity => 'Por actividad extraescolar',
            self::ByGroup => 'Por grupo de actividad',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /** Returns true when this target type requires form_target_items rows. */
    public function requiresTargetItems(): bool
    {
        return ! in_array($this, [self::AllFamilies, self::AmpaMembers]);
    }
}
