<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\ScopeLingkup;
use App\Domain\Pemeliharaan\Application\Services\AturanKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Application\Services\PemberiTahuKonfirmasiPenerima;
use App\Domain\Pemeliharaan\Application\Services\PenyelarasKeluhanTerkonfirmasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\VersiDataBerubah;

/**
 * Satu-satunya jalan mengubah status perintah kerja, dari dasbor, antrean offline,
 * maupun Mode Lapangan.
 *
 * Konfirmasi penerima (PRD 8.22): teknisi selalu bisa menyerahkan pekerjaan ke
 * Menunggu Verifikasi (pelapor keluhan asal langsung diminta mengonfirmasi);
 * verifikasi ke Selesai ditolak tanpa konfirmasi "Diterima" bila organisasi
 * mewajibkannya; kembali ke Dikerjakan dari Menunggu Verifikasi/Selesai/Ditutup
 * mengakhiri siklus penyelesaian sehingga konfirmasinya dicabut.
 */
final class UbahStatusPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
        private readonly AturanKonfirmasiPenerima $konfirmasiPenerima,
        private readonly PenyelarasKeluhanTerkonfirmasi $penyelarasKeluhan,
        private readonly PemberiTahuKonfirmasiPenerima $pemberiTahu,
    ) {}

    public function jalankan(
        PerintahKerja $perintahKerja,
        StatusPerintahKerja $tujuan,
        ?string $catatan,
        ?string $ringkasan,
        int $versi,
        string $penggunaId,
    ): PerintahKerja {
        $hasil = $this->transaksi->jalankan(function () use ($perintahKerja, $tujuan, $catatan, $ringkasan, $versi, $penggunaId): PerintahKerja {
            // Tanpa ScopeLingkup: pemanggil sudah memegang tiket ini dan otorisasinya dijaga
            // policy. Pelapor yang menjawab "masih bermasalah" (PRD 8.22) mengembalikan tiket
            // yang tidak selalu masuk lingkupnya sendiri.
            /** @var PerintahKerja $terkunci */
            $terkunci = PerintahKerja::query()->withoutGlobalScope(ScopeLingkup::class)->lockForUpdate()->findOrFail($perintahKerja->Id);
            if ($terkunci->Versi !== $versi) {
                throw new VersiDataBerubah('Perintah kerja telah berubah. Muat ulang halaman sebelum mencoba lagi.');
            }

            $asal = StatusPerintahKerja::from($terkunci->Status);
            if (! $asal->dapatBeralihKe($tujuan)) {
                throw new AturanBisnisDilanggar("Status {$asal->value} tidak dapat diubah menjadi {$tujuan->value}.");
            }

            if ($tujuan === StatusPerintahKerja::Terjadwal && $terkunci->DijadwalkanMulaiPada === null) {
                throw new AturanBisnisDilanggar('Jadwal mulai wajib diisi sebelum menjadwalkan pekerjaan.');
            }
            if ($tujuan === StatusPerintahKerja::Ditugaskan && ! $terkunci->penugasan()->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])->exists()) {
                throw new AturanBisnisDilanggar('Tambahkan minimal satu teknisi aktif sebelum mengubah status menjadi Ditugaskan.');
            }
            if (in_array($tujuan, [StatusPerintahKerja::MenungguVerifikasi, StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup], true)) {
                $this->pastikanTidakAdaAktivitasTerbuka($terkunci);
            }
            if ($tujuan === StatusPerintahKerja::MenungguVerifikasi && blank($ringkasan)) {
                throw new AturanBisnisDilanggar('Ringkasan penyelesaian wajib diisi sebelum verifikasi.');
            }
            if ($tujuan === StatusPerintahKerja::Selesai && $asal === StatusPerintahKerja::MenungguVerifikasi) {
                $this->konfirmasiPenerima->pastikanBolehDiverifikasi($terkunci);
            }

            $sebelum = $terkunci->toArray();
            $sekarang = now();
            $terkunci->Status = $tujuan->value;
            $terkunci->Versi++;

            if ($tujuan === StatusPerintahKerja::Diterima && $terkunci->DiterimaPada === null) {
                $terkunci->DiterimaPada = $sekarang;
            }
            if ($tujuan === StatusPerintahKerja::Dikerjakan) {
                if (in_array($asal, [StatusPerintahKerja::MenungguVerifikasi, StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup], true)) {
                    $this->konfirmasiPenerima->cabut($terkunci);
                }
                $terkunci->DimulaiPada ??= $sekarang;
                if (in_array($asal, [StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup], true)) {
                    $terkunci->DiselesaikanPada = null;
                    $terkunci->DitutupPada = null;
                    $terkunci->PersentaseSelesai = 0;
                }
            }
            if ($tujuan === StatusPerintahKerja::MenungguVerifikasi) {
                $terkunci->RingkasanPenyelesaian = $ringkasan;
                $terkunci->PersentaseSelesai = 100;
            }
            if ($tujuan === StatusPerintahKerja::Selesai) {
                $terkunci->DiselesaikanPada = $sekarang;
                $terkunci->PersentaseSelesai = 100;
                $terkunci->penugasan()->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])->update([
                    'Status' => StatusPenugasanPerintahKerja::Selesai->value,
                    'SelesaiPada' => $sekarang,
                ]);
            }
            if ($tujuan === StatusPerintahKerja::Ditutup) {
                $terkunci->DitutupPada = $sekarang;
            }
            if ($tujuan === StatusPerintahKerja::Dibatalkan) {
                $terkunci->penugasan()->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])->update([
                    'Status' => StatusPenugasanPerintahKerja::Diganti->value,
                    'SelesaiPada' => $sekarang,
                ]);
            }

            $terkunci->save();
            $this->sinkronkanJadwalPreventif($terkunci, $tujuan);
            RiwayatStatusPerintahKerja::create([
                'PerintahKerjaId' => $terkunci->Id,
                'StatusSebelum' => $asal->value,
                'StatusSesudah' => $tujuan->value,
                'Catatan' => $catatan,
                'DiubahOleh' => $penggunaId,
                'DiubahPada' => $sekarang,
            ]);
            $this->audit->catat('UbahStatus', 'PerintahKerja', $terkunci->Id, $sebelum, $terkunci->toArray());

            if ($tujuan === StatusPerintahKerja::Selesai) {
                $this->penyelarasKeluhan->setelahDiverifikasi($terkunci, $penggunaId);
            }

            return $terkunci;
        });

        if ($tujuan === StatusPerintahKerja::MenungguVerifikasi) {
            $this->pemberiTahu->mintaPelapor($hasil);
        }

        return $hasil;
    }

    /**
     * Menyelaraskan jadwal preventif dengan perintah kerja yang dihasilkannya.
     *
     * Jadwal dibuat "Terjadwal" oleh penjadwal dan sebelumnya tidak pernah
     * disentuh lagi. Laporan preventif menghitung keterlambatan dari jadwal
     * yang masih Terjadwal dan kepatuhan dari jadwal yang Selesai, jadi
     * pekerjaan yang sudah tuntas tetap tampil terlambat dan kepatuhan
     * preventif selalu nol.
     *
     * Diturunkan dari status tujuan, bukan hanya pada penutupan: perintah kerja
     * yang dibuka kembali mengembalikan jadwalnya ke Terjadwal, dan yang
     * dibatalkan berhenti tampil terlambat tanpa ikut terhitung patuh.
     */
    private function sinkronkanJadwalPreventif(PerintahKerja $perintahKerja, StatusPerintahKerja $tujuan): void
    {
        $statusJadwal = match ($tujuan) {
            StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup => 'Selesai',
            StatusPerintahKerja::Dibatalkan => 'Dibatalkan',
            default => 'Terjadwal',
        };

        JadwalPemeliharaan::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->where('Status', '!=', $statusJadwal)
            ->update(['Status' => $statusJadwal]);
    }

    private function pastikanTidakAdaAktivitasTerbuka(PerintahKerja $perintahKerja): void
    {
        if ($perintahKerja->waktuKerja()->whereNull('SelesaiPada')->exists()) {
            throw new AturanBisnisDilanggar('Akhiri semua sesi waktu kerja sebelum menyelesaikan pekerjaan.');
        }
        if ($perintahKerja->waktuHenti()->whereNull('SelesaiPada')->exists()) {
            throw new AturanBisnisDilanggar('Akhiri semua downtime aset sebelum menyelesaikan pekerjaan.');
        }
    }
}
