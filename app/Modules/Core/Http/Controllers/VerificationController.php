<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Email and phone verification via OTP (6-digit code).
 *
 * In development mode (APP_DEBUG=true), the OTP codes are:
 *   - Logged to laravel.log
 *   - Flashed to session for display in the UI
 *
 * In production, codes are sent via email (SMTP) and SMS (gateway).
 */
final class VerificationController
{
    private const OTP_LENGTH = 6;

    private const OTP_TTL_MINUTES = 10;

    public function show(): View
    {
        return view('auth.verification-notice');
    }

    /**
     * Send email verification OTP.
     */
    public function sendEmailOtp(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return back()->with('success', 'Votre email est deja verifie.');
        }

        $otp = $this->generateOtp();
        $cacheKey = "email_otp_{$user->id}";
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));
        session([$cacheKey => $otp]);

        // Log OTP for development.
        Log::info("[HOSTO DEV] Code de verification EMAIL pour {$user->email} : {$otp}");

        // Send email (goes to log in dev mode).
        Mail::raw(
            "Bonjour {$user->name},\n\n"
            ."Votre code de verification HOSTO est : {$otp}\n\n"
            .'Ce code expire dans '.self::OTP_TTL_MINUTES." minutes.\n\n"
            ."— L'equipe HOSTO",
            fn ($message) => $message
                ->to($user->email)
                ->subject("HOSTO — Code de verification : {$otp}"),
        );

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'email_otp_sent',
        ]);

        $flash = ['success' => "Code envoye a {$user->email}. Valable ".self::OTP_TTL_MINUTES.' minutes.'];

        // In dev mode, show OTP directly in the UI.
        if (config('app.debug')) {
            $flash['dev_email_otp'] = $otp;
        }

        return back()->with($flash);
    }

    /**
     * Verify email OTP.
     */
    public function verifyEmailOtp(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['email_otp' => 'required|digits:'.self::OTP_LENGTH]);

        $user = $request->user();
        $cacheKey = "email_otp_{$user->id}";
        $storedOtp = Cache::get($cacheKey) ?? session($cacheKey);

        if ($storedOtp === null || (string) $storedOtp !== (string) $request->input('email_otp')) {
            return back()->withErrors(['email_otp' => 'Code incorrect ou expire. Renvoyez un nouveau code.']);
        }

        $user->update(['email_verified_at' => now()]);
        $request->user()->refresh();
        Cache::forget($cacheKey);
        session()->forget($cacheKey);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'email_verified',
        ]);

        return redirect()->route('verification.notice')->with('success', 'Adresse email verifiee avec succes !');
    }

    /**
     * Send phone verification OTP.
     */
    public function sendPhoneOtp(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at !== null) {
            return back()->with('success', 'Votre telephone est deja verifie.');
        }

        if (! $user->phone) {
            return back()->withErrors(['phone' => 'Aucun numero de telephone renseigne.']);
        }

        $otp = $this->generateOtp();
        $cacheKey = "phone_otp_{$user->id}";
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));
        session([$cacheKey => $otp]);

        // Log OTP for development.
        Log::info("[HOSTO DEV] Code de verification TELEPHONE pour {$user->phone} : {$otp}");

        // TODO: In production, send SMS via gateway (Twilio, AfricasTalking, etc.)
        // SmsGateway::send($user->phone, "Votre code HOSTO : {$otp}");

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'phone_otp_sent',
        ]);

        $flash = ['success' => "Code envoye au {$user->phone}. Valable ".self::OTP_TTL_MINUTES.' minutes.'];

        // In dev mode, show OTP directly in the UI.
        if (config('app.debug')) {
            $flash['dev_phone_otp'] = $otp;
        }

        return back()->with($flash);
    }

    /**
     * Verify phone OTP.
     */
    public function verifyPhoneOtp(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['phone_otp' => 'required|digits:'.self::OTP_LENGTH]);

        $user = $request->user();
        $cacheKey = "phone_otp_{$user->id}";
        $storedOtp = Cache::get($cacheKey) ?? session($cacheKey);

        if ($storedOtp === null || (string) $storedOtp !== (string) $request->input('phone_otp')) {
            return back()->withErrors(['phone_otp' => 'Code incorrect ou expire. Renvoyez un nouveau code.']);
        }

        $user->update(['phone_verified_at' => now()]);
        $request->user()->refresh();
        Cache::forget($cacheKey);
        session()->forget($cacheKey);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'action' => 'phone_verified',
        ]);

        return redirect()->route('verification.notice')->with('success', 'Numero de telephone verifie avec succes !');
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }
}
