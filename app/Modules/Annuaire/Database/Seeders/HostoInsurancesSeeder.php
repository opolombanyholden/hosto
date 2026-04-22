<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use Illuminate\Database\Seeder;

/**
 * Seeds accepted_insurances for all active structures.
 *
 * Major Gabonese insurance providers:
 *   - CNAMGS   (Caisse Nationale d'Assurance Maladie et de Garantie Sociale)
 *   - ASCOMA   (courtier d'assurance)
 *   - OGAR     (groupe assurance Gabon)
 *   - AXA      (international)
 *   - NSIA     (Nouvelle Societe Interafricaine d'Assurance)
 *   - SUNU     (Sunu Assurances)
 *   - Saham    (groupe Saham)
 */
final class HostoInsurancesSeeder extends Seeder
{
    /** @var list<string> */
    private const ALL_INSURANCES = [
        'CNAMGS', 'ASCOMA', 'OGAR', 'AXA', 'NSIA', 'SUNU', 'Saham',
    ];

    public function run(): void
    {
        $hostos = Hosto::whereNull('accepted_insurances')->get();

        foreach ($hostos as $hosto) {
            // All structures accept CNAMGS (public mandatory insurance).
            // Then randomly accept 2-5 additional insurers.
            $extras = collect(array_slice(self::ALL_INSURANCES, 1))
                ->shuffle()
                ->take(random_int(2, 5))
                ->values()
                ->all();

            $hosto->update([
                'accepted_insurances' => array_merge(['CNAMGS'], $extras),
            ]);
        }
    }
}
