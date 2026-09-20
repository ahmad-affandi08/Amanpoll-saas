<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Notifikasi\Application\Services\LayananNotifikasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class TugaskanPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananAudit $audit,
    ) {}

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
