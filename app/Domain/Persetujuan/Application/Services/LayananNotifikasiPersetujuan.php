<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Services;

use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use Illuminate\Database\Eloquent\Model;

/** Membungkus LayananNotifikasi khusus untuk peristiwa alur persetujuan -- memisahkan "kapan. */
final class LayananNotifikasiPersetujuan
{
    public function __construct(
        private readonly LayananNotifikasi $layananNotifikasi,
        private readonly LayananPenyetuju $layananPenyetuju,
    ) {}

    public function beriTahuTahapBaru(PermintaanPersetujuan $permintaan, TahapPersetujuan $tahap, Model $entitas): void
    {
        foreach ($this->layananPenyetuju->calonPenyetuju($tahap, $entitas) as $penyetuju) {
            $this->layananNotifikasi->kirim(
                penggunaId: $penyetuju->Id,
                jenisPeristiwa: 'Persetujuan.PerluTindakan',
                isi: "Permintaan persetujuan {$permintaan->JenisEntitas} menunggu keputusan Anda pada tahap \"{$tahap->Nama}\".",
                judul: 'Perlu Persetujuan Anda',
                jenisEntitas: $permintaan->JenisEntitas,
                entitasId: $permintaan->EntitasId,
            );
        }
    }

    public function beriTahuSelesai(PermintaanPersetujuan $permintaan, string $jenisPeristiwa, string $pesan): void
    {
        $this->layananNotifikasi->kirim(
            penggunaId: $permintaan->DimintaOleh,
            jenisPeristiwa: $jenisPeristiwa,
            isi: $pesan,
            judul: 'Status Permintaan Persetujuan',
            jenisEntitas: $permintaan->JenisEntitas,
            entitasId: $permintaan->EntitasId,
        );
    }
}
