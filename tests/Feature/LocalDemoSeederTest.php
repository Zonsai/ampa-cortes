<?php

namespace Tests\Feature;

use App\Models\ConsentType;
use App\Models\Family;
use App\Models\Student;
use App\Models\User;
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

    public function test_is_idempotent(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        (new LocalDemoSeeder)->run();
        (new LocalDemoSeeder)->run();

        $this->assertSame(5, User::count());
        $this->assertSame(2, Family::count());
        $this->assertSame(4, Student::count());
        $this->assertSame(2, ConsentType::count());
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
