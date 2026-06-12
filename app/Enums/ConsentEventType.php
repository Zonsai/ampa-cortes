<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConsentEventType: string implements HasLabel
{
    case Published = 'published';
    case PendingCreated = 'pending_created';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Revoked = 'revoked';
    case NewVersionRequired = 'new_version_required';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Publicado',
            self::PendingCreated => 'Pendiente creado',
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Revoked => 'Revocado',
            self::NewVersionRequired => 'Nueva versión requerida',
            self::Archived => 'Archivado',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
