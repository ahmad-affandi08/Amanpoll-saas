<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

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

            $selesaiPada = CarbonImmutable::now();
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

    /**
     * Mencatat satu sesi waktu kerja yang sudah utuh (mulai dan selesai
     * diketahui). Dipakai oleh sinkronisasi offline: teknisi menjalankan
     * timernya di perangkat, lalu sesi lengkapnya dikirim saat kembali online
     * sehingga waktu yang tercatat adalah waktu kejadian, bukan waktu sinkron.
     */
    public function catatSelesai(
        PerintahKerja $perintahKerja,
        string $penggunaId,
        CarbonImmutable $mulaiPada,
        CarbonImmutable $selesaiPada,
        ?string $catatan,
    ): WaktuKerja {
        return $this->transaksi->jalankan(function () use ($perintahKerja, $penggunaId, $mulaiPada, $selesaiPada, $catatan): WaktuKerja {
            $status = StatusPerintahKerja::from($perintahKerja->Status);
            if (! $status->dapatMencatatOperasional()) {
                throw new AturanBisnisDilanggar('Waktu kerja hanya dapat dicatat pada pekerjaan yang sudah diterima dan belum selesai.');
            }
            if ($selesaiPada->lessThanOrEqualTo($mulaiPada)) {
                throw new AturanBisnisDilanggar('Waktu selesai harus setelah waktu mulai.');
            }
            if ($selesaiPada->isFuture()) {
                throw new AturanBisnisDilanggar('Sesi waktu kerja tidak boleh berakhir di masa depan.');
            }

            $bertumpuk = WaktuKerja::query()
                ->where('PenggunaId', $penggunaId)
                ->where('MulaiPada', '<', $selesaiPada)
                ->where(fn ($query) => $query->whereNull('SelesaiPada')->orWhere('SelesaiPada', '>', $mulaiPada))
                ->exists();
            if ($bertumpuk) {
                throw new AturanBisnisDilanggar('Sesi waktu kerja ini bertumpuk dengan sesi lain milik teknisi yang sama.');
            }

            $waktuKerja = WaktuKerja::create([
                'PerintahKerjaId' => $perintahKerja->Id,
                'PenggunaId' => $penggunaId,
                'MulaiPada' => $mulaiPada,
                'SelesaiPada' => $selesaiPada,
                'DurasiMenit' => max(0, (int) floor($mulaiPada->diffInSeconds($selesaiPada) / 60)),
                'JenisWaktu' => 'Kerja',
                'Catatan' => $catatan,
            ]);

            $this->audit->catat('WaktuKerja.CatatSelesai', 'PerintahKerja', $perintahKerja->Id, null, [
                'WaktuKerjaId' => $waktuKerja->Id,
                'DurasiMenit' => $waktuKerja->DurasiMenit,
            ]);

            return $waktuKerja;
        });
    }
}
