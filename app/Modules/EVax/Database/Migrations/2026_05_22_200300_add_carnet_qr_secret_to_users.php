<?php
declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('carnet_qr_secret', 32)->nullable()->unique()->after('medical_pin_set_at');
        });

        // Backfill existing accounts.
        User::query()->whereNull('carnet_qr_secret')->cursor()->each(function (User $u): void {
            $u->forceFill(['carnet_qr_secret' => Str::random(32)])->save();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('carnet_qr_secret');
        });
    }
};
