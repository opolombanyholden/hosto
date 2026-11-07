<?php

declare(strict_types=1);

namespace Tests\Feature\Pharma;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Pharma\Models\PharmacyStock;
use App\Modules\Referentiel\Models\Medication;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class AccentInsensitiveSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_accented_query_finds_unaccented_medication(): void
    {
        $this->stockFor('Zzparacetamol');

        $this->getJson('/api/v1/pharma/stock?medication=zzparacétamol')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_unaccented_query_finds_accented_medication(): void
    {
        $this->stockFor('Zzibuprofène');

        $this->getJson('/api/v1/pharma/stock?medication=ZZIBUPROFENE')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_like_wildcards_are_treated_as_plain_text(): void
    {
        $this->stockFor('Zzamoxicilline');

        $this->getJson('/api/v1/pharma/stock?medication=zz%25xicilline')->assertOk()->assertJsonPath('meta.total', 0);
    }

    private function stockFor(string $dci): void
    {
        $medication = Medication::create(['dci' => $dci, 'strength' => '500mg', 'dosage_form' => 'comprime', 'is_active' => true]);
        PharmacyStock::create([
            'hosto_id' => Hosto::factory()->create()->id, 'medication_id' => $medication->id,
            'quantity_in_stock' => 20, 'unit_price' => 900, 'currency_code' => 'XAF', 'is_available' => true,
        ]);
    }

    public function test_exam_search_with_accents_runs_on_real_columns(): void
    {
        $this->getJson('/api/v1/lab/exams/search?exam=échographie')->assertOk();
    }
}
