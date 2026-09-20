<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaWaktuKerja
{
    public function __construct(private readonly TransaksiDatabase $transaksi, private readonly LayananAudit $audit) {}

    public function jalankan(PerintahKerja $perintahKerja, string $penggunaId, string $aksi, ?string $catatan): WaktuKerja
    {
        return $this->transaksi->jalankan(function () use ($perintahKerja, $penggunaId, $aksi, $catatan): WaktuKerja {
            $status = StatusPerintahKerja::from($perintahKerja->Status);
            if (! $status->dapatMencatatOperasional()) {
                throw new AturanBisnisDilanggar('Waktu kerja hanya dapat dicatat pada pekerjaan yang sudah diterima dan belum selesai.');
            }

            /** @var WaktuKerja|null $aktif */
            $aktif = WaktuKerja::query()
                ->where('PenggunaId', $penggunaId)
                ->whereNull('SelesaiPada')
                ->lockForUpdate()
                ->first();

            if (in_array($aksi, ['Mulai', 'Lanjut'], true)) {
                if ($aktif !== null) {
                    throw new AturanBisnisDilanggar('Teknisi masih memiliki sesi waktu kerja aktif. Akhiri atau jeda sesi tersebut terlebih dahulu.');
                }

                $waktuKerja = WaktuKerja::create([
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'PenggunaId' => $penggunaId,
                    'MulaiPada' => now(),
                    'JenisWaktu' => $aksi === 'Lanjut' ? 'Lanjutan' : 'Kerja',
                    'Catatan' => $catatan,
                ]);
                $this->audit->catat("WaktuKerja.{$aksi}", 'PerintahKerja', $perintahKerja->Id, null, ['WaktuKerjaId' => $waktuKerja->Id]);

                return $waktuKerja;
            }

            if ($aktif === null || $aktif->PerintahKerjaId !== $perintahKerja->Id) {
                throw new AturanBisnisDilanggar('Tidak ada sesi waktu kerja aktif untuk pekerjaan ini.');
            }

            $selesaiPada = now();
            $aktif->SelesaiPada = $selesaiPada;
            $aktif->DurasiMenit = max(0, (int) floor($aktif->MulaiPada->diffInSeconds($selesaiPada) / 60));
            $aktif->Catatan = $catatan ?: $aktif->Catatan;
            $aktif->save();
            $this->audit->catat("WaktuKerja.{$aksi}", 'PerintahKerja', $perintahKerja->Id, null, [
                'WaktuKerjaId' => $aktif->Id,
                'DurasiMenit' => $aktif->DurasiMenit,
            ]);

            return $aktif;
        });
    }
}
