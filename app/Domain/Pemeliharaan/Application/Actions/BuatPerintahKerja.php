<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Services\PenentuUnitPengelola;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatPerintahKerja
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananAudit $audit,
        private readonly PenentuUnitPengelola $penentuUnitPengelola,
    ) {}

    /**
     * Satu-satunya jalan membuat perintah kerja: formulir dasbor, dari keluhan,
     * penjadwal preventif, dan tindak lanjut inspeksi semuanya lewat sini.
     *
     * Unit pengelola diturunkan di sini, bukan di controller, supaya setiap
     * jalur mendapat hasil yang sama (PRD 8.21): isian eksplisit → keluhan asal
     * → aset utama → rencana asal → kosong. `UnitOrganisasiId` yang kosong
     * diisi unit organisasi aset utamanya.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $unitPengelolaRencana  Unit pengelola rencana preventif asal, bila tiketnya dihasilkan rencana.
     */
    public function jalankan(array $data, string $penggunaId, ?string $unitPengelolaRencana = null): PerintahKerja
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId, $unitPengelolaRencana): PerintahKerja {
            $organisasiId = $this->konteks->wajibId();
            $keluhan = filled($data['KeluhanId'] ?? null)
                ? Keluhan::query()->findOrFail($data['KeluhanId'])
                : null;
            $asetIds = array_values(array_unique(array_filter($data['AsetIds'] ?? [])));

            if ($keluhan?->AsetId !== null && ! in_array($keluhan->AsetId, $asetIds, true)) {
                array_unshift($asetIds, $keluhan->AsetId);
            }

            unset($data['AsetIds']);
            $asetUtamaId = is_string($asetIds[0] ?? null) ? $asetIds[0] : null;
            $isianUnitPengelola = $data['UnitPengelolaId'] ?? null;

            if (is_string($isianUnitPengelola) && filled($isianUnitPengelola)) {
                $alasan = (new UnitPengelolaSah)->alasanDitolak($isianUnitPengelola);

                if ($alasan !== null) {
                    throw new AturanBisnisDilanggar($alasan);
                }
            }

            $perintahKerja = PerintahKerja::create([
                ...$data,
                'UnitPengelolaId' => $this->penentuUnitPengelola->untukPerintahKerja(
                    is_string($isianUnitPengelola) ? $isianUnitPengelola : null,
                    $keluhan instanceof Keluhan ? $keluhan : null,
                    $asetUtamaId,
                    $unitPengelolaRencana,
                ),
                'UnitOrganisasiId' => $this->penentuUnitPengelola->unitOrganisasiDariAset(
                    is_string($data['UnitOrganisasiId'] ?? null) ? $data['UnitOrganisasiId'] : null,
                    $asetUtamaId,
                ),
                'Nomor' => $this->nomorDokumen->berikutnya($organisasiId, 'PerintahKerja'),
                'KeluhanId' => $keluhan?->Id,
                'TingkatLayananId' => $keluhan?->TingkatLayananId,
                'Judul' => ($data['Judul'] ?? null) ?: $keluhan?->Judul,
                'Deskripsi' => ($data['Deskripsi'] ?? null) ?: $keluhan?->Deskripsi,
                'Prioritas' => $data['Prioritas'] ?? $keluhan?->Prioritas ?? 'Normal',
                'LokasiId' => ($data['LokasiId'] ?? null) ?: $keluhan?->LokasiId,
                'BatasResponsPada' => $keluhan?->BatasResponsPada,
                'BatasPenyelesaianPada' => $keluhan?->BatasPenyelesaianPada,
                'Status' => StatusPerintahKerja::Draf->value,
                'DibuatOleh' => $penggunaId,
            ]);

            foreach ($asetIds as $index => $asetId) {
                PerintahKerjaAset::create([
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'AsetId' => $asetId,
                    'Utama' => $index === 0,
                ]);
            }

            RiwayatStatusPerintahKerja::create([
                'PerintahKerjaId' => $perintahKerja->Id,
                'StatusSebelum' => null,
                'StatusSesudah' => StatusPerintahKerja::Draf->value,
                'Catatan' => $keluhan === null ? 'Perintah kerja dibuat manual.' : "Dibuat dari keluhan {$keluhan->Nomor}.",
                'DiubahOleh' => $penggunaId,
                'DiubahPada' => now(),
            ]);

            $this->audit->catat('Buat', 'PerintahKerja', $perintahKerja->Id, null, $perintahKerja->toArray());

            return $perintahKerja;
        });
    }
}
