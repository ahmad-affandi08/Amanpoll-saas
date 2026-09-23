<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Organisasi\KalenderOrganisasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Application\Actions\KonsumsiReservasiSukuCadang;
use App\Domain\Persediaan\Application\Actions\LepaskanReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class GunakanSukuCadangPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly KonsumsiReservasiSukuCadang $konsumsi,
        private readonly LepaskanReservasiSukuCadang $lepaskan,
        private readonly LayananAudit $audit,
        private readonly KalenderOrganisasi $kalender,
    ) {}

    public function jalankan(PerintahKerja $perintahKerja, ReservasiSukuCadang $reservasi, string $aksi, string $penggunaId): void
    {
        if ($reservasi->PerintahKerjaId !== $perintahKerja->Id) {
            throw new AturanBisnisDilanggar('Reservasi tidak terkait dengan perintah kerja ini.');
        }

        $this->transaksi->jalankan(function () use ($perintahKerja, $reservasi, $aksi, $penggunaId): void {
            if ($aksi === 'Kembalikan') {
                $this->lepaskan->jalankan($reservasi);

                return;
            }

            $this->konsumsi->jalankan($reservasi, $penggunaId);
            $mutasi = MutasiStok::query()
                ->where('ReferensiJenis', 'ReservasiSukuCadang')
                ->where('ReferensiId', $reservasi->Id)
                ->latest('DibuatPada')
                ->firstOrFail();
            $sukuCadang = SukuCadang::query()->findOrFail($reservasi->SukuCadangId);
            $hargaSatuan = (float) $sukuCadang->HargaRataRata;
            $jumlahBiaya = round((float) $reservasi->Jumlah * $hargaSatuan, 2);

            PemakaianSukuCadang::create([
                'PerintahKerjaId' => $perintahKerja->Id,
                'SukuCadangId' => $reservasi->SukuCadangId,
                'GudangId' => $reservasi->GudangId,
                'Jumlah' => $reservasi->Jumlah,
                'HargaSatuan' => $hargaSatuan,
                'MutasiStokId' => $mutasi->Id,
                'DipakaiOleh' => $penggunaId,
                'DipakaiPada' => now(),
            ]);

            BiayaPerintahKerja::create([
                'PerintahKerjaId' => $perintahKerja->Id,
                'JenisBiaya' => 'Sparepart',
                'Deskripsi' => "{$sukuCadang->Kode} · {$sukuCadang->Nama}",
                'Jumlah' => $jumlahBiaya,
                'MataUang' => 'IDR',
                'TanggalBiaya' => $this->kalender->hariIni($perintahKerja->OrganisasiId)->toDateString(),
                'DibuatOleh' => $penggunaId,
            ]);
        });

        $this->audit->catat("SukuCadang.{$aksi}", 'PerintahKerja', $perintahKerja->Id, null, [
            'ReservasiSukuCadangId' => $reservasi->Id,
            'Jumlah' => $reservasi->Jumlah,
        ]);
    }
}
