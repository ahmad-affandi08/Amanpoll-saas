<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Izin\PemeriksaLingkupBaris;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class TugaskanPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananAudit $audit,
        private readonly PemeriksaLingkupBaris $pemeriksaLingkup,
    ) {}

    /**
     * Menolak penerima yang lingkup aksesnya tidak mencakup perintah kerja ini (PRD 8.21).
     *
     * Teknisi seperti itu tidak dapat membuka tiketnya -- ScopeLingkup
     * menyembunyikannya dari daftar, detail, dan Mode Lapangan -- jadi
     * penugasannya hanya akan menggantung. Semantiknya sama persis dengan
     * ScopeLingkup; di organisasi tanpa lingkup tidak ada yang ditolak.
     * Dipakai juga FormRequest supaya penolakannya tampil sebagai galat isian.
     *
     * @param  list<string>  $penggunaIds
     */
    public function alasanTolakPenerima(PerintahKerja $perintahKerja, array $penggunaIds): ?string
    {
        $tercakup = $this->pemeriksaLingkup->penggunaYangMencakup($perintahKerja, $penggunaIds);
        $ditolak = array_values(array_diff($penggunaIds, $tercakup));

        if ($ditolak === []) {
            return null;
        }

        $nama = Pengguna::query()->whereIn('Id', $ditolak)->orderBy('Nama')->pluck('Nama')->implode(', ');
        $unitPengelola = $perintahKerja->unitPengelola()->value('Nama');
        $saran = is_string($unitPengelola)
            ? "Pilih teknisi yang lingkupnya mencakup unit pengelola {$unitPengelola}."
            : 'Pilih teknisi yang lingkupnya mencakup lokasi atau unit perintah kerja ini.';

        return "Lingkup akses {$nama} tidak mencakup perintah kerja ini, sehingga tiketnya tidak dapat dibuka. {$saran}";
    }

    /** @param list<string> $penggunaIds */
    public function jalankan(PerintahKerja $perintahKerja, array $penggunaIds, string $peranTugas, bool $ganti, string $pemberiTugasId): void
    {
        $penugasanBaru = $this->transaksi->jalankan(function () use ($perintahKerja, $penggunaIds, $peranTugas, $ganti, $pemberiTugasId): array {
            /** @var PerintahKerja $terkunci */
            $terkunci = PerintahKerja::query()->lockForUpdate()->findOrFail($perintahKerja->Id);
            $status = StatusPerintahKerja::from($terkunci->Status);
            if (in_array($status, [StatusPerintahKerja::Selesai, StatusPerintahKerja::Ditutup, StatusPerintahKerja::Dibatalkan], true)) {
                throw new AturanBisnisDilanggar('Pekerjaan yang sudah final tidak dapat ditugaskan.');
            }

            $alasanTolak = $this->alasanTolakPenerima($terkunci, $penggunaIds);

            if ($alasanTolak !== null) {
                throw new AturanBisnisDilanggar($alasanTolak);
            }

            if ($ganti) {
                $terkunci->penugasan()->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])->update([
                    'Status' => StatusPenugasanPerintahKerja::Diganti->value,
                    'SelesaiPada' => now(),
                ]);
            }

            $hasil = [];
            foreach ($penggunaIds as $penggunaId) {
                $sudahAktif = $terkunci->penugasan()
                    ->where('PenggunaId', $penggunaId)
                    ->whereIn('Status', [StatusPenugasanPerintahKerja::Ditugaskan->value, StatusPenugasanPerintahKerja::Diterima->value])
                    ->exists();
                if ($sudahAktif) {
                    continue;
                }
                $hasil[] = PenugasanPerintahKerja::create([
                    'PerintahKerjaId' => $terkunci->Id,
                    'PenggunaId' => $penggunaId,
                    'PeranTugas' => $peranTugas,
                    'DitugaskanOleh' => $pemberiTugasId,
                    'DitugaskanPada' => now(),
                    'Status' => StatusPenugasanPerintahKerja::Ditugaskan->value,
                ]);
            }

            if ($hasil === []) {
                throw new AturanBisnisDilanggar('Semua teknisi yang dipilih sudah memiliki penugasan aktif pada pekerjaan ini.');
            }

            if (in_array($status, [StatusPerintahKerja::Draf, StatusPerintahKerja::Terjadwal], true)) {
                $terkunci->Status = StatusPerintahKerja::Ditugaskan->value;
                $terkunci->Versi++;
                $terkunci->save();
                RiwayatStatusPerintahKerja::create([
                    'PerintahKerjaId' => $terkunci->Id,
                    'StatusSebelum' => $status->value,
                    'StatusSesudah' => StatusPerintahKerja::Ditugaskan->value,
                    'Catatan' => 'Teknisi ditugaskan.',
                    'DiubahOleh' => $pemberiTugasId,
                    'DiubahPada' => now(),
                ]);
            }

            $this->audit->catat('Tugaskan', 'PerintahKerja', $terkunci->Id, null, [
                'PenggunaIds' => array_map(fn (PenugasanPerintahKerja $item): string => $item->PenggunaId, $hasil),
                'GantiPenugasanAktif' => $ganti,
            ]);

            return $hasil;
        });

        foreach ($penugasanBaru as $penugasan) {
            $this->notifikasi->kirim(
                penggunaId: $penugasan->PenggunaId,
                jenisPeristiwa: 'PerintahKerja.Ditugaskan',
                isi: "Anda ditugaskan pada {$perintahKerja->Nomor} ({$perintahKerja->Judul}).",
                judul: 'Penugasan Perintah Kerja',
                jenisEntitas: 'PerintahKerja',
                entitasId: $perintahKerja->Id,
            );
        }
    }
}
