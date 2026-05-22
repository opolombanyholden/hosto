<?php
declare(strict_types=1);

namespace App\Modules\EVax\Http\Controllers;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\VaccinationRecord;
use App\Modules\EVax\Services\CarnetSignerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EVaxPublicController
{
    public function verify(Request $request, string $secret): View
    {
        $carnet = $this->lookup($secret);
        if (! $carnet) {
            abort(404, 'Carnet introuvable.');
        }

        $records = ($carnet instanceof User
            ? VaccinationRecord::where('patient_id', $carnet->id)
            : VaccinationRecord::where('dependent_id', $carnet->id))
            ->orderBy('administered_at')
            ->with(['vaccine', 'hosto'])
            ->get();

        Log::info('evax.verify.public', [
            'kind' => $carnet instanceof User ? 'user' : 'dependent',
            'sub' => substr($carnet->uuid, 0, 8),
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 100),
        ]);

        return view('evax::public.verify', [
            'subject' => $carnet,
            'records' => $records,
            'verifiedAt' => now(),
        ]);
    }

    public function identity(string $secret): View
    {
        $u = User::where('carnet_qr_secret', $secret)->first();
        if (! $u) {
            abort(404, 'Carnet introuvable.');
        }
        return view('evax::public.identity', ['user' => $u]);
    }

    public function jwks(CarnetSignerService $signer): JsonResponse
    {
        return response()->json($signer->jwks());
    }

    private function lookup(string $secret): User|Dependent|null
    {
        $u = User::where('carnet_qr_secret', $secret)->first();
        if ($u) {
            return $u;
        }
        return Dependent::where('carnet_qr_secret', $secret)->first();
    }
}
