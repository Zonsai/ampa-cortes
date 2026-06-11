<?php

namespace App\Http\Controllers\Familia;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\EnrollStudentAction;
use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnrollmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'integer'],
            'activity_group_id' => ['required', 'integer'],
        ]);

        $family = $request->user()->family;

        // Seguridad: verificar que el alumno pertenece a esta familia
        $student = $family->students()->find($request->integer('student_id'));
        if (! $student) {
            abort(403, 'No puedes inscribir a este alumno/a.');
        }

        $group = ActivityGroup::with('activity')->find($request->integer('activity_group_id'));
        if (! $group) {
            abort(404);
        }

        // Verificar que la actividad está publicada y visible
        if (
            $group->activity->status !== ActivityStatus::Published
            || ! $group->activity->is_visible_for_families
        ) {
            abort(403);
        }

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        try {
            $enrollment = app(EnrollStudentAction::class)->execute(
                student: $student,
                group: $group,
                family: $family,
                academicYear: $activeYear,
                familyNotes: $request->string('family_notes')->toString() ?: null,
            );

            $message = $enrollment->status === EnrollmentStatus::Waitlist
                ? 'Has quedado en lista de espera. Te avisaremos si queda una plaza libre.'
                : '¡Inscripción realizada correctamente!';

            return redirect()
                ->route('familia.activities.show', $group->activity_id)
                ->with('success', $message);

        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->with('error', collect($e->errors())->flatten()->first());
        }
    }

    public function cancel(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $family = $request->user()->family;

        // Seguridad: verificar que la inscripción pertenece a esta familia
        if ($enrollment->family_id !== $family->id) {
            abort(403);
        }

        // Las familias solo pueden cancelar Pending y Waitlist
        $cancellableByFamily = [EnrollmentStatus::Pending, EnrollmentStatus::Waitlist];

        if (! in_array($enrollment->status, $cancellableByFamily)) {
            return redirect()
                ->back()
                ->with('error', 'Esta inscripción no puede cancelarse desde el portal. Contacta con el AMPA si necesitas darla de baja.');
        }

        try {
            app(CancelEnrollmentAction::class)->execute($enrollment);

            return redirect()
                ->route('familia.children')
                ->with('success', 'Inscripción cancelada correctamente.');

        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->with('error', collect($e->errors())->flatten()->first());
        }
    }
}
