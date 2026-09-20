<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\VersiDataBerubah;

final class UbahStatusPerintahKerja
{
    public function __construct(private readonly TransaksiDatabase $transaksi, private readonly LayananAudit $audit) {}

    public function jalankan(
        PerintahKerja $perintahKerja,
        StatusPerintahKerja $tujuan,
        ?string $catatan,
        ?string $ringkasan,
        int $versi,
        string $penggunaId,
    ): PerintahKerja {
        return $this->transaksi->jalankan(function () use ($perintahKerja, $tujuan, $catatan, $ringkasan, $versi, $penggunaId): PerintahKerja {
            /** @var PerintahKerja $terkunci */
            $terkunci = PerintahKerja::query()->lockForUpdate()->findOrFail($perintahKerja->Id);
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

            $sebelum = $terkunci->toArray();
            $sekarang = now();
            $terkunci->Status = $tujuan->value;
            $terkunci->Versi++;

            if ($tujuan === StatusPerintahKerja::Diterima && $terkunci->DiterimaPada === null) {
                $terkunci->DiterimaPada = $sekarang;
            }
            if ($tujuan === StatusPerintahKerja::Dikerjakan) {
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
            RiwayatStatusPerintahKerja::create([
                'PerintahKerjaId' => $terkunci->Id,
                'StatusSebelum' => $asal->value,
                'StatusSesudah' => $tujuan->value,
                'Catatan' => $catatan,
                'DiubahOleh' => $penggunaId,
                'DiubahPada' => $sekarang,
            ]);
            $this->audit->catat('UbahStatus', 'PerintahKerja', $terkunci->Id, $sebelum, $terkunci->toArray());

            return $terkunci;
        });
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
