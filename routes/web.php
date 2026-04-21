<?php

declare(strict_types=1);

use App\Http\Controllers\AdminWebController;
use App\Http\Controllers\AnnuaireWebController;
use App\Http\Controllers\BookingWebController;
use App\Http\Controllers\ClaimsWebController;
use App\Http\Controllers\ProWebController;
use App\Modules\Core\Http\Controllers\AuthController;
use App\Modules\Core\Http\Controllers\ProfileController;
use App\Modules\Core\Http\Controllers\TwoFactorController;
use App\Modules\Pro\Models\Consultation;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Http\Request;
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
Route::get('/annuaire/{slug}', [AnnuaireWebController::class, 'show'])->name('annuaire.show');

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
            $consultations = Consultation::where('patient_id', auth()->id())
                ->with(['practitioner', 'structure', 'prescriptions.items', 'examRequests', 'careActs', 'treatments'])
                ->orderByDesc('created_at')
                ->get();

            return view('compte.dossier-medical', compact('consultations'));
        })->name('compte.dossier');
        Route::get('/dossier-medical/{uuid}', function (string $uuid) {
            $consultation = Consultation::where('patient_id', auth()->id())
                ->whereUuid($uuid)
                ->with(['practitioner', 'structure', 'prescriptions.items', 'examRequests', 'careActs', 'treatments'])
                ->firstOrFail();

            return view('compte.consultation-detail', compact('consultation'));
        })->name('compte.consultation');
        Route::get('/profil', [ProfileController::class, 'show'])->name('compte.profil');
        Route::put('/profil/info', [ProfileController::class, 'updateInfo']);
        Route::put('/profil/password', [ProfileController::class, 'updatePassword']);
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
    Route::post('/web/rdv/book', [BookingWebController::class, 'bookAppointment'])->name('web.rdv.book');
    Route::post('/web/rdv/{uuid}/cancel', [BookingWebController::class, 'cancelAppointment'])->name('web.rdv.cancel');
    Route::post('/web/like/{uuid}', [BookingWebController::class, 'toggleLike'])->name('web.like');
    Route::post('/web/recommend/{uuid}', [BookingWebController::class, 'recommend'])->name('web.recommend');
    Route::post('/web/evaluate/{uuid}', [ClaimsWebController::class, 'submitEvaluation'])->name('web.evaluate');
});

// ---------------------------------------------------------------
// Verification (email + phone)
// ---------------------------------------------------------------

Route::middleware('auth')->group(function (): void {
    Route::get('/verification', function () {
        return view('auth.verification-notice');
    })->name('verification.notice');

    Route::post('/verification/email', function (Request $request) {
        // TODO: Phase 3.1 — Send actual OTP via email (SMTP + queue).
        return back()->with('success', 'Un code de verification a ete envoye a votre adresse email.');
    })->name('verification.send.email');
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
