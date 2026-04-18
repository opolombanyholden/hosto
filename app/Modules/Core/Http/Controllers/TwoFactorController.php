<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles 2FA setup, verification and disabling.
 *
 * The 2FA challenge is injected into the login flow via the
 * AuthController — when a user with 2FA enabled logs in, they
 * are redirected to the challenge page before accessing the dashboard.
 */
final class TwoFactorController
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Show 2FA setup page (generate secret + QR code).
     */
    public function setup(Request $request): View
    {
        $user = $request->user();
        $secret = $this->twoFactor->generateSecret();

        $request->session()->put('2fa_secret', $secret);

        $qrUri = $this->twoFactor->qrCodeUri($user, $secret);

        return view('auth.2fa-setup', [
            'secret' => $secret,
            'qrUri' => $qrUri,
            'environment' => $this->detectEnvironment($request),
        ]);
    }

    /**
     * Confirm 2FA activation with a valid TOTP code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $secret = $request->session()->get('2fa_secret');
        if (! $secret || ! $this->twoFactor->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide. Reessayez.']);
        }

        $user = $request->user();
        $recoveryCodes = $this->twoFactor->enable($user, $secret);

        $request->session()->forget('2fa_secret');

        $this->audit->record(AuditLogger::ACTION_2FA_ENABLED, 'user', $user->uuid);

        return redirect()->route('2fa.recovery')
            ->with('recovery_codes', $recoveryCodes->all());
    }

    /**
     * Show recovery codes (only after activation).
     */
    public function recovery(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('recovery_codes');
        if (! $codes) {
            $env = $this->detectEnvironment($request);

            return redirect("/{$env}");
        }

        return view('auth.2fa-recovery', [
            'codes' => $codes,
            'environment' => $this->detectEnvironment($request),
        ]);
    }

    /**
     * Show 2FA challenge page (during login).
     */
    public function challenge(Request $request): View
    {
        return view('auth.2fa-challenge', [
            'environment' => $request->session()->get('2fa_environment', 'compte'),
        ]);
    }

    /**
     * Verify 2FA code during login.
     */
    public function verifyChallengeCode(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('2fa_user_id');
        if (! $userId) {
            return redirect('/compte/connexion');
        }

        $user = User::findOrFail($userId);

        if (! $this->twoFactor->verifyCode($user, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        Auth::login($user, $request->session()->get('2fa_remember', false));

        $environment = $request->session()->get('2fa_environment', 'compte');
        $redirect = match ($environment) {
            'admin' => '/admin',
            'pro' => '/pro',
            default => '/compte',
        };

        $this->audit->record(AuditLogger::ACTION_2FA_CHALLENGED, 'user', $user->uuid, [
            'environment' => $environment,
        ]);

        $request->session()->forget(['2fa_user_id', '2fa_environment', '2fa_remember']);
        $request->session()->regenerate();

        return redirect()->intended($redirect);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required']);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $this->twoFactor->disable($user);

        $this->audit->record('2fa.disabled', 'user', $user->uuid);

        return back()->with('success', 'Authentification a deux facteurs desactivee.');
    }

    private function detectEnvironment(Request $request): string
    {
        if ($request->is('admin*')) {
            return 'admin';
        }
        if ($request->is('pro*')) {
            return 'pro';
        }

        return 'compte';
    }
}
