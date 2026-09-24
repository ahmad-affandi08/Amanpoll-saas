<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menetapkan satu unit pengelola untuk banyak aset sekaligus (PRD 8.21).
 *
 * Satu UPDATE untuk seluruh aset yang berubah, dengan Versi ikut dinaikkan
 * supaya formulir ubah yang sedang terbuka di tempat lain mendeteksi konflik
 * seperti pada UbahAset. Aset yang unit pengelolanya sudah sama dilewati,
 * jadi mengulang permintaan yang sama tidak mengubah apa pun.
 */
final class AturUnitPengelolaAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    /**
     * @param  Collection<int, Aset>  $aset  Aset yang sudah lolos lingkup dan izin ubah.
     * @return int Jumlah aset yang berubah.
     */
    public function jalankan(Collection $aset, ?string $unitPengelolaId): int
    {
        if ($unitPengelolaId !== null) {
            $alasan = (new UnitPengelolaSah)->alasanDitolak($unitPengelolaId);

            if ($alasan !== null) {
                throw new AturanBisnisDilanggar($alasan);
            }
        }

        $berubah = $aset->filter(fn (Aset $satu): bool => $satu->UnitPengelolaId !== $unitPengelolaId);

        if ($berubah->isEmpty()) {
            return 0;
        }

        $this->transaksi->jalankan(function () use ($berubah, $unitPengelolaId): void {
            Aset::query()
                ->whereKey($berubah->modelKeys())
                ->update([
                    'UnitPengelolaId' => $unitPengelolaId,
                    'Versi' => DB::raw('Versi + 1'),
                ]);
        });

        $this->audit->catat(
            'Aset.UnitPengelolaDiubahMassal',
            'Aset',
            null,
            dataSebelum: $berubah->mapWithKeys(fn (Aset $satu): array => [$satu->Id => $satu->UnitPengelolaId])->all(),
            dataSesudah: ['UnitPengelolaId' => $unitPengelolaId, 'AsetId' => $berubah->modelKeys()],
        );

        return $berubah->count();
    }
}
