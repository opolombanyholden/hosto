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
        $credentials = $this->resolveCredentials($request);

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
        $credentials = $this->resolveCredentials($request);

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
        $credentials = $this->resolveCredentials($request);

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
     * Resolve login credentials from the request.
     *
     * Supports email or phone (with country prefix) login.
     *
     * @return array<string, string>
     */
    private function resolveCredentials(Request $request): array
    {
        $loginMode = $request->input('login_mode', 'email');

        if ($loginMode === 'phone') {
            $request->validate([
                'country_code' => 'required|string|max:5',
                'phone_number' => 'required|string|max:30',
                'password' => 'required',
            ]);

            $fullPhone = $request->input('country_code').$request->input('phone_number');

            return ['phone' => $fullPhone, 'password' => $request->input('password')];
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return ['email' => $request->input('email'), 'password' => $request->input('password')];
    }

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
        $identifier = $credentials['email'] ?? $credentials['phone'] ?? '';

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $audit->record(AuditLogger::ACTION_LOGIN_FAILED, 'user', null, [
                'identifier' => $identifier,
                'environment' => $environment,
            ]);

            return redirect($failUrl)
                ->withInput($request->only('email', 'login_mode', 'country_code', 'phone_number'))
                ->withErrors(['login' => 'Identifiants incorrects.']);
        }

        $user = Auth::user();
        $user->load('roles');

        if (! $user->canAccessEnvironment($environment)) {
            Auth::logout();

            return redirect($failUrl)
                ->withInput($request->only('email', 'login_mode', 'country_code', 'phone_number'))
                ->withErrors(['login' => 'Vous n\'avez pas acces a cet espace.']);
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
