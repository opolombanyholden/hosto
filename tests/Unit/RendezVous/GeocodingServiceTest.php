<?php
declare(strict_types=1);

namespace Tests\Unit\RendezVous;

use App\Modules\RendezVous\Services\GeocodingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeocodingServiceTest extends TestCase
{
    public function test_geocode_returns_lat_lng_for_known_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '0.4162', 'lon' => '9.4673', 'display_name' => 'Libreville, Gabon'],
            ], 200),
        ]);

        $svc = new GeocodingService();
        $out = $svc->geocode('Libreville');
        $this->assertNotNull($out);
        $this->assertEqualsWithDelta(0.4162, $out['lat'], 0.001);
        $this->assertEqualsWithDelta(9.4673, $out['lng'], 0.001);
    }

    public function test_geocode_returns_null_on_empty_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([], 200),
        ]);
        $svc = new GeocodingService();
        $this->assertNull($svc->geocode('XYZ_invalid_address'));
    }

    public function test_reverse_geocode_returns_address(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Quartier Glass, Libreville, Estuaire, Gabon',
            ], 200),
        ]);

        $svc = new GeocodingService();
        $address = $svc->reverseGeocode(0.4162, 9.4673);
        $this->assertSame('Quartier Glass, Libreville, Estuaire, Gabon', $address);
    }

    public function test_reverse_geocode_returns_null_on_error(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response('', 500),
        ]);
        $svc = new GeocodingService();
        $this->assertNull($svc->reverseGeocode(0.0, 0.0));
    }
}
