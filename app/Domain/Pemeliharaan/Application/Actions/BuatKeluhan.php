<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Peristiwa\LayananKotakKeluar;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Application\Services\LayananKalkulasiSla;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Support\Facades\DB;

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
    ) {}

    /** @param array<string, mixed> $data */
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

    private function kirimNotifikasiRouting(Keluhan $keluhan): void
    {
        $peranId = KategoriKeluhan::query()->whereKey($keluhan->KategoriKeluhanId)->value('PeranPenanggungJawabId');
        if ($peranId === null) {
            return;
        }

        $penerima = DB::table('PenggunaPeran')
            ->where('OrganisasiId', $keluhan->OrganisasiId)
            ->where('PeranId', $peranId)
            ->where('PenggunaId', '!=', $keluhan->PelaporId)
            ->where(fn ($query) => $query->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now()))
            ->where(fn ($query) => $query->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now()))
            ->distinct()
            ->pluck('PenggunaId');

        foreach ($penerima as $penggunaId) {
            $this->notifikasi->kirim(
                penggunaId: (string) $penggunaId,
                jenisPeristiwa: 'Keluhan.Baru',
                isi: "Keluhan {$keluhan->Nomor} ({$keluhan->Judul}) perlu ditinjau.",
                judul: 'Keluhan Baru',
                jenisEntitas: 'Keluhan',
                entitasId: $keluhan->Id,
            );
        }
    }
}
