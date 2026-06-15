<?php

namespace Database\Seeders;

use App\Actions\Consents\RevokeConsentAction;
use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FormFieldType;
use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\FormResponseAnswer;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('LocalDemoSeeder solo puede ejecutarse en entorno local.');

            return;
        }

        $this->seedUsers();
        $year = $this->ensureAcademicStructure();
        [$garcia, $martinez] = $this->seedFamilies();
        [$pablo, $laura, $lucia, $juan] = $this->seedStudents($garcia, $martinez, $year);
        $this->seedExtracurricular($garcia, $martinez, $pablo, $laura, $juan, $year);
        $this->seedForm($garcia, $year);
        $this->seedConsents($garcia, $martinez, $pablo, $laura, $lucia, $juan);

        $this->command?->info('LocalDemoSeeder completado correctamente.');
    }

    // -------------------------------------------------------------------------
    // Usuarios
    // -------------------------------------------------------------------------

    private function seedUsers(): void
    {
        $users = [
            ['email' => 'admin@ampa.test',         'name' => 'Administrador AMPA',    'role' => 'super_admin'],
            ['email' => 'junta@ampa.test',          'name' => 'Junta AMPA',            'role' => 'junta_ampa'],
            ['email' => 'formularios@ampa.test',    'name' => 'Admin Formularios',     'role' => 'admin_formularios'],
            ['email' => 'extraescolares@ampa.test', 'name' => 'Admin Extraescolares',  'role' => 'admin_extraescolares'],
            ['email' => 'familia@ampa.test',        'name' => 'Familia García López',  'role' => 'familia'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Estructura académica
    // -------------------------------------------------------------------------

    private function ensureAcademicStructure(): AcademicYear
    {
        $year = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::firstOrCreate(
                ['name' => '2025-2026'],
                ['starts_at' => '2025-09-08', 'ends_at' => '2026-06-19', 'is_active' => true]
            );

        $infantil = SchoolStage::firstOrCreate(
            ['name' => 'Educación Infantil'],
            ['sort_order' => 1]
        );

        $primaria = SchoolStage::firstOrCreate(
            ['name' => 'Educación Primaria'],
            ['sort_order' => 2]
        );

        $gradeDefs = [
            [$infantil->id, '3 años',       1],
            [$infantil->id, '4 años',       2],
            [$infantil->id, '5 años',       3],
            [$primaria->id, '1º Primaria',  4],
            [$primaria->id, '2º Primaria',  5],
            [$primaria->id, '3º Primaria',  6],
            [$primaria->id, '4º Primaria',  7],
            [$primaria->id, '5º Primaria',  8],
            [$primaria->id, '6º Primaria',  9],
        ];

        foreach ($gradeDefs as [$stageId, $name, $sortOrder]) {
            $grade = Grade::firstOrCreate(
                ['school_stage_id' => $stageId, 'name' => $name],
                ['sort_order' => $sortOrder]
            );

            Classroom::firstOrCreate(
                ['academic_year_id' => $year->id, 'grade_id' => $grade->id, 'name' => 'A']
            );
        }

        return $year;
    }

    // -------------------------------------------------------------------------
    // Familias y tutores
    // -------------------------------------------------------------------------

    /**
     * @return array{Family, Family}
     */
    private function seedFamilies(): array
    {
        $familiaUser = User::where('email', 'familia@ampa.test')->first();

        // Familia principal: García López
        $garcia = Family::firstOrCreate(
            ['name' => 'García López'],
            ['is_ampa_member' => true, 'ampa_member_since' => '2023-09-01']
        );

        $guardianGarcia = Guardian::firstOrCreate(
            ['email' => 'demo@ampa.test'],
            [
                'family_id' => $garcia->id,
                'first_name' => 'María',
                'last_name' => 'García López',
                'phone' => '666 111 222',
                'relationship' => 'madre',
            ]
        );

        // Enlaza familia@ampa.test al tutor para que el login funcione
        if ($familiaUser && $guardianGarcia->user_id !== $familiaUser->id) {
            $guardianGarcia->update(['user_id' => $familiaUser->id]);
        }

        // Segunda familia: Martínez Ruiz (para probar cross-family)
        $martinez = Family::firstOrCreate(
            ['name' => 'Martínez Ruiz'],
            ['is_ampa_member' => false]
        );

        Guardian::firstOrCreate(
            ['email' => 'ana.martinez@ampa.test'],
            [
                'family_id' => $martinez->id,
                'first_name' => 'Ana',
                'last_name' => 'Martínez Ruiz',
                'phone' => '666 333 444',
                'relationship' => 'madre',
            ]
        );

        return [$garcia, $martinez];
    }

    // -------------------------------------------------------------------------
    // Alumnos
    // -------------------------------------------------------------------------

    /**
     * @return array{Student, Student, Student, Student}
     */
    private function seedStudents(Family $garcia, Family $martinez, AcademicYear $year): array
    {
        $classroom = fn (string $gradeName): ?Classroom => Classroom::where('academic_year_id', $year->id)
            ->where('grade_id', Grade::firstWhere('name', $gradeName)?->id)
            ->first();

        $attachClassroom = function (Student $student, ?Classroom $class): void {
            if ($class && ! $student->classrooms()->where('classrooms.id', $class->id)->exists()) {
                $student->classrooms()->attach($class->id, ['enrolled_at' => now()->toDateString()]);
            }
        };

        // Pablo García López — 1º Primaria A
        $pablo = Student::firstOrCreate(
            ['family_id' => $garcia->id, 'first_name' => 'Pablo', 'last_name' => 'García López'],
            ['birth_date' => '2018-03-15', 'is_active' => true]
        );
        $attachClassroom($pablo, $classroom('1º Primaria'));

        // Laura García López — 2º Primaria A
        $laura = Student::firstOrCreate(
            ['family_id' => $garcia->id, 'first_name' => 'Laura', 'last_name' => 'García López'],
            ['birth_date' => '2017-06-20', 'is_active' => true]
        );
        $attachClassroom($laura, $classroom('2º Primaria'));

        // Lucía Demo García — sin clase (caso de prueba "Sin clase asignada este curso")
        $lucia = Student::firstOrCreate(
            ['family_id' => $garcia->id, 'first_name' => 'Lucía Demo', 'last_name' => 'García'],
            ['birth_date' => '2020-01-10', 'is_active' => true]
        );

        // Juan Villarreal Martínez — 3º Primaria A (familia Martínez Ruiz)
        $juan = Student::firstOrCreate(
            ['family_id' => $martinez->id, 'first_name' => 'Juan Villarreal', 'last_name' => 'Martínez'],
            ['birth_date' => '2016-11-05', 'is_active' => true]
        );
        $attachClassroom($juan, $classroom('3º Primaria'));

        return [$pablo, $laura, $lucia, $juan];
    }

    // -------------------------------------------------------------------------
    // Extraescolar
    // -------------------------------------------------------------------------

    private function seedExtracurricular(
        Family $garcia,
        Family $martinez,
        Student $pablo,
        Student $laura,
        Student $juan,
        AcademicYear $year,
    ): void {
        $activity = ExtracurricularActivity::firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Inglés'],
            [
                'short_description' => 'Clases de inglés para alumnos de primaria.',
                'status' => ActivityStatus::Published,
                'is_visible_for_families' => true,
                'requires_ampa_membership' => false,
            ]
        );

        if (! $activity->wasRecentlyCreated) {
            $activity->update(['status' => ActivityStatus::Published, 'is_visible_for_families' => true]);
        }

        $grade1 = Grade::firstWhere('name', '1º Primaria');
        $grade2 = Grade::firstWhere('name', '2º Primaria');
        $grade3 = Grade::firstWhere('name', '3º Primaria');

        $group = ActivityGroup::firstOrCreate(
            ['activity_id' => $activity->id, 'name' => 'Lunes y miércoles'],
            [
                'weekdays' => [1, 3],
                'starts_at' => '16:00:00',
                'ends_at' => '17:00:00',
                'max_spots' => 10,
                'price_member' => 25.00,
                'price_non_member' => 35.00,
                'provider' => 'Academia Oxford',
                'location' => 'Aula B-04',
                'status' => ActivityGroupStatus::Open,
            ]
        );

        $gradeIds = array_filter([$grade1?->id, $grade2?->id, $grade3?->id]);
        if ($gradeIds) {
            $group->grades()->syncWithoutDetaching($gradeIds);
        }

        // Pablo: inscrito (spot ocupado)
        $this->ensureEnrollment($pablo, $garcia, $activity, $group, $year,
            EnrollmentStatus::Enrolled, 25.00, PriceType::Member
        );

        // Laura: lista de espera
        $this->ensureEnrollment($laura, $garcia, $activity, $group, $year,
            EnrollmentStatus::Waitlist, 25.00, PriceType::Member, 1
        );

        // Juan (cross-family, no socio): inscrito
        $this->ensureEnrollment($juan, $martinez, $activity, $group, $year,
            EnrollmentStatus::Enrolled, 35.00, PriceType::NonMember
        );
    }

    private function ensureEnrollment(
        Student $student,
        Family $family,
        ExtracurricularActivity $activity,
        ActivityGroup $group,
        AcademicYear $year,
        EnrollmentStatus $status,
        float $amount,
        PriceType $priceType,
        ?int $waitlistPosition = null,
    ): void {
        $activeValues = array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses());

        $exists = Enrollment::where('student_id', $student->id)
            ->where('activity_group_id', $group->id)
            ->whereIn('status', $activeValues)
            ->exists();

        if ($exists) {
            return;
        }

        Enrollment::create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'status' => $status,
            'registered_at' => now()->subDays(3),
            'enrolled_at' => $status === EnrollmentStatus::Enrolled ? now()->subDays(3) : null,
            'amount' => $amount,
            'price_type' => $priceType,
            'waitlist_position' => $waitlistPosition,
        ]);
    }

    // -------------------------------------------------------------------------
    // Formulario
    // -------------------------------------------------------------------------

    private function seedForm(Family $garcia, AcademicYear $year): void
    {
        $form = Form::firstOrCreate(
            ['title' => 'Excursión fin de curso', 'academic_year_id' => $year->id],
            [
                'description' => 'Autorización y preferencias para la excursión de fin de curso.',
                'status' => FormStatus::Published,
                'response_scope' => FormResponseScope::PerFamily,
                'target_type' => FormTargetType::AllFamilies,
                'allow_edit' => false,
                'opens_at' => now()->subDays(7),
                'closes_at' => now()->addDays(30),
            ]
        );

        if (! $form->wasRecentlyCreated) {
            $form->update(['status' => FormStatus::Published]);
        }

        $fieldInfo = FormField::firstOrCreate(
            ['form_id' => $form->id, 'label' => 'Información importante'],
            [
                'type' => FormFieldType::InfoText,
                'description' => 'La excursión tendrá lugar el 15 de junio. El precio incluye transporte y entrada al museo.',
                'is_required' => false,
                'sort_order' => 1,
            ]
        );

        $fieldObs = FormField::firstOrCreate(
            ['form_id' => $form->id, 'label' => 'Observaciones'],
            [
                'type' => FormFieldType::TextShort,
                'description' => 'Alergias, medicamentos u otras indicaciones relevantes.',
                'is_required' => true,
                'sort_order' => 2,
            ]
        );

        $fieldAuth = FormField::firstOrCreate(
            ['form_id' => $form->id, 'label' => 'Autorizo la asistencia de mi hijo/a'],
            [
                'type' => FormFieldType::Radio,
                'is_required' => true,
                'sort_order' => 3,
                'options' => ['Sí, autorizo la asistencia', 'No autorizo la asistencia'],
            ]
        );

        $fieldNeeds = FormField::firstOrCreate(
            ['form_id' => $form->id, 'label' => 'Necesidades especiales'],
            [
                'type' => FormFieldType::Checkboxes,
                'is_required' => false,
                'sort_order' => 4,
                'options' => ['Alergia alimentaria', 'Movilidad reducida', 'Dieta especial', 'Ninguna'],
            ]
        );

        $responseKey = FormResponse::buildResponseKey($garcia);

        $response = FormResponse::firstOrCreate(
            ['form_id' => $form->id, 'family_id' => $garcia->id, 'response_key' => $responseKey],
            ['submitted_at' => now()->subDays(2)]
        );

        if ($response->wasRecentlyCreated) {
            // $fieldInfo (InfoText) no almacena respuesta
            FormResponseAnswer::create([
                'form_response_id' => $response->id,
                'form_field_id' => $fieldObs->id,
                'value' => 'Sin alergias conocidas.',
            ]);
            FormResponseAnswer::create([
                'form_response_id' => $response->id,
                'form_field_id' => $fieldAuth->id,
                'value' => 'Sí, autorizo la asistencia',
            ]);
            FormResponseAnswer::create([
                'form_response_id' => $response->id,
                'form_field_id' => $fieldNeeds->id,
                'value' => json_encode(['Ninguna']),
            ]);
        }

        // Referencia para silenciar el warning de variable no usada (fieldInfo solo es demo visual)
        unset($fieldInfo);
    }

    // -------------------------------------------------------------------------
    // Consentimientos
    // -------------------------------------------------------------------------

    private function seedConsents(
        Family $garcia,
        Family $martinez,
        Student $pablo,
        Student $laura,
        Student $lucia,
        Student $juan,
    ): void {
        // --- Consentimiento 1: por familia ---
        $type1 = ConsentType::firstOrCreate(
            ['name' => 'Autorización general de comunicaciones AMPA'],
            [
                'purpose' => 'Autorización para recibir comunicaciones del AMPA por email y WhatsApp.',
                'scope' => ConsentScope::PerFamily,
                'is_rejectable' => true,
                'is_revocable' => true,
                'requires_image_review' => false,
                'status' => ConsentTypeStatus::Draft,
                'sort_order' => 1,
            ]
        );

        $version1 = ConsentVersion::firstOrCreate(
            ['consent_type_id' => $type1->id, 'version_number' => 1],
            [
                'legal_text' => 'El AMPA del CEIP Cortés, en su condición de responsable del tratamiento, tratará sus datos de contacto con la finalidad de mantenerle informado/a de las actividades, noticias y eventos del AMPA y del centro escolar. Los datos no serán cedidos a terceros sin su consentimiento expreso.',
                'summary' => 'Autorizo al AMPA a contactarme por email y WhatsApp para informarme de actividades y noticias del centro.',
                'published_at' => now()->subDays(30),
                'effective_from' => now()->subDays(30)->toDateString(),
            ]
        );

        $this->ensureConsentPublished($type1, $version1);

        $this->ensureConsentResponse($type1, $version1, $garcia, null, ConsentResponseStatus::Accepted);
        $this->ensureConsentResponse($type1, $version1, $martinez, null, ConsentResponseStatus::Pending);

        // --- Consentimiento 2: por alumno/a — con revisión de imagen ---
        $type2 = ConsentType::firstOrCreate(
            ['name' => 'Autorización de uso de imagen'],
            [
                'purpose' => 'Autorización para publicar fotografías o vídeos del alumno/a en medios del AMPA.',
                'scope' => ConsentScope::PerStudent,
                'is_rejectable' => true,
                'is_revocable' => true,
                'requires_image_review' => true,
                'status' => ConsentTypeStatus::Draft,
                'sort_order' => 2,
            ]
        );

        $version2 = ConsentVersion::firstOrCreate(
            ['consent_type_id' => $type2->id, 'version_number' => 1],
            [
                'legal_text' => 'El AMPA del CEIP Cortés podrá publicar imágenes o vídeos en los que aparezca el alumno/a en el contexto de actividades escolares y extraescolares gestionadas por el AMPA, en su página web, redes sociales y boletines informativos. En ningún caso se cederán las imágenes a terceros con fines comerciales.',
                'summary' => 'Autorizo la publicación de imágenes de mi hijo/a en los medios del AMPA (web, redes sociales, boletín).',
                'published_at' => now()->subDays(25),
                'effective_from' => now()->subDays(25)->toDateString(),
            ]
        );

        $this->ensureConsentPublished($type2, $version2);

        $this->ensureConsentResponse($type2, $version2, $garcia, $pablo, ConsentResponseStatus::Accepted);
        $this->ensureConsentResponse($type2, $version2, $garcia, $laura, ConsentResponseStatus::Pending);
        $this->ensureConsentResponse($type2, $version2, $garcia, $lucia, ConsentResponseStatus::Revoked);
        $this->ensureConsentResponse($type2, $version2, $martinez, $juan, ConsentResponseStatus::Pending);
    }

    private function ensureConsentPublished(ConsentType $type, ConsentVersion $version): void
    {
        if ($type->status === ConsentTypeStatus::Draft) {
            $type->update(['status' => ConsentTypeStatus::Published]);
        }

        if (! $version->published_at) {
            $version->update(['published_at' => now()]);
        }
    }

    private function ensureConsentResponse(
        ConsentType $type,
        ConsentVersion $version,
        Family $family,
        ?Student $student,
        ConsentResponseStatus $finalStatus,
    ): ConsentResponse {
        $existing = ConsentResponse::where('consent_type_id', $type->id)
            ->where('family_id', $family->id)
            ->where('student_id', $student?->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => $student?->id,
            'subject_key' => ConsentResponse::buildSubjectKey($family, $student),
            'status' => $finalStatus,
            'responded_at' => $finalStatus !== ConsentResponseStatus::Pending ? now()->subDays(20) : null,
            'revoked_at' => $finalStatus === ConsentResponseStatus::Revoked ? now()->subDays(5) : null,
        ]);

        $this->createHistoryChain($response, $type, $version, $family, $student, $finalStatus);

        return $response;
    }

    private function createHistoryChain(
        ConsentResponse $response,
        ConsentType $type,
        ConsentVersion $version,
        Family $family,
        ?Student $student,
        ConsentResponseStatus $finalStatus,
    ): void {
        $base = [
            'consent_response_id' => $response->id,
            'consent_version_id' => $version->id,
            'consent_type_id' => $type->id,
            'family_id' => $family->id,
            'student_id' => $student?->id,
        ];

        ConsentHistory::create(array_merge($base, [
            'event_type' => ConsentEventType::PendingCreated,
            'notes' => "Consentimiento publicado — versión {$version->version_number}",
        ]));

        if (in_array($finalStatus, [ConsentResponseStatus::Accepted, ConsentResponseStatus::Revoked])) {
            ConsentHistory::create(array_merge($base, ['event_type' => ConsentEventType::Accepted]));
        }

        if ($finalStatus === ConsentResponseStatus::Rejected) {
            ConsentHistory::create(array_merge($base, ['event_type' => ConsentEventType::Rejected]));
        }

        if ($finalStatus === ConsentResponseStatus::Revoked) {
            ConsentHistory::create(array_merge($base, [
                'event_type' => ConsentEventType::Revoked,
                'notes' => $type->requires_image_review ? RevokeConsentAction::IMAGE_REVIEW_NOTE : null,
            ]));
        }
    }
}
