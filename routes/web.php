<?php

declare(strict_types=1);

use App\Http\Controllers\AdminReferenceController;
use App\Http\Controllers\AdminWebController;
use App\Http\Controllers\AnnuaireWebController;
use App\Http\Controllers\BookingWebController;
use App\Http\Controllers\ClaimsWebController;
use App\Http\Controllers\PractitionerProfileController;
use App\Http\Controllers\ProWebController;
use App\Http\Controllers\PublicationInteractionController;
use App\Http\Controllers\TeleconWebController;
use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Core\Http\Controllers\AuthController;
use App\Modules\Core\Http\Controllers\PasswordResetController;
use App\Modules\Core\Http\Controllers\ProfileController;
use App\Modules\Core\Http\Controllers\SocialAuthController;
use App\Modules\Core\Http\Controllers\TwoFactorController;
use App\Modules\Core\Http\Controllers\VerificationController;
use App\Modules\Pro\Models\Consultation;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Public
// ---------------------------------------------------------------

Route::get('/', function () {
    return view('welcome');
});

Route::get('/annuaire', [AnnuaireWebController::class, 'index'])->name('annuaire.index');
Route::get('/annuaire/medecins', [AnnuaireWebController::class, 'practitioners'])->name('annuaire.practitioners');
Route::get('/annuaire/medecins/{slug}', [AnnuaireWebController::class, 'practitionerShow'])->name('annuaire.practitioner.show');
Route::get('/medicaments', [AnnuaireWebController::class, 'medications'])->name('medications.index');
Route::get('/examens', [AnnuaireWebController::class, 'exams'])->name('exams.index');
Route::get('/annuaire/{slug}/rendez-vous', [AnnuaireWebController::class, 'bookRdv'])->name('annuaire.book-rdv');
Route::get('/annuaire/{slug}', [AnnuaireWebController::class, 'show'])->name('annuaire.show');

// ---------------------------------------------------------------
// Password reset (shared, guest only)
// ---------------------------------------------------------------

Route::middleware('guest')->group(function (): void {
    Route::get('/mot-de-passe/oublie', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/mot-de-passe/oublie', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/mot-de-passe/reinitialiser', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/mot-de-passe/reinitialiser', [PasswordResetController::class, 'resetPassword'])->name('password.update');

    // Social login (OAuth)
    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

// ---------------------------------------------------------------
// Auth : Usager (patient)
// ---------------------------------------------------------------

Route::prefix('compte')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'compteConnexionForm'])->name('compte.connexion');
        Route::post('/connexion', [AuthController::class, 'compteConnexion']);
        Route::get('/inscription', [AuthController::class, 'compteInscriptionForm'])->name('compte.inscription');
        Route::post('/inscription', [AuthController::class, 'compteInscription']);
    });

    Route::middleware(['auth', 'env:usager'])->group(function (): void {
        Route::get('/', function () {
            return view('compte.dashboard');
        })->name('compte.dashboard');
        Route::get('/rendez-vous', function () {
            $appointments = Appointment::where('patient_id', auth()->id())
                ->with(['timeSlot', 'practitioner', 'structure'])
                ->orderByDesc('created_at')
                ->get();

            return view('compte.rendez-vous', compact('appointments'));
        })->name('compte.rdv');
        Route::get('/dossier-medical', function () {
            return view('compte.mon-dossier', ['user' => auth()->user()]);
        })->name('compte.dossier');

        // API JSON pour les sections du dossier (AJAX + pagination)
        Route::get('/api/rendez-vous', function (Request $request) {
            $query = Appointment::where('patient_id', auth()->id())->with(['timeSlot', 'practitioner', 'structure'])->orderByDesc('created_at');
            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(fn ($w) => $w->whereHas('structure', fn ($s) => $s->where('name', 'ILIKE', "%{$q}%"))
                    ->orWhereHas('practitioner', fn ($p) => $p->where('last_name', 'ILIKE', "%{$q}%")->orWhere('first_name', 'ILIKE', "%{$q}%"))
                    ->orWhere('reason', 'ILIKE', "%{$q}%"));
            }
            $data = $query->paginate($request->integer('per_page', 5));

            return response()->json(['data' => $data->map(fn ($r) => [
                'uuid' => $r->uuid, 'status' => $r->status, 'reason' => $r->reason,
                'date' => $r->timeSlot->date->format('d/m/Y'), 'time' => substr($r->timeSlot->start_time, 0, 5),
                'structure' => $r->structure->name, 'practitioner' => $r->practitioner->full_name,
                'created_at' => $r->created_at->format('d/m/Y'),
            ]), 'meta' => ['total' => $data->total(), 'current_page' => $data->currentPage(), 'last_page' => $data->lastPage()]]);
        });
        Route::get('/api/consultations', function (Request $request) {
            $query = Consultation::where('patient_id', auth()->id())->with(['practitioner', 'structure'])->orderByDesc('created_at');
            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(fn ($w) => $w->whereHas('practitioner', fn ($p) => $p->where('last_name', 'ILIKE', "%{$q}%")->orWhere('first_name', 'ILIKE', "%{$q}%"))
                    ->orWhere('motif', 'ILIKE', "%{$q}%")->orWhere('diagnostic', 'ILIKE', "%{$q}%"));
            }
            $data = $query->paginate($request->integer('per_page', 5));

            return response()->json(['data' => $data->map(fn ($c) => [
                'uuid' => $c->uuid, 'status' => $c->status, 'motif' => $c->motif, 'diagnostic' => $c->diagnostic,
                'structure' => $c->structure->name, 'practitioner' => $c->practitioner->full_name,
                'created_at' => $c->created_at->format('d/m/Y'),
            ]), 'meta' => ['total' => $data->total(), 'current_page' => $data->currentPage(), 'last_page' => $data->lastPage()]]);
        });
        // API JSON fiche structure (medecins, services)
        Route::get('/api/structure/{slug}/medecins', function (string $slug, Request $request) {
            $hosto = Hosto::where('slug', $slug)->firstOrFail();
            $query = Practitioner::active()
                ->whereHas('structures', fn ($q) => $q->where('hostos.id', $hosto->id))
                ->with('specialties');
            if ($request->filled('q')) {
                $q = $request->input('q');
                $query->where(fn ($w) => $w->where('last_name', 'ILIKE', "%{$q}%")->orWhere('first_name', 'ILIKE', "%{$q}%")
                    ->orWhereHas('specialties', fn ($s) => $s->where('name_fr', 'ILIKE', "%{$q}%")));
            }
            $data = $query->orderBy('last_name')->paginate($request->integer('per_page', 5));

            return response()->json(['data' => $data->map(fn ($p) => [
                'slug' => $p->slug, 'full_name' => $p->full_name,
                'specialties' => $p->specialties->pluck('name_fr')->toArray(),
                'does_teleconsultation' => $p->does_teleconsultation, 'does_home_care' => $p->does_home_care,
            ]), 'meta' => ['total' => $data->total(), 'current_page' => $data->currentPage(), 'last_page' => $data->lastPage()]]);
        });

        // Booking RDV dans l'espace patient
        Route::get('/rdv/{slug}', [AnnuaireWebController::class, 'bookRdvInDashboard'])->name('compte.book-rdv');

        Route::get('/dossier-medical/{uuid}', function (string $uuid) {
            $consultation = Consultation::where('patient_id', auth()->id())
                ->whereUuid($uuid)
                ->with(['practitioner', 'structure', 'prescriptions.items', 'examRequests', 'careActs', 'treatments'])
                ->firstOrFail();

            return view('compte.consultation-detail', compact('consultation'));
        })->middleware('medical.pin')->name('compte.consultation');
        // Explorer (dans l'espace patient)
        Route::get('/structures', fn () => view('compte.explorer.structures'))->name('compte.structures');
        Route::get('/medecins', fn () => view('compte.explorer.medecins'))->name('compte.medecins');
        Route::get('/medicaments', fn () => view('compte.explorer.medicaments'))->name('compte.medicaments');
        Route::get('/examens', fn () => view('compte.explorer.examens'))->name('compte.examens');
        Route::get('/pharmacies', fn () => view('compte.explorer.pharmacies'))->name('compte.pharmacies');
        Route::get('/hopitaux', fn () => view('compte.explorer.hopitaux'))->name('compte.hopitaux');
        Route::get('/soins-domicile', fn () => view('compte.explorer.soins-domicile'))->name('compte.soins-domicile');
        Route::get('/urgences', fn () => view('compte.explorer.urgences'))->name('compte.urgences');

        // Detail views (dans l'espace patient)
        Route::get('/structure/{slug}', [AnnuaireWebController::class, 'showInDashboard'])->name('compte.structure.show');
        Route::get('/medecin/{slug}', [AnnuaireWebController::class, 'practitionerShowInDashboard'])->name('compte.medecin.show');

        Route::get('/profil', [ProfileController::class, 'show'])->name('compte.profil');
        Route::put('/profil/info', [ProfileController::class, 'updateInfo']);
        Route::put('/profil/password', [ProfileController::class, 'updatePassword']);

        // PIN verification before profile access
        Route::get('/profil/pin', [ProfileController::class, 'showProfilePin'])->name('compte.profile-pin');
        Route::post('/profil/pin-verification', [ProfileController::class, 'verifyProfilePin']);

        // Complete profile flow (protected by PIN if set)
        Route::middleware('profile.pin')->group(function (): void {
            Route::get('/profil/completer', [ProfileController::class, 'completeProfile'])->name('compte.complete-profile');
            Route::put('/profil/identite', [ProfileController::class, 'updateIdentity'])->name('compte.profil.identity');
            Route::post('/profil/identite/document', [ProfileController::class, 'uploadIdDocument'])->name('compte.profil.id-document');
            Route::put('/profil/residence', [ProfileController::class, 'updateResidence'])->name('compte.profil.residence');
            Route::put('/profil/question-secrete', [ProfileController::class, 'updateSecurityQuestion'])->name('compte.profil.security-question');
            Route::put('/profil/pin-medical', [ProfileController::class, 'updateMedicalPin'])->name('compte.profil.medical-pin');
            Route::post('/profil/pin-medical/verify', [ProfileController::class, 'verifyMedicalPin'])->name('compte.profil.verify-pin');
            Route::put('/profil/contacts-urgence', [ProfileController::class, 'updateEmergencyContacts'])->name('compte.profil.emergency');
            Route::post('/profil/photo', [ProfileController::class, 'updatePhoto'])->name('compte.profil.photo');
        });
    });
});

// ---------------------------------------------------------------
// Auth : Professionnel
// ---------------------------------------------------------------

Route::prefix('pro')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'proConnexionForm'])->name('pro.connexion');
        Route::post('/connexion', [AuthController::class, 'proConnexion']);
        Route::get('/inscription', [AuthController::class, 'proInscriptionForm'])->name('pro.inscription');
        Route::post('/inscription', [AuthController::class, 'proInscription']);
    });

    Route::middleware(['auth', 'env:pro'])->group(function (): void {
        Route::get('/', function () {
            return view('pro.dashboard');
        })->name('pro.dashboard');
        Route::get('/consultations', [ProWebController::class, 'consultations'])->name('pro.consultations');
        Route::get('/consultations/nouvelle', [ProWebController::class, 'newConsultation'])->name('pro.consultation.create');
        Route::post('/consultations', [ProWebController::class, 'storeConsultation']);
        Route::get('/consultations/{uuid}', [ProWebController::class, 'showConsultation'])->name('pro.consultation.show');
        Route::post('/consultations/{uuid}/examen', [ProWebController::class, 'storeExamRequest']);
        Route::post('/consultations/{uuid}/soin', [ProWebController::class, 'storeCareAct']);
        Route::post('/consultations/{uuid}/traitement', [ProWebController::class, 'storeTreatment']);
        Route::post('/consultations/{uuid}/ordonnance', [ProWebController::class, 'storePrescription'])->name('pro.prescription.store');
        Route::get('/enregistrer-structure', [ClaimsWebController::class, 'claimForm'])->name('pro.claim');
        Route::post('/enregistrer-structure', [ClaimsWebController::class, 'submitClaim']);
        Route::get('/mes-demandes', [ClaimsWebController::class, 'myClaims'])->name('pro.claims');
        Route::get('/profil', [ProfileController::class, 'show'])->name('pro.profil');
        Route::put('/profil/info', [ProfileController::class, 'updateInfo']);
        Route::put('/profil/password', [ProfileController::class, 'updatePassword']);

        // Visibility & services settings
        Route::get('/visibility', [PractitionerProfileController::class, 'visibilityPage'])->name('pro.visibility');
        Route::put('/visibility/settings', [PractitionerProfileController::class, 'updateVisibility']);
        Route::put('/visibility/services', [PractitionerProfileController::class, 'updateServices']);

        // Publications CRUD
        Route::get('/publications', [PractitionerProfileController::class, 'publicationsPage'])->name('pro.publications');
        Route::post('/publications', [PractitionerProfileController::class, 'storePublication']);
        Route::put('/publications/{uuid}', [PractitionerProfileController::class, 'updatePublication']);
        Route::delete('/publications/{uuid}', [PractitionerProfileController::class, 'deletePublication']);
    });
});

// ---------------------------------------------------------------
// Auth : Admin
// ---------------------------------------------------------------

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/connexion', [AuthController::class, 'adminConnexionForm'])->name('admin.connexion');
        Route::post('/connexion', [AuthController::class, 'adminConnexion']);
    });

    Route::middleware(['auth', 'env:admin'])->group(function (): void {
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
        Route::get('/utilisateurs', [AdminWebController::class, 'users'])->name('admin.users');
        Route::get('/structures', [AdminWebController::class, 'structures'])->name('admin.structures');
        Route::get('/demandes', [AdminWebController::class, 'claims'])->name('admin.claims');
        Route::post('/demandes/{uuid}/review', [AdminWebController::class, 'reviewClaim'])->name('admin.claims.review');
        Route::get('/profil', [ProfileController::class, 'show'])->name('admin.profil');
        Route::put('/profil/info', [ProfileController::class, 'updateInfo']);
        Route::put('/profil/password', [ProfileController::class, 'updatePassword']);

        // CRUD: Structure Types
        Route::get('/structure-types', [AdminReferenceController::class, 'structureTypes'])->name('admin.structure-types');
        Route::post('/structure-types', [AdminReferenceController::class, 'storeStructureType']);
        Route::put('/structure-types/{id}', [AdminReferenceController::class, 'updateStructureType']);

        // CRUD: Specialties
        Route::get('/specialties', [AdminReferenceController::class, 'specialties'])->name('admin.specialties');
        Route::post('/specialties', [AdminReferenceController::class, 'storeSpecialty']);
        Route::put('/specialties/{id}', [AdminReferenceController::class, 'updateSpecialty']);

        // CRUD: Services
        Route::get('/services', [AdminReferenceController::class, 'services'])->name('admin.services');
        Route::post('/services', [AdminReferenceController::class, 'storeService']);
        Route::put('/services/{id}', [AdminReferenceController::class, 'updateService']);

        // CRUD: Reference Data (generic enums)
        Route::get('/references/{category}', [AdminReferenceController::class, 'referenceData'])->name('admin.references');
        Route::post('/references/{category}', [AdminReferenceController::class, 'storeReferenceData']);
        Route::put('/references/item/{id}', [AdminReferenceController::class, 'updateReferenceData']);
        Route::delete('/references/item/{id}', [AdminReferenceController::class, 'deleteReferenceData']);
    });
});

// ---------------------------------------------------------------
// Logout (shared)
// ---------------------------------------------------------------

Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------
// Actions interactives (web session, auth requise)
// ---------------------------------------------------------------

Route::middleware('auth')->group(function (): void {
    // Teleconsultation (requires phone verification)
    Route::middleware('phone.verified')->group(function (): void {
        Route::get('/teleconsultation/{uuid}', [TeleconWebController::class, 'room'])->name('telecon.room');
        Route::post('/web/telecon/{uuid}/join', [TeleconWebController::class, 'markJoined']);
        Route::post('/web/telecon/{uuid}/end', [TeleconWebController::class, 'endSession']);
    });

    // RDV booking (requires phone verification)
    Route::post('/web/rdv/book', [BookingWebController::class, 'bookAppointment'])->middleware('phone.verified')->name('web.rdv.book');
    Route::post('/web/rdv/{uuid}/cancel', [BookingWebController::class, 'cancelAppointment'])->name('web.rdv.cancel');
    Route::post('/web/like/{uuid}', [BookingWebController::class, 'toggleLike'])->name('web.like');
    Route::post('/web/publication/{uuid}/like', [PublicationInteractionController::class, 'toggleLike'])->name('web.pub.like');
    Route::post('/web/publication/{uuid}/comment', [PublicationInteractionController::class, 'addComment'])->name('web.pub.comment');
    Route::post('/web/recommend/{uuid}', [BookingWebController::class, 'recommend'])->name('web.recommend');
    Route::post('/web/evaluate/{uuid}', [ClaimsWebController::class, 'submitEvaluation'])->name('web.evaluate');
});

// ---------------------------------------------------------------
// Verification (email + phone)
// ---------------------------------------------------------------

Route::middleware('auth')->group(function (): void {
    Route::get('/verification', [VerificationController::class, 'show'])->name('verification.notice');
    Route::post('/verification/email/send', [VerificationController::class, 'sendEmailOtp'])->name('verification.send.email');
    Route::post('/verification/email/verify', [VerificationController::class, 'verifyEmailOtp'])->name('verification.verify.email');
    Route::post('/verification/phone/send', [VerificationController::class, 'sendPhoneOtp'])->name('verification.send.phone');
    Route::post('/verification/phone/verify', [VerificationController::class, 'verifyPhoneOtp'])->name('verification.verify.phone');
});

// ---------------------------------------------------------------
// 2FA (shared — authenticated users)
// ---------------------------------------------------------------

Route::middleware('auth')->group(function (): void {
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::get('/2fa/recovery', [TwoFactorController::class, 'recovery'])->name('2fa.recovery');
    Route::delete('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
});

// 2FA challenge (during login — not yet fully authenticated).
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verifyChallengeCode'])->name('2fa.verify');
