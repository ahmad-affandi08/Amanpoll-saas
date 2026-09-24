<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\PemeriksaLingkupBaris;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Memindahkan perintah kerja ke unit pengelola lain (PRD 8.21), mis. tiket
 * yang turunannya keliru masuk antrian IPSRS padahal milik IT.
 *
 * Ditolak selama masih ada penugasan aktif kepada teknisi yang lingkupnya
 * tidak mencakup tiket sesudah dialihkan: tiket itu akan lenyap dari layar
 * teknisinya, sementara penugasannya tetap menggantung atas namanya.
 * Koordinator mengganti penugasannya lebih dulu. Penugasan kepada teknisi
 * yang tetap dapat melihat tiketnya (mis. lingkupnya mencakup ruangannya)
 * tidak menghalangi.
 *
 * `Versi` sengaja tidak dinaikkan: status tidak berubah, dan menaikkannya
 * membuat pembaruan status teknisi yang sedang offline tertolak sebagai
 * konflik tanpa alasan.
 */
final class AlihkanUnitPengelolaPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly PemeriksaLingkupBaris $pemeriksaLingkup,
    ) {}

    public function jalankan(PerintahKerja $perintahKerja, ?string $unitPengelolaId, string $alasan): PerintahKerja
    {
        return $this->transaksi->jalankan(function () use ($perintahKerja, $unitPengelolaId, $alasan): PerintahKerja {
            $terkunci = PerintahKerja::query()->lockForUpdate()->findOrFail($perintahKerja->Id);
            $tujuan = filled($unitPengelolaId) ? $unitPengelolaId : null;

            $alasanUnit = $tujuan === null ? null : (new UnitPengelolaSah)->alasanDitolak($tujuan);
            $alasanTolak = $alasanUnit ?? $this->alasanDitolak($terkunci, $tujuan);

            if ($alasanTolak !== null) {
                throw new AturanBisnisDilanggar($alasanTolak);
            }

            $sebelum = $terkunci->UnitPengelolaId;
            $terkunci->UnitPengelolaId = $tujuan;
            $terkunci->save();

            $this->audit->catat('AlihkanUnitPengelola', 'PerintahKerja', $terkunci->Id,
                ['UnitPengelolaId' => $sebelum],
                ['UnitPengelolaId' => $tujuan, 'Alasan' => $alasan],
            );

            return $terkunci;
        });
    }

    /**
     * Alasan pengalihan ditolak, atau null bila boleh. Tidak memeriksa sahnya
     * unit tujuan (itu tugas UnitPengelolaSah); dipakai juga FormRequest supaya
     * penolakannya tampil di isian.
     */
    public function alasanDitolak(PerintahKerja $perintahKerja, ?string $unitPengelolaId): ?string
    {
        $tujuan = filled($unitPengelolaId) ? $unitPengelolaId : null;

        if (StatusPerintahKerja::from($perintahKerja->Status)->final()) {
            return 'Perintah kerja yang sudah ditutup atau dibatalkan tidak dapat dialihkan.';
        }

        if ($perintahKerja->UnitPengelolaId === $tujuan) {
            return 'Perintah kerja ini sudah berada di unit pengelola tersebut.';
        }

        $penggunaAktif = $perintahKerja->penugasan()
            ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])
            ->pluck('PenggunaId')
            ->unique()
            ->values()
            ->all();

        if ($penggunaAktif === []) {
            return null;
        }

        $sesudah = $perintahKerja->replicate();
        $sesudah->UnitPengelolaId = $tujuan;
        $ditolak = array_values(array_diff($penggunaAktif, $this->pemeriksaLingkup->penggunaYangMencakup($sesudah, $penggunaAktif)));

        if ($ditolak === []) {
            return null;
        }

        $nama = Pengguna::query()->whereIn('Id', $ditolak)->orderBy('Nama')->pluck('Nama')->implode(', ');

        return "Perintah kerja masih ditugaskan kepada {$nama}, yang lingkup aksesnya tidak mencakup unit pengelola tujuan sehingga tiketnya akan hilang dari layarnya. Ganti penugasannya lebih dulu, atau pilih unit pengelola lain.";
    }
}
