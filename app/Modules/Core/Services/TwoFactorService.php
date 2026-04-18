<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TwoFactorService.
 *
 * Manages TOTP-based two-factor authentication.
 * Uses Google Authenticator-compatible TOTP (RFC 6238).
 *
 * Recovery codes are generated as fallback for lost devices.
 *
 * @see docs/adr/0005-authentification-sanctum.md
 */
final class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new 2FA secret for the user.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Generate the provisioning URI for QR code display.
     */
    public function qrCodeUri(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            'HOSTO',
            $user->email,
            $secret,
        );
    }

    /**
     * Verify a TOTP code against a secret.
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Generate recovery codes.
     *
     * @return Collection<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): Collection
    {
        /** @var Collection<int, string> */
        return Collection::times($count, fn () => Str::upper(Str::random(4).'-'.Str::random(4)));
    }

    /**
     * Enable 2FA for a user.
     *
     * @return Collection<int, string> Recovery codes
     */
    public function enable(User $user, string $secret): Collection
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt($recoveryCodes->toJson()),
            'two_factor_confirmed_at' => now(),
        ]);

        return $recoveryCodes;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Verify a code (TOTP or recovery code).
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);

        // Try TOTP first.
        if ($this->verify($secret, $code)) {
            return true;
        }

        // Try recovery code.
        return $this->useRecoveryCode($user, $code);
    }

    /**
     * Attempt to use a recovery code (single use).
     */
    private function useRecoveryCode(User $user, string $code): bool
    {
        if ($user->two_factor_recovery_codes === null) {
            return false;
        }

        /** @var list<string> $codes */
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $code = Str::upper($code);

        $index = array_search($code, $codes, true);
        if ($index === false) {
            return false;
        }

        // Remove used code.
        unset($codes[$index]);
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ]);

        return true;
    }
}
