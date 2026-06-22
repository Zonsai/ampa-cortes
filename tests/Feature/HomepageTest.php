<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_200(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_homepage_does_not_contain_laravel_welcome_text(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Documentation');
        $response->assertDontSee('Laracasts');
        $response->assertDontSee('Deploy now');
        $response->assertDontSee("Let's get started");
    }

    public function test_homepage_shows_ampa_name(): void
    {
        $this->get('/')->assertSee('AMPA Cortés de Aragón');
    }

    public function test_homepage_shows_link_to_familia_login(): void
    {
        $this->get('/')->assertSee('/familia/login', false);
    }

    public function test_homepage_shows_link_to_admin(): void
    {
        $this->get('/')->assertSee('/admin', false);
    }

    public function test_homepage_loads_for_authenticated_familia_user(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('familia');

        // The public home is a marketing site; it loads for everyone, including
        // authenticated users, and keeps its access entry points.
        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('AMPA Cortés de Aragón')
            ->assertSee('/familia/login', false);
    }

    public function test_homepage_loads_for_authenticated_admin_user(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('junta_ampa');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('/admin', false);
    }
}
