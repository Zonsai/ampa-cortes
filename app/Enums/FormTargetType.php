<?php

namespace App\Enums;

use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\ExtracurricularActivity;
use App\Models\Grade;
use App\Models\SchoolStage;
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

    /** Returns the Eloquent model class to use for morph relationships, or null for non-targeted types. */
    public function morphClass(): ?string
    {
        return match ($this) {
            self::ByStage => SchoolStage::class,
            self::ByGrade => Grade::class,
            self::ByClassroom => Classroom::class,
            self::ByActivity => ExtracurricularActivity::class,
            self::ByGroup => ActivityGroup::class,
            default => null,
        };
    }
}
