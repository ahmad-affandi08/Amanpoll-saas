<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Persetujuan\Application\Services\LayananNotifikasiPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class AjukanPermintaanPersetujuan
{
    public function __construct(
        private readonly RegistriEntitas $registriEntitas,
        private readonly LayananNotifikasiPersetujuan $layananNotifikasiPersetujuan,
    ) {}

    /**
     * @param array<string, mixed>|null $dataTambahan
     */
    public function jalankan(
        AlurPersetujuan $alurPersetujuan,
        string $entitasId,
        ?array $dataTambahan,
        string $pemintaId,
    ): PermintaanPersetujuan {
        if (!$alurPersetujuan->Aktif) {
            throw new AturanBisnisDilanggar('Alur persetujuan tidak aktif.');
        }

        // Melempar DataTidakDitemukan (404) kalau entitas tidak dikenal/lintas organisasi.
        $entitas = $this->registriEntitas->cariEntitas($alurPersetujuan->JenisEntitas, $entitasId);

        $sudahMenunggu = PermintaanPersetujuan::query()
            ->where('JenisEntitas', $alurPersetujuan->JenisEntitas)
            ->where('EntitasId', $entitasId)
            ->where('Status', PermintaanPersetujuan::STATUS_MENUNGGU)
            ->exists();

        if ($sudahMenunggu) {
            throw new AturanBisnisDilanggar('Entitas ini masih punya permintaan persetujuan yang belum selesai.');
        }

        $tahapPertama = $alurPersetujuan->tahapPersetujuan()->orderBy('Urutan')->first();
        if (!$tahapPertama) {
            throw new AturanBisnisDilanggar('Alur persetujuan belum punya tahap.');
        }

        $permintaan = PermintaanPersetujuan::create([
            'AlurPersetujuanId' => $alurPersetujuan->Id,
            'JenisEntitas' => $alurPersetujuan->JenisEntitas,
            'EntitasId' => $entitasId,
            'TahapSaatIni' => $tahapPertama->Urutan,
            'Status' => PermintaanPersetujuan::STATUS_MENUNGGU,
            'DimintaOleh' => $pemintaId,
            'DimintaPada' => now(),
            'DataTambahan' => $dataTambahan,
        ]);

        $this->layananNotifikasiPersetujuan->beriTahuTahapBaru($permintaan, $tahapPertama, $entitas);

        return $permintaan;
    }
}
