<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ConsentType;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Form;
use App\Models\Student;
use App\Models\User;
use App\Services\AppSettings;
use Database\Seeders\LocalDemoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_aborts_in_non_local_environment(): void
    {
        // APP_ENV = 'testing' según phpunit.xml
        (new LocalDemoSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'familia@ampa.test']);
    }

    public function test_creates_all_expected_users(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        foreach ([
            'admin@ampa.test',
            'junta@ampa.test',
            'formularios@ampa.test',
            'extraescolares@ampa.test',
            'familia@ampa.test',
        ] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }

        $this->assertTrue(User::where('email', 'familia@ampa.test')->first()->hasRole('familia'));
        $this->assertTrue(User::where('email', 'junta@ampa.test')->first()->hasRole('junta_ampa'));
        $this->assertTrue(User::where('email', 'admin@ampa.test')->first()->hasRole('super_admin'));
    }

    public function test_creates_families_and_students(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        $this->assertDatabaseHas('families', ['name' => 'García López', 'is_ampa_member' => true]);
        $this->assertDatabaseHas('families', ['name' => 'Martínez Ruiz', 'is_ampa_member' => false]);
        $this->assertDatabaseHas('students', ['first_name' => 'Pablo',          'last_name' => 'García López']);
        $this->assertDatabaseHas('students', ['first_name' => 'Laura',          'last_name' => 'García López']);
        $this->assertDatabaseHas('students', ['first_name' => 'Lucía Demo',     'last_name' => 'García']);
        $this->assertDatabaseHas('students', ['first_name' => 'Juan Villarreal', 'last_name' => 'Martínez']);
    }

    public function test_creates_consent_types_with_responses(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        $this->assertDatabaseHas('consent_types', [
            'name' => 'Autorización general de comunicaciones AMPA',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('consent_types', [
            'name' => 'Autorización de uso de imagen',
            'requires_image_review' => true,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('consent_responses', ['status' => 'accepted']);
        $this->assertDatabaseHas('consent_responses', ['status' => 'pending']);
        $this->assertDatabaseHas('consent_responses', ['status' => 'revoked']);
    }

    public function test_creates_enrollments_with_varied_states(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        foreach (['pending', 'enrolled', 'waitlist', 'pending_payment', 'paid'] as $status) {
            $this->assertDatabaseHas('enrollments', ['status' => $status]);
        }
    }

    public function test_creates_demo_announcements_public_and_family(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        // Public pinned + public + family-only + draft + expired demo announcements.
        $this->assertDatabaseHas('announcements', ['audience' => 'public', 'is_pinned' => true, 'status' => 'published']);
        $this->assertDatabaseHas('announcements', ['audience' => 'families', 'status' => 'published']);
        $this->assertDatabaseHas('announcements', ['status' => 'draft']);

        // The public board shows only the visible ones (not draft/expired/family-only).
        $this->assertGreaterThanOrEqual(1, Announcement::publiclyVisible()->count());
    }

    public function test_creates_per_family_per_student_and_closed_forms(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        $this->assertDatabaseHas('forms', ['title' => 'Excursión fin de curso', 'response_scope' => 'per_family']);
        $this->assertDatabaseHas('forms', ['title' => 'Autorización salida a la piscina', 'response_scope' => 'per_student']);
        $this->assertDatabaseHas('forms', ['title' => 'Reunión de inicio de curso', 'status' => 'closed']);

        // El formulario por alumno/a tiene una respuesta de un alumno (Pablo) y deja el otro pendiente.
        $pablo = Student::where('first_name', 'Pablo')->firstOrFail();
        $this->assertDatabaseHas('form_responses', [
            'student_id' => $pablo->id,
            'response_key' => 'student:'.$pablo->id,
        ]);
    }

    public function test_creates_consents_with_all_states_including_rejected(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        foreach (['pending', 'accepted', 'rejected', 'revoked'] as $status) {
            $this->assertDatabaseHas('consent_responses', ['status' => $status]);
        }
    }

    public function test_configures_branding(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        $this->assertSame('AMPA Cortés de Aragón', AppSettings::get('ampa_name'));
        $this->assertSame('CEIP Cortés de Aragón', AppSettings::get('school_name'));
    }

    public function test_is_idempotent(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();
        (new LocalDemoSeeder)->run();

        $this->assertSame(5, User::count());
        $this->assertSame(2, Family::count());
        $this->assertSame(4, Student::count());
        $this->assertSame(2, ConsentType::count());
        // Inglés (3) + Multideporte (2) = 5 inscripciones; 3 formularios
        $this->assertSame(5, Enrollment::count());
        $this->assertSame(3, Form::count());
        $this->assertSame(5, Announcement::count());
    }

    public function test_links_familia_user_to_family(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();

        $user = User::where('email', 'familia@ampa.test')->first();

        $this->assertNotNull($user->family);
        $this->assertSame('García López', $user->family->name);
    }
}
