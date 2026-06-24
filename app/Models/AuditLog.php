<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    // ── Action keys (stable identifiers used for filtering) ──────────────────
    public const PAYMENT_REGISTERED = 'payment.registered';

    public const PAYMENT_VOIDED = 'payment.voided';

    public const PAYMENT_MARKED_PENDING = 'payment.marked_pending';

    public const ENROLLMENT_CONFIRMED = 'enrollment.confirmed';

    public const ENROLLMENT_WAITLISTED = 'enrollment.waitlisted';

    public const ENROLLMENT_PROMOTED = 'enrollment.promoted';

    public const ENROLLMENT_CANCELLED = 'enrollment.cancelled';

    public const ENROLLMENT_DROPPED = 'enrollment.dropped';

    public const ENROLLMENT_REQUESTED = 'enrollment.requested';

    public const ENROLLMENT_REQUESTED_WAITLIST = 'enrollment.requested_waitlist';

    public const ENROLLMENT_ATTENDANCE_UPDATED = 'enrollment.attendance_updated';

    public const GROUP_EXCEPTION_CREATED = 'group_exception.created';

    public const GROUP_EXCEPTION_UPDATED = 'group_exception.updated';

    public const GROUP_EXCEPTION_DELETED = 'group_exception.deleted';

    public const USER_CREATED = 'user.created';

    public const USER_PASSWORD_RESET = 'user.password_reset';

    public const USER_ACTIVATED = 'user.activated';

    public const USER_DEACTIVATED = 'user.deactivated';

    public const FAMILY_ACCESS_CREATED = 'user.family_access_created';

    public const FORM_CREATED = 'form.created';

    public const FORM_UPDATED = 'form.updated';

    public const FORM_CLONED = 'form.cloned';

    public const CONSENT_PUBLISHED = 'consent.published';

    public const CONSENT_NEW_VERSION = 'consent.new_version';

    public const BRANDING_UPDATED = 'branding.updated';

    public const ANNOUNCEMENT_CREATED = 'announcement.created';

    public const ANNOUNCEMENT_UPDATED = 'announcement.updated';

    public const ANNOUNCEMENT_PUBLISHED = 'announcement.published';

    public const ANNOUNCEMENT_ARCHIVED = 'announcement.archived';

    public const ANNOUNCEMENT_DELETED = 'announcement.deleted';

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Human-readable labels for the known action keys (used in the admin UI).
     *
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::PAYMENT_REGISTERED => 'Pago registrado',
            self::PAYMENT_VOIDED => 'Pago anulado',
            self::PAYMENT_MARKED_PENDING => 'Marcado pendiente de pago',
            self::ENROLLMENT_CONFIRMED => 'Solicitud confirmada',
            self::ENROLLMENT_WAITLISTED => 'Enviado a lista de espera',
            self::ENROLLMENT_PROMOTED => 'Pasado a inscrito/a',
            self::ENROLLMENT_CANCELLED => 'Inscripción cancelada',
            self::ENROLLMENT_DROPPED => 'Inscripción dada de baja',
            self::ENROLLMENT_REQUESTED => 'Solicitud familiar',
            self::ENROLLMENT_REQUESTED_WAITLIST => 'Solicitud familiar (lista de espera)',
            self::ENROLLMENT_ATTENDANCE_UPDATED => 'Fechas de asistencia actualizadas',
            self::GROUP_EXCEPTION_CREATED => 'Excepción de grupo creada',
            self::GROUP_EXCEPTION_UPDATED => 'Excepción de grupo editada',
            self::GROUP_EXCEPTION_DELETED => 'Excepción de grupo borrada',
            self::USER_CREATED => 'Usuario creado',
            self::USER_PASSWORD_RESET => 'Contraseña restablecida',
            self::USER_ACTIVATED => 'Usuario activado',
            self::USER_DEACTIVATED => 'Usuario desactivado',
            self::FAMILY_ACCESS_CREATED => 'Acceso familiar creado',
            self::FORM_CREATED => 'Formulario creado',
            self::FORM_UPDATED => 'Formulario editado',
            self::FORM_CLONED => 'Formulario clonado',
            self::CONSENT_PUBLISHED => 'Consentimiento publicado',
            self::CONSENT_NEW_VERSION => 'Nueva versión de consentimiento',
            self::BRANDING_UPDATED => 'Marca actualizada',
            self::ANNOUNCEMENT_CREATED => 'Anuncio creado',
            self::ANNOUNCEMENT_UPDATED => 'Anuncio editado',
            self::ANNOUNCEMENT_PUBLISHED => 'Anuncio publicado',
            self::ANNOUNCEMENT_ARCHIVED => 'Anuncio archivado',
            self::ANNOUNCEMENT_DELETED => 'Anuncio borrado',
        ];
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? $this->action;
    }
}
