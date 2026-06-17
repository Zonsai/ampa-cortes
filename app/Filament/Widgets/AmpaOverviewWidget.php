<?php

namespace App\Filament\Widgets;

use App\Enums\ConsentResponseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FormStatus;
use App\Models\AcademicYear;
use App\Models\ConsentResponse;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AmpaOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -3;

    /**
     * Rendered synchronously (cheap aggregate counts) so the overview is visible
     * immediately and easily testable; no live polling needed for a dashboard.
     */
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Resumen del AMPA';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'junta_ampa',
            'admin_extraescolares',
            'admin_formularios',
        ]) ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $canPeople = $user?->hasAnyRole(['super_admin', 'junta_ampa']) ?? false;
        $canExtra = $user?->hasAnyRole(['super_admin', 'junta_ampa', 'admin_extraescolares']) ?? false;
        $canForms = $user?->hasAnyRole(['super_admin', 'junta_ampa', 'admin_formularios']) ?? false;

        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $stats = [$this->academicYearStat($activeYear)];

        if ($canPeople) {
            $stats = array_merge($stats, $this->peopleStats($activeYear));
        }

        if ($canExtra) {
            $stats = array_merge($stats, $this->extracurricularStats($activeYear));
        }

        if ($canForms) {
            $stats = array_merge($stats, $this->formsAndConsentsStats($activeYear));
        }

        return $stats;
    }

    private function academicYearStat(?AcademicYear $activeYear): Stat
    {
        if ($activeYear === null) {
            return Stat::make('Curso académico', 'Sin curso activo')
                ->description('Configura un año académico activo en Ajustes')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }

        return Stat::make('Curso académico', $activeYear->name)
            ->description('Curso activo')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('primary');
    }

    /** @return array<int, Stat> */
    private function peopleStats(?AcademicYear $activeYear): array
    {
        $totalFamilies = Family::query()->count();
        $memberFamilies = Family::query()->where('is_ampa_member', true)->count();
        $memberRatio = $totalFamilies > 0 ? round($memberFamilies / $totalFamilies * 100) : 0;

        $activeStudents = Student::query()->where('is_active', true)->count();

        $withClassroom = 0;
        if ($activeYear !== null) {
            $withClassroom = Student::query()
                ->where('is_active', true)
                ->whereHas('classrooms', fn (Builder $q) => $q->where('academic_year_id', $activeYear->id))
                ->count();
        }
        $withoutClassroom = max(0, $activeStudents - $withClassroom);

        return [
            Stat::make('Familias', (string) $totalFamilies)
                ->description("{$memberFamilies} socias AMPA ({$memberRatio}%)")
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),

            Stat::make('Alumnos/as', (string) $activeStudents)
                ->description("{$withClassroom} con clase · {$withoutClassroom} sin clase")
                ->descriptionIcon('heroicon-m-users')
                ->color($withoutClassroom > 0 ? 'warning' : 'success'),
        ];
    }

    /** @return array<int, Stat> */
    private function extracurricularStats(?AcademicYear $activeYear): array
    {
        $base = fn (): Builder => $activeYear !== null
            ? Enrollment::query()->where('academic_year_id', $activeYear->id)
            : Enrollment::query();

        $pending = (clone $base())->where('status', EnrollmentStatus::Pending->value)->count();
        $enrolled = (clone $base())->whereIn('status', [
            EnrollmentStatus::Enrolled->value,
            EnrollmentStatus::Paid->value,
        ])->count();
        $waitlist = (clone $base())->where('status', EnrollmentStatus::Waitlist->value)->count();
        $pendingPayment = (clone $base())->where('status', EnrollmentStatus::PendingPayment->value)->count();
        $pendingAmount = (float) (clone $base())->where('status', EnrollmentStatus::PendingPayment->value)->sum('amount');

        return [
            Stat::make('Solicitudes pendientes', (string) $pending)
                ->description('Extraescolares por confirmar')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($pending > 0 ? 'info' : 'gray'),

            Stat::make('Inscritos/as', (string) $enrolled)
                ->description('Confirmados (inscritos y pagados)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Lista de espera', (string) $waitlist)
                ->description('A la espera de plaza')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($waitlist > 0 ? 'warning' : 'gray'),

            Stat::make('Pagos pendientes', (string) $pendingPayment)
                ->description($this->formatMoney($pendingAmount).' pendientes de cobro')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingPayment > 0 ? 'warning' : 'gray'),
        ];
    }

    /** @return array<int, Stat> */
    private function formsAndConsentsStats(?AcademicYear $activeYear): array
    {
        $openForms = Form::query()
            ->where('status', FormStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()));

        if ($activeYear !== null) {
            $openForms->where('academic_year_id', $activeYear->id);
        }
        $openFormsCount = $openForms->count();
        $totalResponses = FormResponse::query()->count();

        $pendingConsents = ConsentResponse::query()->where('status', ConsentResponseStatus::Pending->value)->count();
        $acceptedConsents = ConsentResponse::query()->where('status', ConsentResponseStatus::Accepted->value)->count();
        $rejectedConsents = ConsentResponse::query()->where('status', ConsentResponseStatus::Rejected->value)->count();
        $revokedConsents = ConsentResponse::query()->where('status', ConsentResponseStatus::Revoked->value)->count();
        $pendingImageReview = ConsentResponse::query()
            ->where('status', ConsentResponseStatus::Pending->value)
            ->whereHas('consentType', fn (Builder $q) => $q->where('requires_image_review', true))
            ->count();

        $pendingDescription = 'Por revisar por las familias';
        if ($pendingImageReview > 0) {
            $pendingDescription = "{$pendingImageReview} requieren revisión de imagen";
        }

        return [
            Stat::make('Formularios abiertos', (string) $openFormsCount)
                ->description("{$totalResponses} respuestas recibidas")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Consentimientos pendientes', (string) $pendingConsents)
                ->description($pendingDescription)
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($pendingConsents > 0 ? 'warning' : 'gray'),

            Stat::make('Consentimientos aceptados', (string) $acceptedConsents)
                ->description("{$rejectedConsents} rechazados · {$revokedConsents} revocados")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),
        ];
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' €';
    }
}
