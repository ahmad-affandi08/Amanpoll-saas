<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Keputusan per aset di dalam satu permintaan mutasi.
 *
 * Persetujuan permintaan hanya mengotorisasi perpindahannya secara keseluruhan.
 * Pemegang aset masih perlu menolak satuan yang tidak boleh ikut berpindah --
 * misalnya alat yang sedang dipakai -- tanpa membatalkan seluruh permintaan.
 */
final class PutuskanDetailMutasiAset
{
    /** Keputusan hanya masuk akal selagi permintaan belum dieksekusi. */
    private const STATUS_PERMINTAAN_TERBUKA = [
        StatusPermintaanMutasiAset::Menunggu->value,
        StatusPermintaanMutasiAset::Disetujui->value,
    ];

    /** Baris yang sudah dieksekusi atau dibatalkan tidak dapat diputuskan lagi. */
    private const STATUS_DETAIL_TERBUKA = [
        StatusDetailMutasiAset::Menunggu->value,
        StatusDetailMutasiAset::Disetujui->value,
        StatusDetailMutasiAset::Ditolak->value,
    ];

    public function __construct(private readonly LayananAudit $layananAudit) {}

    public function jalankan(
        DetailMutasiAset $detail,
        bool $disetujui,
        string $diputuskanOleh,
        ?string $alasanPenolakan = null,
    ): DetailMutasiAset {
        /** @var PermintaanMutasiAset|null $permintaan */
        $permintaan = $detail->permintaanMutasiAset;

        if ($permintaan === null) {
            throw new AturanBisnisDilanggar('Permintaan mutasi untuk aset ini tidak ditemukan.');
        }

        if (! in_array($permintaan->Status, self::STATUS_PERMINTAAN_TERBUKA, true)) {
            throw new AturanBisnisDilanggar('Keputusan per aset hanya bisa diberikan selagi permintaan belum dieksekusi.');
        }

        if (! in_array($detail->Status, self::STATUS_DETAIL_TERBUKA, true)) {
            throw new AturanBisnisDilanggar('Aset ini sudah dieksekusi atau dibatalkan, keputusannya tidak bisa diubah.');
        }

        $alasan = $alasanPenolakan === null ? null : trim($alasanPenolakan);

        if (! $disetujui && ($alasan === null || $alasan === '')) {
            throw new AturanBisnisDilanggar('Penolakan aset harus disertai alasan.');
        }

        $statusSebelum = $detail->Status;

        $detail->Status = $disetujui
            ? StatusDetailMutasiAset::Disetujui->value
            : StatusDetailMutasiAset::Ditolak->value;
        $detail->AlasanPenolakan = $disetujui ? null : $alasan;
        $detail->DiputuskanOleh = $diputuskanOleh;
        $detail->DiputuskanPada = now()->toImmutable();
        $detail->save();

        $this->layananAudit->catat(
            aksi: $disetujui ? 'DetailMutasiAset.Disetujui' : 'DetailMutasiAset.Ditolak',
            jenisEntitas: 'DetailMutasiAset',
            entitasId: $detail->Id,
            dataSebelum: ['Status' => $statusSebelum],
            dataSesudah: ['Status' => $detail->Status, 'AlasanPenolakan' => $detail->AlasanPenolakan],
        );

        return $detail;
    }
}
