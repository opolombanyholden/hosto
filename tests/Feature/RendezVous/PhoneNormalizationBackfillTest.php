<?php

declare(strict_types=1);

namespace Tests\Feature\RendezVous;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PhoneNormalizationBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_normalized_column_exists_and_accepts_e164(): void
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['phone_normalized' => '+241060000001']);

        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('users');
        $this->assertContains('phone_normalized', $cols);
        $this->assertSame('+241060000001', $u->fresh()->phone_normalized);
    }

    public function test_phone_normalized_is_unique(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        DB::table('users')->where('id', $u1->id)->update(['phone_normalized' => '+241060000002']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('users')->where('id', $u2->id)->update(['phone_normalized' => '+241060000002']);
    }
}
