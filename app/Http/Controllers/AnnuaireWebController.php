<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoRecommendation;
use App\Modules\Annuaire\Models\Practitioner;
use App\Modules\Annuaire\Models\PractitionerPublication;
use App\Modules\RendezVous\Models\TimeSlot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AnnuaireWebController
{
    public function index(): View
    {
        return view('annuaire.index');
    }

    public function show(string $slug): View
    {
        $hosto = Hosto::where('slug', $slug)
            ->with(['city.region.country', 'structureTypes', 'specialties', 'services', 'media'])
            ->first();

        if ($hosto === null) {
            throw new NotFoundHttpException('Structure introuvable.');
        }

        // Practitioners working at this structure.
        $practitioners = Practitioner::active()
            ->whereHas('structures', fn ($q) => $q->where('hostos.id', $hosto->id))
            ->with('specialties')
            ->orderBy('last_name')
            ->get();

        // Approved recommendations.
        $recommendations = HostoRecommendation::where('hosto_id', $hosto->id)
            ->approved()
            ->with('user:id,name,uuid')
            ->orderByDesc('approved_at')
            ->limit(10)
            ->get();

        // User like status.
        $userLiked = false;
        if (auth()->check()) {
            $userLiked = $hosto->likes()->where('user_id', auth()->id())->exists();
        }

        return view('annuaire.show', compact('hosto', 'practitioners', 'recommendations', 'userLiked'));
    }

    public function practitioners(Request $request): View
    {
        return view('annuaire.practitioners');
    }

    public function practitionerShow(string $slug): View
    {
        $practitioner = Practitioner::where('slug', $slug)
            ->with(['specialties', 'structures.city.region', 'structures.structureTypes'])
            ->firstOrFail();

        // Available time slots for the next 7 days.
        $slots = TimeSlot::where('practitioner_id', $practitioner->id)
            ->available()
            ->with('structure')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(40)
            ->get()
            ->groupBy(fn ($s) => $s->date->toDateString());

        $publications = PractitionerPublication::where('practitioner_id', $practitioner->id)
            ->published()
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return view('annuaire.practitioner-show', compact('practitioner', 'slots', 'publications'));
    }

    public function bookRdv(string $slug): View|RedirectResponse
    {
        $hosto = Hosto::where('slug', $slug)
            ->with('specialties')
            ->firstOrFail();

        if (! $hosto->is_partner) {
            abort(403, 'La prise de rendez-vous n\'est disponible que pour les structures partenaires HOSTO.');
        }

        if (! auth()->check()) {
            session(['url.intended' => url()->current()]);

            return redirect()->route('compte.connexion')
                ->with('info', 'Connectez-vous pour prendre rendez-vous chez '.$hosto->name.'.');
        }

        return redirect()->route('compte.book-rdv', ['slug' => $slug]);
    }

    public function medications(): View
    {
        return view('annuaire.medications');
    }

    public function exams(): View
    {
        return view('annuaire.exams');
    }

    /**
     * Booking RDV in patient dashboard layout.
     */
    public function bookRdvInDashboard(string $slug): View
    {
        $hosto = Hosto::where('slug', $slug)
            ->with('specialties')
            ->firstOrFail();

        $specialties = $hosto->specialties;

        return view('compte.explorer.book-rdv', compact('hosto', 'specialties'));
    }

    /**
     * Structure detail in patient dashboard layout.
     */
    public function showInDashboard(string $slug): View
    {
        $hosto = Hosto::where('slug', $slug)
            ->with(['city.region.country', 'structureTypes', 'specialties', 'services', 'media'])
            ->firstOrFail();

        $practitioners = Practitioner::active()
            ->whereHas('structures', fn ($q) => $q->where('hostos.id', $hosto->id))
            ->with('specialties')
            ->orderBy('last_name')
            ->get();

        $recommendations = HostoRecommendation::where('hosto_id', $hosto->id)
            ->approved()
            ->with('user:id,name,uuid')
            ->orderByDesc('approved_at')
            ->limit(10)
            ->get();

        $userLiked = auth()->check() && $hosto->likes()->where('user_id', auth()->id())->exists();
        $userRecommended = auth()->check() && HostoRecommendation::where('user_id', auth()->id())->where('hosto_id', $hosto->id)->exists();
        $recoCount = HostoRecommendation::where('hosto_id', $hosto->id)->count();

        return view('compte.explorer.structure-detail', compact('hosto', 'practitioners', 'recommendations', 'userLiked', 'userRecommended', 'recoCount'));
    }

    /**
     * Practitioner detail in patient dashboard layout.
     */
    public function practitionerShowInDashboard(string $slug): View
    {
        $practitioner = Practitioner::where('slug', $slug)
            ->with(['specialties', 'structures.city.region', 'structures.structureTypes'])
            ->firstOrFail();

        $slots = TimeSlot::where('practitioner_id', $practitioner->id)
            ->available()
            ->with('structure')
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(40)
            ->get()
            ->groupBy(fn ($s) => $s->date->toDateString());

        $publications = PractitionerPublication::where('practitioner_id', $practitioner->id)
            ->published()
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return view('compte.explorer.medecin-detail', compact('practitioner', 'slots', 'publications'));
    }
}
