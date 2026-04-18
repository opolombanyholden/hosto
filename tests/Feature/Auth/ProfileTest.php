<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'patient'], ['name_fr' => 'Patient', 'name_en' => 'Patient', 'environment' => 'usager']);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('slug', 'patient')->first());
    }

    public function test_profile_page_accessible_when_authenticated(): void
    {
        $this->actingAs($this->user)
            ->get('/compte/profil')
            ->assertOk()
            ->assertSee('Informations personnelles')
            ->assertSee('Modifier le mot de passe')
            ->assertSee('Verification en 2 etapes');
    }

    public function test_profile_page_redirects_when_unauthenticated(): void
    {
        $this->get('/compte/profil')
            ->assertRedirect('/compte/connexion');
    }

    public function test_can_update_name_and_email(): void
    {
        $newEmail = 'nouveau-'.uniqid().'@test.com';

        $this->actingAs($this->user)
            ->put('/compte/profil/info', [
                'name' => 'Nouveau Nom',
                'email' => $newEmail,
            ])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertSame('Nouveau Nom', $this->user->name);
        $this->assertSame($newEmail, $this->user->email);
    }

    public function test_can_update_password(): void
    {
        $this->user->update(['password' => bcrypt('ancienpassword1')]);

        $this->actingAs($this->user)
            ->put('/compte/profil/password', [
                'current_password' => 'ancienpassword1',
                'password' => 'nouveaupassword1',
                'password_confirmation' => 'nouveaupassword1',
            ])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertTrue(Hash::check('nouveaupassword1', $this->user->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->put('/compte/profil/password', [
                'current_password' => 'mauvais',
                'password' => 'nouveaupassword1',
                'password_confirmation' => 'nouveaupassword1',
            ])
            ->assertSessionHasErrors('current_password');
    }
}
