<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\Actions;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;

final class BuatPenilaianPenyedia
{
    private const KOMPONEN_SKOR = ['SkorKualitas', 'SkorKetepatanWaktu', 'SkorHarga', 'SkorLayanan'];

    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Penyedia $penyedia, array $data, string $dinilaiOleh): PenilaianPenyedia
    {
        $komponen = array_filter(
            array_map(static fn (string $kolom) => $data[$kolom] ?? null, self::KOMPONEN_SKOR),
            static fn (mixed $nilai): bool => $nilai !== null,
        );

        $data['SkorTotal'] = $komponen === [] ? null : round(array_sum($komponen) / count($komponen), 2);
        $data['DinilaiOleh'] = $dinilaiOleh;

        return $penyedia->penilaianPenyedia()->create($data);
    }
}
