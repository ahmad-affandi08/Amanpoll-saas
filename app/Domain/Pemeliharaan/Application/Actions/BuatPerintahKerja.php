<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Shared\Domain\Contracts\TransaksiDatabase;

final class BuatPerintahKerja
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananAudit $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(array $data, string $penggunaId): PerintahKerja
    {
        return $this->transaksi->jalankan(function () use ($data, $penggunaId): PerintahKerja {
            $organisasiId = $this->konteks->wajibId();
            $keluhan = filled($data['KeluhanId'] ?? null)
                ? Keluhan::query()->findOrFail($data['KeluhanId'])
                : null;
            $asetIds = array_values(array_unique(array_filter($data['AsetIds'] ?? [])));

            if ($keluhan?->AsetId !== null && ! in_array($keluhan->AsetId, $asetIds, true)) {
                array_unshift($asetIds, $keluhan->AsetId);
            }

            unset($data['AsetIds']);
            $perintahKerja = PerintahKerja::create([
                ...$data,
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
