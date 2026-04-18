<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist.
        Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);
        Role::firstOrCreate(['slug' => 'doctor'], ['name_fr' => 'Medecin', 'name_en' => 'Doctor', 'environment' => 'pro']);
        Role::firstOrCreate(['slug' => 'super_admin'], ['name_fr' => 'Super admin', 'name_en' => 'Super admin', 'environment' => 'admin']);
    }

    // ---------------------------------------------------------------
    // Login pages accessible
    // ---------------------------------------------------------------

    public function test_compte_login_page_is_accessible(): void
    {
        $this->get('/compte/connexion')->assertOk();
    }

    public function test_pro_login_page_is_accessible(): void
    {
        $this->get('/pro/connexion')->assertOk();
    }

    public function test_admin_login_page_is_accessible(): void
    {
        $this->get('/admin/connexion')->assertOk();
    }

    public function test_compte_inscription_page_is_accessible(): void
    {
        $this->get('/compte/inscription')->assertOk();
    }

    public function test_pro_inscription_page_is_accessible(): void
    {
        $this->get('/pro/inscription')->assertOk();
    }

    // ---------------------------------------------------------------
    // Protected dashboards redirect to login
    // ---------------------------------------------------------------

    public function test_compte_dashboard_redirects_unauthenticated(): void
    {
        $this->get('/compte')->assertRedirect('/compte/connexion');
    }

    public function test_pro_dashboard_redirects_unauthenticated(): void
    {
        $this->get('/pro')->assertRedirect('/pro/connexion');
    }

    public function test_admin_dashboard_redirects_unauthenticated(): void
    {
        $this->get('/admin')->assertRedirect('/admin/connexion');
    }

    // ---------------------------------------------------------------
    // Registration
    // ---------------------------------------------------------------

    public function test_patient_can_register(): void
    {
        $response = $this->post('/compte/inscription', [
            'name' => 'Jean Ndong',
            'email' => 'jean-'.uniqid().'@test.com',
            'password' => 'motdepasse12345',
            'password_confirmation' => 'motdepasse12345',
        ]);

        $response->assertRedirect('/compte');
        $this->assertAuthenticated();
    }

    // ---------------------------------------------------------------
    // Login + environment enforcement
    // ---------------------------------------------------------------

    public function test_patient_can_login_to_compte(): void
    {
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $response = $this->post('/compte/connexion', [
            'email' => $user->email,
            'password' => 'motdepasse12345',
        ]);

        $response->assertRedirect('/compte');
        $this->assertAuthenticatedAs($user);
    }

    public function test_patient_cannot_login_to_pro(): void
    {
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $response = $this->post('/pro/connexion', [
            'email' => $user->email,
            'password' => 'motdepasse12345',
        ]);

        $response->assertRedirect('/pro/connexion');
        $this->assertGuest();
    }

    public function test_patient_cannot_login_to_admin(): void
    {
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $response = $this->post('/admin/connexion', [
            'email' => $user->email,
            'password' => 'motdepasse12345',
        ]);

        $response->assertRedirect('/admin/connexion');
        $this->assertGuest();
    }

    public function test_admin_can_login_to_admin(): void
    {
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);
        $user->roles()->attach(Role::where('slug', 'super_admin')->first());

        $response = $this->post('/admin/connexion', [
            'email' => $user->email,
            'password' => 'motdepasse12345',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_fails(): void
    {
        $user = User::factory()->create(['password' => bcrypt('motdepasse12345')]);

        $this->post('/compte/connexion', [
            'email' => $user->email,
            'password' => 'mauvais',
        ])->assertRedirect('/compte/connexion');

        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // Logout
    // ---------------------------------------------------------------

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'patient')->first());

        $this->actingAs($user)->post('/deconnexion')->assertRedirect('/');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // Visual identity
    // ---------------------------------------------------------------

    public function test_compte_login_uses_green_color(): void
    {
        $this->get('/compte/connexion')
            ->assertOk()
            ->assertSee('#388E3C');
    }

    public function test_pro_login_uses_blue_color(): void
    {
        $this->get('/pro/connexion')
            ->assertOk()
            ->assertSee('#1565C0');
    }

    public function test_admin_login_uses_red_color(): void
    {
        $this->get('/admin/connexion')
            ->assertOk()
            ->assertSee('#B71C1C');
    }
}
