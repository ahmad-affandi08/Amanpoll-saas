<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AnalisisKegagalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;

final class SimpanAnalisisKegagalan
{
    public function __construct(private readonly LayananAudit $audit) {}

    /** @param array<string, mixed> $data */
    public function jalankan(PerintahKerja $perintahKerja, array $data, string $penggunaId): AnalisisKegagalan
    {
        $analisis = AnalisisKegagalan::query()->firstOrNew(['PerintahKerjaId' => $perintahKerja->Id]);
        $sebelum = $analisis->exists ? $analisis->toArray() : null;
        $dibuatOleh = $analisis->exists ? ($analisis->getAttribute('DibuatOleh') ?? $penggunaId) : $penggunaId;
        $analisis->fill([
            ...$data,
            'OrganisasiId' => $perintahKerja->OrganisasiId,
            'PerintahKerjaId' => $perintahKerja->Id,
            'DibuatOleh' => $dibuatOleh,
        ]);
        $analisis->save();
        $this->audit->catat($sebelum === null ? 'BuatAnalisisKegagalan' : 'UbahAnalisisKegagalan', 'PerintahKerja', $perintahKerja->Id, $sebelum, $analisis->toArray());

        return $analisis;
    }
}
