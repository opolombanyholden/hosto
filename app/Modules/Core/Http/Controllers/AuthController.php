<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * AuthController.
 *
 * Handles login/register/logout for the three environments.
 * Each environment has its own login page but shares the auth logic.
 *
 * @see docs/adr/0011-trois-environnements-authentification.md
 */
final class AuthController
{
    // ---------------------------------------------------------------
    // Usager (Patient)
    // ---------------------------------------------------------------

    public function compteConnexionForm(): View
    {
        return view('compte.connexion');
    }

    public function compteConnexion(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return $this->attemptLogin($credentials, 'usager', '/compte', '/compte/connexion', $request, $audit);
    }

    public function compteInscriptionForm(): View
    {
        return view('compte.inscription');
    }

    public function compteInscription(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $patientRole = Role::where('slug', 'patient')->first();
        if ($patientRole) {
            $user->roles()->attach($patientRole->id);
        }

        $audit->record(AuditLogger::ACTION_CREATE, 'user', $user->uuid, ['role' => 'patient']);

        Auth::login($user);

        return redirect('/compte')->with('success', 'Compte cree avec succes.');
    }

    // ---------------------------------------------------------------
    // Professionnel
    // ---------------------------------------------------------------

    public function proConnexionForm(): View
    {
        return view('pro.connexion');
    }

    public function proConnexion(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return $this->attemptLogin($credentials, 'pro', '/pro', '/pro/connexion', $request, $audit);
    }

    public function proInscriptionForm(): View
    {
        $roles = Role::where('environment', 'pro')->orderBy('display_order')->get();

        return view('pro.inscription', compact('roles'));
    }

    public function proInscription(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:30',
            'role' => 'required|exists:roles,slug',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        $role = Role::where('slug', $data['role'])->firstOrFail();
        $user->roles()->attach($role->id);

        $audit->record(AuditLogger::ACTION_CREATE, 'user', $user->uuid, [
            'role' => $data['role'],
            'status' => 'pending_validation',
        ]);

        Auth::login($user);

        return redirect('/pro')->with('success', 'Compte professionnel cree. En attente de validation.');
    }

    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------

    public function adminConnexionForm(): View
    {
        return view('admin.connexion');
    }

    public function adminConnexion(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return $this->attemptLogin($credentials, 'admin', '/admin', '/admin/connexion', $request, $audit);
    }

    // ---------------------------------------------------------------
    // Logout (shared)
    // ---------------------------------------------------------------

    public function logout(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $audit->record(AuditLogger::ACTION_LOGOUT, 'user', $user->uuid);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // ---------------------------------------------------------------
    // Shared login logic
    // ---------------------------------------------------------------

    /**
     * @param  array<string, string>  $credentials
     */
    private function attemptLogin(
        array $credentials,
        string $environment,
        string $successUrl,
        string $failUrl,
        Request $request,
        AuditLogger $audit,
    ): RedirectResponse {
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $audit->record(AuditLogger::ACTION_LOGIN_FAILED, 'user', null, [
                'email' => $credentials['email'],
                'environment' => $environment,
            ]);

            return redirect($failUrl)
                ->withInput(['email' => $credentials['email']])
                ->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $user = Auth::user();
        $user->load('roles');

        if (! $user->canAccessEnvironment($environment)) {
            Auth::logout();

            return redirect($failUrl)
                ->withInput(['email' => $credentials['email']])
                ->withErrors(['email' => 'Vous n\'avez pas acces a cet espace.']);
        }

        // 2FA challenge: if enabled, logout and redirect to challenge page.
        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null) {
            Auth::logout();

            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_environment', $environment);
            $request->session()->put('2fa_remember', $request->boolean('remember'));

            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();

        $audit->record(AuditLogger::ACTION_LOGIN, 'user', $user->uuid, [
            'environment' => $environment,
        ]);

        return redirect()->intended($successUrl);
    }
}
