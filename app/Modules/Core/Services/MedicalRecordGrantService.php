<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Models\MedicalRecordAccessLog;
use App\Modules\Core\Models\MedicalRecordGrant;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Http\Request;

final class MedicalRecordGrantService
{
    public function grant(
        User $patient,
        Practitioner $practitioner,
        ?array $scope = null,
        ?Appointment $source = null,
    ): MedicalRecordGrant {
        $existing = MedicalRecordGrant::where('patient_id', $patient->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereNull('revoked_at')
            ->first();
        if ($existing) {
            return $existing;
        }
        return MedicalRecordGrant::create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'source_appointment_id' => $source?->id,
            'scope' => $scope,
            'granted_at' => now(),
        ]);
    }

    public function revoke(MedicalRecordGrant $grant, User $by): void
    {
        if ($grant->isActive()) {
            $grant->update(['revoked_at' => now()]);
        }
    }

    public function hasActiveGrant(User $patient, Practitioner $practitioner): bool
    {
        return MedicalRecordGrant::where('patient_id', $patient->id)
            ->where('practitioner_id', $practitioner->id)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function logAccess(
        MedicalRecordGrant $grant,
        User $practitionerUser,
        array $sections,
        Request $request,
    ): MedicalRecordAccessLog {
        $log = MedicalRecordAccessLog::create([
            'grant_id' => $grant->id,
            'practitioner_user_id' => $practitionerUser->id,
            'accessed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'sections_accessed' => $sections,
        ]);
        $grant->increment('access_count');
        $grant->update(['last_accessed_at' => now()]);
        return $log;
    }
}
