<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Shared\Domain\Services\PemeriksaHierarkiSirkular;

final class SimpanKategoriKeluhan
{
    public function __construct(private readonly LayananAudit $audit) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, ?KategoriKeluhan $kategori = null): KategoriKeluhan
    {
        $kategori ??= new KategoriKeluhan;
        $sebelum = $kategori->exists ? $kategori->toArray() : null;

        if ($kategori->exists) {
            PemeriksaHierarkiSirkular::pastikanTidakSirkular('KategoriKeluhan', 'IndukId', $kategori->Id, $data['IndukId'] ?? null);
        }

        $kategori->fill($data);
        $kategori->save();
        $this->audit->catat($sebelum ? 'Ubah' : 'Buat', 'KategoriKeluhan', $kategori->Id, $sebelum, $kategori->toArray());

        return $kategori;
    }
}
