<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Models\User;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Password reset flow (shared across all environments).
 *
 * 1. User requests a reset link → token stored in password_reset_tokens
 * 2. Email sent with link containing token
 * 3. User clicks link → form to enter new password
 * 4. Password updated, token deleted
 */
final class PasswordResetController
{
    private const TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Show the "forgot password" form.
     */
    public function showForgotForm(Request $request): View
    {
        $env = $this->detectEnv($request);

        return view('auth.forgot-password', compact('env'));
    }

    /**
     * Send the password reset link.
     */
    public function sendResetLink(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');
        $env = $this->detectEnv($request);
        $user = User::where('email', $email)->first();

        // Always show success (don't reveal if email exists).
        $successMsg = 'Si cette adresse est associee a un compte, vous recevrez un lien de reinitialisation.';

        if ($user === null) {
            return back()->with('success', $successMsg);
        }

        // Delete any existing token.
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Generate token.
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Build reset URL.
        $resetUrl = url("/mot-de-passe/reinitialiser?token={$token}&email=".urlencode($email));

        // Send email.
        Mail::raw(
            "Bonjour {$user->name},\n\n"
            ."Vous avez demande la reinitialisation de votre mot de passe HOSTO.\n\n"
            ."Cliquez sur le lien ci-dessous pour definir un nouveau mot de passe :\n"
            ."{$resetUrl}\n\n"
            .'Ce lien expire dans '.self::TOKEN_EXPIRY_MINUTES." minutes.\n\n"
            ."Si vous n'etes pas a l'origine de cette demande, ignorez cet email.\n\n"
            ."— L'equipe HOSTO",
            fn ($message) => $message
                ->to($email)
                ->subject('HOSTO — Reinitialisation de votre mot de passe'),
        );

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'password_reset_requested',
        ]);

        return back()->with('success', $successMsg);
    }

    /**
     * Show the reset password form (after clicking email link).
     */
    public function showResetForm(Request $request): View|RedirectResponse
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (! $token || ! $email) {
            return redirect('/compte/connexion')
                ->withErrors(['email' => 'Lien de reinitialisation invalide.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Reset the password.
     */
    public function resetPassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $email = $request->input('email');
        $token = $request->input('token');

        // Find the token record.
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if ($record === null) {
            return back()->withErrors(['email' => 'Ce lien de reinitialisation est invalide ou a expire.']);
        }

        // Check token validity.
        if (! Hash::check($token, $record->token)) {
            return back()->withErrors(['email' => 'Ce lien de reinitialisation est invalide ou a expire.']);
        }

        // Check expiry.
        if (now()->diffInMinutes($record->created_at) > self::TOKEN_EXPIRY_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()->withErrors(['email' => 'Ce lien a expire. Veuillez refaire une demande.']);
        }

        // Update password.
        $user = User::where('email', $email)->first();
        if ($user === null) {
            return back()->withErrors(['email' => 'Utilisateur introuvable.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        // Delete the token.
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'password_reset_completed',
        ]);

        // Detect which login page to redirect to.
        $loginUrl = '/compte/connexion';
        $user->load('roles');
        if ($user->canAccessEnvironment('admin')) {
            $loginUrl = '/admin/connexion';
        } elseif ($user->canAccessEnvironment('pro')) {
            $loginUrl = '/pro/connexion';
        }

        return redirect($loginUrl)->with('success', 'Mot de passe reinitialise avec succes. Vous pouvez vous connecter.');
    }

    private function detectEnv(Request $request): string
    {
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/admin')) {
            return 'admin';
        }
        if (str_contains($referer, '/pro')) {
            return 'pro';
        }

        return 'compte';
    }
}
