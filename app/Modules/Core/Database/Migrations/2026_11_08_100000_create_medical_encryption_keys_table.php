<?php

declare(strict_types=1);

use App\Modules\Core\Support\SchemaBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry of medical encryption keys: fingerprint and escrow status only.
 * The key itself is never stored here.
 *
 * @see docs/adr/0017-sequestre-cle-chiffrement.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_encryption_keys', function (Blueprint $table): void {
            SchemaBuilder::base($table);

            $table->string('key_id', 8)->unique();
            $table->unsignedTinyInteger('escrow_shares')->nullable();
            $table->unsignedTinyInteger('escrow_threshold')->nullable();
            $table->timestampTz('escrowed_at')->nullable();
            $table->timestampTz('last_verified_at')->nullable();
            $table->timestampTz('retired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_encryption_keys');
    }
};
