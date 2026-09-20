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

final class ResponsPenugasanPerintahKerja
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananNotifikasi $notifikasi,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(PenugasanPerintahKerja $penugasan, string $respons, ?string $catatan, string $penggunaId): void
    {
        if ($penugasan->PenggunaId !== $penggunaId) {
            throw new AturanBisnisDilanggar('Penugasan hanya dapat direspons oleh teknisi yang ditugaskan.');
        }

        $this->transaksi->jalankan(function () use ($penugasan, $respons, $catatan): void {
            /** @var PenugasanPerintahKerja $terkunci */
            $terkunci = PenugasanPerintahKerja::query()->lockForUpdate()->findOrFail($penugasan->Id);
            if ($terkunci->Status !== StatusPenugasanPerintahKerja::Ditugaskan->value) {
                throw new AturanBisnisDilanggar('Penugasan ini sudah direspons atau sudah tidak aktif.');
            }

            /** @var PerintahKerja $perintahKerja */
            $perintahKerja = PerintahKerja::query()->lockForUpdate()->findOrFail($terkunci->PerintahKerjaId);
            $sebelum = $terkunci->toArray();
            $terkunci->Status = $respons === 'Terima'
                ? StatusPenugasanPerintahKerja::Diterima->value
                : StatusPenugasanPerintahKerja::Ditolak->value;
            $terkunci->DiterimaPada = $respons === 'Terima' ? now() : null;
            $terkunci->SelesaiPada = $respons === 'Tolak' ? now() : null;
            $terkunci->save();

            if ($respons === 'Terima' && $perintahKerja->Status === StatusPerintahKerja::Ditugaskan->value) {
                $perintahKerja->Status = StatusPerintahKerja::Diterima->value;
                $perintahKerja->DiterimaPada ??= now();
                $perintahKerja->Versi++;
                $perintahKerja->save();
                RiwayatStatusPerintahKerja::create([
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'StatusSebelum' => StatusPerintahKerja::Ditugaskan->value,
                    'StatusSesudah' => StatusPerintahKerja::Diterima->value,
                    'Catatan' => 'Penugasan diterima oleh teknisi.',
                    'DiubahOleh' => $terkunci->PenggunaId,
                    'DiubahPada' => now(),
                ]);
            }

            $this->audit->catat('ResponsPenugasan', 'PenugasanPerintahKerja', $terkunci->Id, $sebelum, [
                ...$terkunci->toArray(),
                'Catatan' => $catatan,
            ]);
        });

        if ($penugasan->DitugaskanOleh !== null) {
            $this->notifikasi->kirim(
                penggunaId: $penugasan->DitugaskanOleh,
                jenisPeristiwa: "PerintahKerja.Penugasan{$respons}",
                isi: "Penugasan {$penugasan->perintahKerja->Nomor} direspons: {$respons}.".($catatan ? " {$catatan}" : ''),
                judul: 'Respons Penugasan',
                jenisEntitas: 'PerintahKerja',
                entitasId: $penugasan->PerintahKerjaId,
            );
        }
    }
}
