<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Application\Services\PenerimaNotifikasiKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\VersiDataBerubah;

/**
 * Memindahkan keluhan ke antrean unit pengelola lain (PRD 8.21), mis. printer
 * yang telanjur masuk IPSRS dialihkan ke IT.
 *
 * Tercatat tiga kali: audit (sebelum/sesudah beserta alasannya), riwayat
 * keluhan, dan notifikasi ke koordinator unit tujuan. Baris riwayatnya
 * memakai status yang sama di kedua sisi (`StatusSebelum` = `StatusSesudah`)
 * karena statusnya memang tidak berubah; hanya pengalihan yang menulis baris
 * seperti itu, dan tampilan riwayat membacanya sebagai "dialihkan".
 *
 * Perintah kerja keluhan ini yang belum final dan masih berada di antrean
 * lama ikut dialihkan lewat `AlihkanUnitPengelolaPerintahKerja`, dalam
 * transaksi yang sama: satu pekerjaan tidak boleh terbelah di dua antrean.
 * Bila salah satunya tidak dapat dialihkan (mis. teknisinya akan kehilangan
 * tiket), seluruh pengalihan ditolak dengan alasan itu. Perintah kerja yang
 * sengaja diarahkan ke unit lain dibiarkan.
 */
final class AlihkanUnitPengelolaKeluhan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly LayananNotifikasi $notifikasi,
        private readonly PenerimaNotifikasiKeluhan $penerimaNotifikasi,
        private readonly AlihkanUnitPengelolaPerintahKerja $alihkanPerintahKerja,
    ) {}

    public function jalankan(Keluhan $keluhan, string $unitPengelolaId, string $alasan, int $versi, string $penggunaId): Keluhan
    {
        $dialihkan = $this->transaksi->jalankan(function () use ($keluhan, $unitPengelolaId, $alasan, $versi, $penggunaId): Keluhan {
            $terkunci = Keluhan::query()->lockForUpdate()->findOrFail($keluhan->Id);
            if ($terkunci->Versi !== $versi) {
                throw new VersiDataBerubah('Keluhan telah berubah. Muat ulang halaman sebelum mencoba lagi.');
            }

            $hambatan = (new UnitPengelolaSah)->alasanDitolak($unitPengelolaId)
                ?? $this->hambatan($terkunci, $unitPengelolaId);
            if ($hambatan !== null) {
                throw new AturanBisnisDilanggar($hambatan);
            }

            $perintahKerjaIkut = $this->perintahKerjaIkut($terkunci);
            $sebelum = $terkunci->toArray();
            $namaAsal = $this->namaUnit($terkunci->UnitPengelolaId);
            $terkunci->UnitPengelolaId = $unitPengelolaId;
            $terkunci->Versi++;
            $terkunci->save();

            foreach ($perintahKerjaIkut as $perintahKerja) {
                if ($perintahKerja->UnitPengelolaId !== $unitPengelolaId) {
                    $this->alihkanPerintahKerja->jalankan($perintahKerja, $unitPengelolaId, "Ikut keluhan {$terkunci->Nomor}: {$alasan}");
                }
            }

            $catatan = $namaAsal === null
                ? "Dialihkan ke unit pengelola {$this->namaUnit($unitPengelolaId)}. Alasan: {$alasan}"
                : "Dialihkan dari {$namaAsal} ke {$this->namaUnit($unitPengelolaId)}. Alasan: {$alasan}";

            RiwayatStatusKeluhan::create([
                'KeluhanId' => $terkunci->Id,
                'StatusSebelum' => $terkunci->Status,
                'StatusSesudah' => $terkunci->Status,
                'Catatan' => $catatan,
                'DiubahOleh' => $penggunaId,
                'DiubahPada' => now()->toImmutable(),
            ]);

            $this->audit->catat(
                'AlihkanUnitPengelola',
                'Keluhan',
                $terkunci->Id,
                $sebelum,
                [...$terkunci->toArray(), 'AlasanPengalihan' => $alasan],
            );

            return $terkunci;
        });

        $this->beriTahuUnitTujuan($dialihkan, $penggunaId);

        return $dialihkan;
    }

    /**
     * Alasan keluhan ini tidak dapat dialihkan ke unit itu, atau null bila boleh.
     *
     * Dipakai bersama request (galat isian yang terbaca), halaman detail
     * (tanpa `$unitPengelolaId`: tombol dimatikan beserta alasannya), dan
     * Action ini sendiri (penjaga terakhir di dalam kunci baris). Keabsahan
     * unit tujuannya sendiri diperiksa `UnitPengelolaSah`.
     */
    public function hambatan(Keluhan $keluhan, ?string $unitPengelolaId = null): ?string
    {
        if (StatusKeluhan::from($keluhan->Status)->final()) {
            return 'Keluhan yang sudah ditutup, ditolak, atau dibatalkan tidak dapat dialihkan.';
        }

        if ($unitPengelolaId === null) {
            return null;
        }

        if ($unitPengelolaId === $keluhan->UnitPengelolaId) {
            return 'Keluhan ini sudah dikelola unit tersebut.';
        }

        foreach ($this->perintahKerjaIkut($keluhan) as $perintahKerja) {
            if ($perintahKerja->UnitPengelolaId === $unitPengelolaId) {
                continue;
            }

            $alasan = $this->alihkanPerintahKerja->alasanDitolak($perintahKerja, $unitPengelolaId);
            if ($alasan !== null) {
                return "Perintah kerja {$perintahKerja->Nomor} ikut dialihkan bersama keluhan ini, tetapi: {$alasan}";
            }
        }

        return null;
    }

    /**
     * Perintah kerja keluhan ini yang belum final dan masih mengikuti antrean keluhannya.
     *
     * Dibaca lepas dari ScopeLingkup (tenancy tetap): perintah kerja yang
     * tidak terlihat oleh koordinator tetap harus ikut, bukan tertinggal.
     *
     * @return list<PerintahKerja>
     */
    private function perintahKerjaIkut(Keluhan $keluhan): array
    {
        $statusFinal = array_values(array_map(
            fn (StatusPerintahKerja $status): string => $status->value,
            array_filter(StatusPerintahKerja::cases(), fn (StatusPerintahKerja $status): bool => $status->final()),
        ));

        return array_values(PerintahKerja::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->where('KeluhanId', $keluhan->Id)
            ->whereNotIn('Status', $statusFinal)
            ->when(
                $keluhan->UnitPengelolaId === null,
                fn ($query) => $query->whereNull('UnitPengelolaId'),
                fn ($query) => $query->where('UnitPengelolaId', $keluhan->UnitPengelolaId),
            )
            ->orderBy('Nomor')
            ->orderBy('Id')
            ->get()
            ->all());
    }

    /**
     * Koordinator berlingkup unit tujuan diberi tahu bahwa keluhan masuk antreannya.
     *
     * Tanpa cadangan ke pemegang tanpa batas: yang mengalihkan sendiri sudah
     * koordinator, jadi keluhan ini tidak jatuh tanpa diketahui siapa pun.
     */
    private function beriTahuUnitTujuan(Keluhan $keluhan, string $penggunaId): void
    {
        foreach ($this->penerimaNotifikasi->koordinatorUnitPengelola($keluhan, $penggunaId, cadanganTanpaBatas: false) as $penerima) {
            $this->notifikasi->kirim(
                penggunaId: $penerima,
                jenisPeristiwa: 'Keluhan.Baru',
                isi: "Keluhan {$keluhan->Nomor} ({$keluhan->Judul}) dialihkan ke antrean unit Anda dan perlu ditinjau.",
                judul: 'Keluhan Dialihkan',
                jenisEntitas: 'Keluhan',
                entitasId: $keluhan->Id,
            );
        }
    }

    /** Nama unit dibaca lepas dari ScopeLingkup: unit tujuan boleh di luar lingkup koordinatornya. */
    private function namaUnit(?string $unitId): ?string
    {
        if ($unitId === null) {
            return null;
        }

        $nama = UnitOrganisasi::query()->withoutGlobalScope(ScopeLingkup::class)->whereKey($unitId)->value('Nama');

        return is_string($nama) ? $nama : null;
    }
}
