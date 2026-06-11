<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnrollmentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Enrolled = 'enrolled';
    case Waitlist = 'waitlist';
    case Dropped = 'dropped';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Enrolled => 'Inscrito/a',
            self::Waitlist => 'Lista de espera',
            self::Dropped => 'Baja',
            self::PendingPayment => 'Pendiente de pago',
            self::Paid => 'Pagado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Enrolled => 'success',
            self::Waitlist => 'warning',
            self::Dropped => 'danger',
            self::PendingPayment => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    /** Returns true when this status counts toward occupying a spot. */
    public function occupiesSpot(): bool
    {
        return in_array($this, [self::Enrolled, self::PendingPayment, self::Paid]);
    }

    /**
     * Returns true when this status is considered active — i.e. blocks a new
     * duplicate enrollment for the same student + group combination.
     *
     * Note: Pending is reserved for the future family self-service portal.
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Enrolled,
            self::Waitlist,
            self::PendingPayment,
            self::Paid,
        ]);
    }

    /** @return list<self> */
    public static function activeStatuses(): array
    {
        return array_filter(self::cases(), fn (self $s) => $s->isActive());
    }

    /** @return list<self> */
    public static function spotOccupyingStatuses(): array
    {
        return array_filter(self::cases(), fn (self $s) => $s->occupiesSpot());
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
