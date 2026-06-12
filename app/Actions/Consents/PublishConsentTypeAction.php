<?php

namespace App\Actions\Consents;

use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishConsentTypeAction
{
    /**
     * Publishes a consent type and its first version, creating pending responses
     * for all applicable families or active students.
     *
     * @throws ValidationException
     */
    public function execute(ConsentType $type, ConsentVersion $version): void
    {
        if ($type->status !== ConsentTypeStatus::Draft) {
            throw ValidationException::withMessages([
                'consent_type' => 'El tipo de consentimiento ya está publicado o archivado.',
            ]);
        }

        if ($version->consent_type_id !== $type->id) {
            throw ValidationException::withMessages([
                'consent_version' => 'La versión no pertenece a este tipo de consentimiento.',
            ]);
        }

        DB::transaction(function () use ($type, $version) {
            $version->update(['published_at' => now()]);
            $type->update(['status' => ConsentTypeStatus::Published]);

            if ($type->scope === ConsentScope::PerFamily) {
                $this->createPendingForFamilies($type, $version);
            } else {
                $this->createPendingForStudents($type, $version);
            }
        });
    }

    private function createPendingForFamilies(ConsentType $type, ConsentVersion $version): void
    {
        Family::query()->each(function (Family $family) use ($type, $version) {
            $subjectKey = ConsentResponse::buildSubjectKey($family, null);

            $response = ConsentResponse::create([
                'consent_type_id' => $type->id,
                'consent_version_id' => $version->id,
                'family_id' => $family->id,
                'student_id' => null,
                'subject_key' => $subjectKey,
                'status' => ConsentResponseStatus::Pending,
            ]);

            ConsentHistory::create([
                'consent_response_id' => $response->id,
                'consent_version_id' => $version->id,
                'consent_type_id' => $type->id,
                'family_id' => $family->id,
                'student_id' => null,
                'event_type' => ConsentEventType::PendingCreated,
                'performed_by_id' => null,
                'notes' => "Consentimiento publicado — versión {$version->version_number}",
            ]);
        });
    }

    private function createPendingForStudents(ConsentType $type, ConsentVersion $version): void
    {
        Student::where('is_active', true)->each(function (Student $student) use ($type, $version) {
            $subjectKey = ConsentResponse::buildSubjectKey($student->family, $student);

            $response = ConsentResponse::create([
                'consent_type_id' => $type->id,
                'consent_version_id' => $version->id,
                'family_id' => $student->family_id,
                'student_id' => $student->id,
                'subject_key' => $subjectKey,
                'status' => ConsentResponseStatus::Pending,
            ]);

            ConsentHistory::create([
                'consent_response_id' => $response->id,
                'consent_version_id' => $version->id,
                'consent_type_id' => $type->id,
                'family_id' => $student->family_id,
                'student_id' => $student->id,
                'event_type' => ConsentEventType::PendingCreated,
                'performed_by_id' => null,
                'notes' => "Consentimiento publicado — versión {$version->version_number}",
            ]);
        });
    }
}
