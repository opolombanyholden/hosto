<?php

declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\Core\Models\InvitationLink;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class ThirdPartyResolverService
{
    public function normalizePhone(string $raw, string $countryIso = 'GA'): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($raw, strtoupper($countryIso));
            if (! $util->isValidNumber($parsed)) {
                return null;
            }

            return $util->format($parsed, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return null;
        }
    }

    public function findUserByPhone(string $normalizedPhone): ?User
    {
        return User::where('phone_normalized', $normalizedPhone)->first();
    }

    public function sendInvitation(User $inviter, string $phoneNormalized, Appointment $apt): InvitationLink
    {
        $token = Str::random(64);

        $link = InvitationLink::create([
            'inviter_user_id' => $inviter->id,
            'context' => 'appointment_third_party',
            'context_id' => $apt->id,
            'phone_normalized' => $phoneNormalized,
            'token' => $token,
            'sent_via' => 'sms',
            'sent_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        // v1 stub : log only. Real SMS integration arrives later.
        Log::info('invitation.sms.stub', [
            'phone' => $phoneNormalized,
            'token' => $token,
            'inviter_user_id' => $inviter->id,
            'context_id' => $apt->id,
        ]);

        return $link;
    }
}
