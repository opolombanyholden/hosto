<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Http\Controllers;

use App\Modules\Referentiel\Http\Resources\ServiceResource;
use App\Modules\Referentiel\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ServicesController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Service::active();

        if ($request->filled('category')) {
            $query->category((string) $request->input('category'));
        }

        return ServiceResource::collection(
            $query->orderBy('category')->orderBy('display_order')->orderBy('name_fr')->get(),
        );
    }
}
