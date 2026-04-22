<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Social login (OAuth) via Google, Facebook, Yahoo.
 *
 * Two scenarios:
 *   1. New user → create account + assign patient role + login
 *   2. Existing user (same email) → link provider + login
 */
final class SocialAuthController
{
    /** @var list<string> */
    private const PROVIDERS = ['google', 'facebook', 'yahoo'];

    /**
     * Redirect to the OAuth provider.
     */
    public function redirect(string $provider): RedirectResponse|SymfonyRedirect
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from the OAuth provider.
     */
    public function callback(string $provider, AuditLogger $audit): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect('/compte/connexion')
                ->withErrors(['email' => 'Echec de connexion via '.ucfirst($provider).'. Veuillez reessayer.']);
        }

        $email = $socialUser->getEmail();

        if ($email === null) {
            return redirect('/compte/connexion')
                ->withErrors(['email' => 'Impossible de recuperer votre adresse email depuis '.ucfirst($provider).'.']);
        }

        // Check if user already exists with this email.
        $user = User::where('email', $email)->first();

        if ($user !== null) {
            // Link OAuth provider if not already linked.
            if ($user->oauth_provider === null) {
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $socialUser->getId(),
                    'avatar_url' => $socialUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            $audit->record(AuditLogger::ACTION_LOGIN, 'user', $user->uuid, [
                'method' => 'oauth',
                'provider' => $provider,
            ]);
        } else {
            // Create new user.
            $user = User::create([
                'name' => $socialUser->getName() ?? $email,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'oauth_provider' => $provider,
                'oauth_provider_id' => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            // Assign patient role.
            $patientRole = Role::where('slug', 'patient')->first();
            if ($patientRole) {
                $user->roles()->attach($patientRole->id);
            }

            $audit->record(AuditLogger::ACTION_CREATE, 'user', $user->uuid, [
                'method' => 'oauth',
                'provider' => $provider,
                'role' => 'patient',
            ]);
        }

        Auth::login($user, true);
        session()->regenerate();

        return redirect('/compte');
    }
}
