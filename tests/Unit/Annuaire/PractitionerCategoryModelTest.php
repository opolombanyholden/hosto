<?php
declare(strict_types=1);

namespace Tests\Unit\Annuaire;

use App\Modules\Annuaire\Models\PractitionerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PractitionerCategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $c = PractitionerCategory::create([
            'code' => 'doctor',
            'name_fr' => 'Médecin généraliste',
            'icon_name' => 'stethoscope',
            'color_hex' => '#388E3C',
            'is_medical' => true,
            'display_order' => 1,
        ]);
        $this->assertNotNull($c->uuid);
        $this->assertTrue($c->is_medical);
    }

    public function test_category_can_have_parent(): void
    {
        $parent = PractitionerCategory::create([
            'code' => 'specialist',
            'name_fr' => 'Spécialiste',
            'display_order' => 1,
        ]);
        $child = PractitionerCategory::create([
            'code' => 'cardiologist',
            'name_fr' => 'Cardiologue',
            'parent_category_id' => $parent->id,
            'display_order' => 2,
        ]);
        $this->assertSame($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('code', 'cardiologist'));
    }
}
