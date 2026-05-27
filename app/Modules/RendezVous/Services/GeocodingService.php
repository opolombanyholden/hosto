<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Modules\RendezVous\Jobs\GeocodeAppointmentAddressJob;
use App\Modules\RendezVous\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeocodingService
{
    private const NOMINATIM_BASE = 'https://nominatim.openstreetmap.org';
    private const USER_AGENT = 'HOSTO/1.0 (contact@hosto.ga)';

    /** @return array{lat: float, lng: float, accuracy_m: int|null}|null */
    public function geocode(string $address): ?array
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::NOMINATIM_BASE.'/search', [
                    'q' => $address, 'format' => 'json', 'limit' => 1,
                ]);
            if (! $resp->ok()) return null;
            $data = $resp->json();
            if (! is_array($data) || count($data) === 0) return null;
            return [
                'lat' => (float) ($data[0]['lat'] ?? 0),
                'lng' => (float) ($data[0]['lon'] ?? 0),
                'accuracy_m' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('geocoding.search.failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get(self::NOMINATIM_BASE.'/reverse', [
                    'lat' => $lat, 'lon' => $lng, 'format' => 'json',
                ]);
            if (! $resp->ok()) return null;
            $data = $resp->json();
            return is_array($data) ? ($data['display_name'] ?? null) : null;
        } catch (\Throwable $e) {
            Log::warning('geocoding.reverse.failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function dispatchGeocodeJob(Appointment $apt): void
    {
        GeocodeAppointmentAddressJob::dispatch($apt->id);
    }
}
