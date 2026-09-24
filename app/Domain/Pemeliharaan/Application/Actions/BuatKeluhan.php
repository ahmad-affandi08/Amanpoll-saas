<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Peristiwa\LayananKotakKeluar;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Application\Services\LayananKalkulasiSla;
use App\Domain\Pemeliharaan\Application\Services\PenentuUnitPengelola;
use App\Domain\Pemeliharaan\Application\Services\PenerimaNotifikasiKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;

final class BuatKeluhan
{
    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNomorDokumen $nomorDokumen,
        private readonly LayananKalkulasiSla $kalkulasiSla,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananAudit $audit,
        private readonly LayananKotakKeluar $kotakKeluar,
        private readonly PenentuUnitPengelola $penentuUnitPengelola,
        private readonly PenerimaNotifikasiKeluhan $penerimaNotifikasi,
    ) {}

    /**
     * Satu-satunya jalan keluhan tercipta: dasbor, Mode Lapangan (online dan
     * antrean offline), dan API semuanya lewat sini.
     *
     * `UnitPengelolaId` selalu diturunkan di sini dari kategori (naik ke induk)
     * lalu aset (PRD 8.21), bukan diterima dari pemanggil, supaya keluhan yang
     * sama mendarat di antrean yang sama dari jalur mana pun.
     *
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $pelaporId): Keluhan
    {
        $keluhan = $this->transaksi->jalankan(function () use ($data, $pelaporId): Keluhan {
            $organisasiId = $this->konteks->wajibId();
            $kategori = KategoriKeluhan::query()->with('tingkatLayanan')->findOrFail($data['KategoriKeluhanId']);
            $prioritas = $data['Prioritas'] ?? $kategori->PrioritasBawaan;
            $dilaporkanPada = now()->toImmutable();
            $batas = ['respons' => null, 'penyelesaian' => null];

            if ($kategori->tingkatLayanan !== null) {
                $aturan = AturanTingkatLayanan::query()
                    ->where('TingkatLayananId', $kategori->TingkatLayananId)
                    ->where('Prioritas', $prioritas)
                    ->first();

                if ($aturan !== null) {
                    $lokasi = Lokasi::query()->findOrFail($data['LokasiId']);
                    $zonaWaktu = $lokasi->ZonaWaktu ?: Organisasi::query()->findOrFail($organisasiId)->ZonaWaktu;
                    $batas = $this->kalkulasiSla->hitungBatas(
                        $organisasiId,
                        $kategori->tingkatLayanan,
                        $aturan,
                        $dilaporkanPada,
                        $zonaWaktu,
                        $lokasi->Id,
                    );
                }
            }

            $keluhan = Keluhan::create([
                ...$data,
                'UnitPengelolaId' => $this->penentuUnitPengelola->untukKeluhan(
                    is_string($data['KategoriKeluhanId']) ? $data['KategoriKeluhanId'] : null,
                    is_string($data['AsetId'] ?? null) && $data['AsetId'] !== '' ? $data['AsetId'] : null,
                ),
                'Nomor' => $this->nomorDokumen->berikutnya($organisasiId, 'Keluhan'),
                'TingkatLayananId' => $kategori->TingkatLayananId,
                'Prioritas' => $prioritas,
                'Status' => StatusKeluhan::Baru->value,
                'Sumber' => $data['Sumber'] ?? 'Web',
                'PelaporId' => $pelaporId,
                'DilaporkanPada' => $dilaporkanPada,
                'BatasResponsPada' => $batas['respons'],
                'BatasPenyelesaianPada' => $batas['penyelesaian'],
            ]);

            RiwayatStatusKeluhan::create([
                'KeluhanId' => $keluhan->Id,
                'StatusSebelum' => null,
                'StatusSesudah' => StatusKeluhan::Baru->value,
                'Catatan' => 'Keluhan dibuat.',
                'DiubahOleh' => $pelaporId,
                'DiubahPada' => $dilaporkanPada,
            ]);

            $this->audit->catat('Buat', 'Keluhan', $keluhan->Id, null, $keluhan->toArray());

            // Peristiwa ditulis di transaksi yang sama.
            $this->kotakKeluar->catat('Keluhan.Dibuat', [
                'KeluhanId' => $keluhan->Id,
                'Nomor' => $keluhan->Nomor,
                'Judul' => $keluhan->Judul,
                'Prioritas' => $keluhan->Prioritas,
                'Status' => $keluhan->Status,
                'Sumber' => $keluhan->Sumber,
            ], 'Keluhan', $keluhan->Id);

            return $keluhan;
        });

        $this->kirimNotifikasiRouting($keluhan);

        return $keluhan;
    }

    /**
     * Routing kategori (`PeranPenanggungJawabId`), atau koordinator unit pengelola
     * bila kategorinya tidak menunjuk peran; hanya penerima yang lingkupnya
     * mencakup keluhan ini (lihat `PenerimaNotifikasiKeluhan`).
     */
    private function kirimNotifikasiRouting(Keluhan $keluhan): void
    {
        $peranId = KategoriKeluhan::query()->whereKey($keluhan->KategoriKeluhanId)->value('PeranPenanggungJawabId');

        foreach ($this->penerimaNotifikasi->untukKeluhanBaru($keluhan, is_string($peranId) ? $peranId : null) as $penggunaId) {
            $this->notifikasi->kirim(
                penggunaId: $penggunaId,
                jenisPeristiwa: 'Keluhan.Baru',
                isi: "Keluhan {$keluhan->Nomor} ({$keluhan->Judul}) perlu ditinjau.",
                judul: 'Keluhan Baru',
                jenisEntitas: 'Keluhan',
                entitasId: $keluhan->Id,
            );
        }
    }
}
