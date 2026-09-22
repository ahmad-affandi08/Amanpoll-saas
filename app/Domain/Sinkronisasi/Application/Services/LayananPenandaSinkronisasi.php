<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\Services;

use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Penanda sinkronisasi per perangkat dan jenis entitas (20.04). */
final class LayananPenandaSinkronisasi
{
    public function catat(string $perangkatPenggunaId, string $jenisEntitas, ?string $token = null): PenandaSinkronisasi
    {
        $penanda = PenandaSinkronisasi::query()->firstOrNew([
            'PerangkatPenggunaId' => $perangkatPenggunaId,
            'JenisEntitas' => $jenisEntitas,
        ]);

        $penanda->TokenSinkronisasi = $token ?? (string) Str::ulid();
        $penanda->TerakhirSinkronPada = CarbonImmutable::now();
        $penanda->save();

        return $penanda;
    }

    /**
     * @param  array<string, string>  $token  sidik jari per jenis entitas
     * @return array<string, array{TokenSinkronisasi: string|null, TerakhirSinkronPada: string|null}>
     */
    public function catatPaket(PerangkatPengguna $perangkat, array $token): array
    {
        $hasil = [];
        foreach ($token as $jenisEntitas => $sidikJari) {
            $penanda = $this->catat($perangkat->Id, $jenisEntitas, $sidikJari);
            $hasil[$jenisEntitas] = [
                'TokenSinkronisasi' => $penanda->TokenSinkronisasi,
                'TerakhirSinkronPada' => $penanda->TerakhirSinkronPada?->toIso8601String(),
            ];
        }

        $perangkat->TerakhirSinkronPada = CarbonImmutable::now();
        $perangkat->save();

        return $hasil;
    }

    /**
     * @return array<string, array{TokenSinkronisasi: string|null, TerakhirSinkronPada: string|null}>
     */
    public function untukPerangkat(PerangkatPengguna $perangkat): array
    {
        return PenandaSinkronisasi::query()
            ->where('PerangkatPenggunaId', $perangkat->Id)
            ->get()
            ->mapWithKeys(fn (PenandaSinkronisasi $penanda): array => [
                $penanda->JenisEntitas => [
                    'TokenSinkronisasi' => $penanda->TokenSinkronisasi,
                    'TerakhirSinkronPada' => $penanda->TerakhirSinkronPada?->toIso8601String(),
                ],
            ])
            ->all();
    }

    /** Membuang penanda perangkat, dipakai saat data lokal dibersihkan. */
    public function bersihkan(PerangkatPengguna $perangkat): void
    {
        PenandaSinkronisasi::query()->where('PerangkatPenggunaId', $perangkat->Id)->delete();
    }
}
