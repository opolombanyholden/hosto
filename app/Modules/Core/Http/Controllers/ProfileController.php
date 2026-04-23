<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Models\EmergencyContact;
use App\Modules\Core\Services\AuditLogger;
use App\Modules\Core\Services\TwoFactorService;
use App\Modules\Referentiel\Models\ReferenceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * User profile management (shared across all environments).
 */
final class ProfileController
{
    public function show(Request $request, TwoFactorService $twoFactor): View
    {
        $user = $request->user();
        $environment = $this->detectEnvironment($request);

        return view('auth.profile', [
            'user' => $user,
            'environment' => $environment,
            'twoFactorEnabled' => $twoFactor->isEnabled($user),
        ]);
    }

    /**
     * Complete profile page (patient only).
     */
    public function completeProfile(Request $request): View
    {
        $user = $request->user();
        $user->load('emergencyContacts');

        return view('compte.complete-profile', [
            'user' => $user,
            'completionPercent' => $user->profileCompletionPercent(),
            'idDocumentTypes' => ReferenceData::forCategory('id_document_type'),
            'genders' => ReferenceData::forCategory('gender'),
            'bloodGroups' => ReferenceData::forCategory('blood_group'),
            'securityQuestions' => ReferenceData::forCategory('security_question'),
            'contactRelations' => ReferenceData::forCategory('contact_relation'),
            'countryCodes' => ReferenceData::forCategory('country_code'),
        ]);
    }

    public function updateInfo(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($data);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => array_keys($data),
        ]);

        return back()->with('success', 'Informations mises a jour.');
    }

    /**
     * Save identity section (NIP, ID document, DOB, gender, blood group).
     */
    public function updateIdentity(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nip' => 'nullable|string|max:30|unique:users,nip,'.$user->id,
            'id_document_type' => 'nullable|in:cni,passeport,carte_sejour,permis_conduire',
            'id_document_number' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
        ]);

        $user->update($data);
        $this->checkProfileCompletion($user);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => array_keys($data),
            'section' => 'identity',
        ]);

        return response()->json(['data' => ['message' => 'Identite mise a jour.']]);
    }

    /**
     * Save residence section (country, city, address).
     */
    public function updateResidence(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'country_of_residence' => 'nullable|string|max:3',
            'city_of_residence' => 'nullable|string|max:255',
            'address_of_residence' => 'nullable|string|max:255',
        ]);

        $user->update($data);
        $this->checkProfileCompletion($user);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => array_keys($data),
            'section' => 'residence',
        ]);

        return response()->json(['data' => ['message' => 'Residence mise a jour.']]);
    }

    /**
     * Save security question + answer.
     */
    public function updateSecurityQuestion(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string|max:255',
        ]);

        $user->update([
            'security_question' => $request->input('security_question'),
            'security_answer' => Hash::make($request->input('security_answer')),
        ]);
        $this->checkProfileCompletion($user);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'section' => 'security_question',
        ]);

        return response()->json(['data' => ['message' => 'Question secrete enregistree.']]);
    }

    /**
     * Set or update the medical record PIN (4–6 digits).
     */
    public function updateMedicalPin(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'pin' => 'required|digits_between:4,6',
            'pin_confirmation' => 'required|same:pin',
        ]);

        // If PIN already exists, require current PIN first.
        if ($user->hasMedicalPin()) {
            $request->validate(['current_pin' => 'required|digits_between:4,6']);

            if (! Hash::check($request->input('current_pin'), $user->medical_pin)) {
                return response()->json([
                    'error' => ['code' => 'INVALID_PIN', 'message' => 'PIN actuel incorrect.'],
                ], 422);
            }
        }

        $user->update([
            'medical_pin' => Hash::make($request->input('pin')),
            'medical_pin_set_at' => now(),
        ]);
        $this->checkProfileCompletion($user);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'section' => 'medical_pin',
        ]);

        return response()->json(['data' => ['message' => 'PIN medical enregistre.']]);
    }

    /**
     * Verify medical PIN (used before accessing DPE).
     */
    public function verifyMedicalPin(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasMedicalPin()) {
            return response()->json([
                'error' => ['code' => 'NO_PIN', 'message' => 'Aucun PIN defini. Configurez-le dans votre profil.'],
            ], 422);
        }

        $request->validate(['pin' => 'required|digits_between:4,6']);

        if (! Hash::check($request->input('pin'), $user->medical_pin)) {
            return response()->json([
                'error' => ['code' => 'INVALID_PIN', 'message' => 'PIN incorrect.'],
            ], 422);
        }

        // Store PIN verification in session (valid 15 minutes).
        session(['medical_pin_verified_at' => now()->timestamp]);

        return response()->json(['data' => ['message' => 'PIN verifie.']]);
    }

    /**
     * Save / update emergency contacts.
     */
    public function updateEmergencyContacts(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'contacts' => 'required|array|min:1|max:5',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:30',
            'contacts.*.relation' => 'nullable|in:enfant,parent,conjoint,frere_soeur,ami,autre',
            'contacts.*.can_access_medical_record' => 'nullable|boolean',
        ]);

        // Replace all contacts.
        $user->emergencyContacts()->delete();

        foreach ($data['contacts'] as $i => $contact) {
            EmergencyContact::create([
                'user_id' => $user->id,
                'name' => $contact['name'],
                'phone' => $contact['phone'],
                'relation' => $contact['relation'] ?? null,
                'can_access_medical_record' => (bool) ($contact['can_access_medical_record'] ?? false),
                'priority' => $i + 1,
            ]);
        }

        $this->checkProfileCompletion($user);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'section' => 'emergency_contacts',
            'count' => count($data['contacts']),
        ]);

        return response()->json(['data' => ['message' => 'Contacts d\'urgence enregistres.']]);
    }

    /**
     * Upload profile photo.
     */
    public function updatePhoto(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('photo')->store('profile-photos/'.$user->uuid, 'public');

        $user->update(['profile_photo_path' => $path]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'section' => 'profile_photo',
        ]);

        return response()->json(['data' => ['message' => 'Photo mise a jour.', 'path' => $path]]);
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        $audit->record(AuditLogger::ACTION_UPDATE, 'user', $user->uuid, [
            'fields' => ['password'],
        ]);

        return back()->with('success', 'Mot de passe modifie avec succes.');
    }

    /**
     * Auto-mark profile as complete when all sections are filled.
     */
    private function checkProfileCompletion(mixed $user): void
    {
        $user->refresh();

        if ($user->profileCompletionPercent() === 100 && $user->profile_completed_at === null) {
            $user->update(['profile_completed_at' => now()]);
        }
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
