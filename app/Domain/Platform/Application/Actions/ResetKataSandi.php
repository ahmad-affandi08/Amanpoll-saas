<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ResetKataSandi
{
    private const KEDALUWARSA_MENIT = 60;

    public function jalankan(string $penggunaId, string $tokenMentah, string $kataSandiBaru): void
    {
        $baris = DB::table('TokenResetKataSandi')->where('PenggunaId', $penggunaId)->first();

        if (! $baris || ! hash_equals($baris->TokenHash, hash('sha256', $tokenMentah))) {
            throw new AturanBisnisDilanggar('Tautan reset kata sandi tidak valid.');
        }

        if (Carbon::parse($baris->DibuatPada)->lt(now()->subMinutes(self::KEDALUWARSA_MENIT))) {
            DB::table('TokenResetKataSandi')->where('PenggunaId', $penggunaId)->delete();
            throw new AturanBisnisDilanggar('Tautan reset kata sandi sudah kedaluwarsa.');
        }

        $pengguna = Pengguna::query()->findOrFail($penggunaId);
        $pengguna->KataSandi = $kataSandiBaru;
        $pengguna->save();

        DB::table('TokenResetKataSandi')->where('PenggunaId', $penggunaId)->delete();
    }
}
