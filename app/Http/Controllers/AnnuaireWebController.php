<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Web controller for the public Annuaire pages.
 *
 * Serves Blade views. The actual data for the listing page is loaded
 * client-side from the API (annuaire/index.blade.php uses fetch).
 *
 * The detail page is server-rendered for SEO and sharing (meta tags
 * with the structure name, description, and coordinates).
 */
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

        return view('annuaire.show', compact('hosto'));
    }
}
