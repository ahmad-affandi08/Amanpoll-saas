<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class KelolaWaktuHentiAset
{
    public function __construct(private readonly TransaksiDatabase $transaksi, private readonly LayananAudit $audit) {}

    public function jalankan(PerintahKerja $perintahKerja, string $asetId, string $aksi, string $jenis, string $alasan): WaktuHentiAset
    {
        return $this->transaksi->jalankan(function () use ($perintahKerja, $asetId, $aksi, $jenis, $alasan): WaktuHentiAset {
            if (! StatusPerintahKerja::from($perintahKerja->Status)->dapatMencatatOperasional()) {
                throw new AturanBisnisDilanggar('Downtime hanya dapat dicatat pada pekerjaan aktif.');
            }
            if (! $perintahKerja->asetPekerjaan()->where('AsetId', $asetId)->exists()) {
                throw new AturanBisnisDilanggar('Aset tersebut tidak terdaftar pada perintah kerja ini.');
            }

            /** @var WaktuHentiAset|null $aktif */
            $aktif = WaktuHentiAset::query()->where('AsetId', $asetId)->whereNull('SelesaiPada')->lockForUpdate()->first();

            if ($aksi === 'Mulai') {
                if ($aktif !== null) {
                    throw new AturanBisnisDilanggar('Aset masih memiliki downtime aktif sehingga interval tidak boleh tumpang tindih.');
                }

                $downtime = WaktuHentiAset::create([
                    'AsetId' => $asetId,
                    'PerintahKerjaId' => $perintahKerja->Id,
                    'MulaiPada' => now(),
                    'Jenis' => $jenis,
                    'Alasan' => $alasan,
                ]);
                $this->audit->catat('Downtime.Mulai', 'PerintahKerja', $perintahKerja->Id, null, ['WaktuHentiAsetId' => $downtime->Id]);

                return $downtime;
            }

            if ($aktif === null || $aktif->PerintahKerjaId !== $perintahKerja->Id) {
                throw new AturanBisnisDilanggar('Tidak ada downtime aktif untuk aset pada pekerjaan ini.');
            }

            $selesaiPada = now();
            $aktif->SelesaiPada = $selesaiPada;
            $aktif->DurasiMenit = max(0, (int) floor($aktif->MulaiPada->diffInSeconds($selesaiPada) / 60));
            $aktif->Alasan = $alasan;
            $aktif->save();
            $this->audit->catat('Downtime.Selesai', 'PerintahKerja', $perintahKerja->Id, null, [
                'WaktuHentiAsetId' => $aktif->Id,
                'DurasiMenit' => $aktif->DurasiMenit,
            ]);

            return $aktif;
        });
    }
}
