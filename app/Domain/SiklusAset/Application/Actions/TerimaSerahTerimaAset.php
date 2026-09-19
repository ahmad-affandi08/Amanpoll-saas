<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class TerimaSerahTerimaAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param array<int, array{AsetId: string, KondisiSaatDiterima: string}> $kondisiPerAset
     */
    public function jalankan(SerahTerimaAset $serahTerima, array $kondisiPerAset): SerahTerimaAset
    {
        if ($serahTerima->Status !== SerahTerimaAset::STATUS_DISERAHKAN) {
            throw new AturanBisnisDilanggar('Dokumen ini sudah diterima sebelumnya.');
        }

        $detail = $serahTerima->detailSerahTerimaAset()->get();
        if ($detail->isEmpty()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu aset sebelum menerima dokumen ini.');
        }

        $kondisiPerAsetId = collect($kondisiPerAset)->keyBy('AsetId');
        $asetIdBelumDikonfirmasi = $detail->pluck('AsetId')->diff($kondisiPerAsetId->keys())->values();
        if ($asetIdBelumDikonfirmasi->isNotEmpty()) {
            throw new AturanBisnisDilanggar('Kondisi setiap aset harus dikonfirmasi sebelum dokumen diterima.');
        }

        $this->transaksi->jalankan(function () use ($serahTerima, $detail, $kondisiPerAsetId): void {
            foreach ($detail as $baris) {
                $kondisi = $kondisiPerAsetId->get($baris->AsetId)['KondisiSaatDiterima'];
                $baris->KondisiSaatDiterima = $kondisi;
                $baris->save();

                /** @var Aset|null $aset */
                $aset = $baris->aset;
                if ($aset) {
                    $aset->Kondisi = $kondisi;
                    $aset->save();
                }
            }

            $serahTerima->Status = SerahTerimaAset::STATUS_DITERIMA;
            $serahTerima->DiterimaPada = now()->toImmutable();
            $serahTerima->save();
        });

        $this->layananAudit->catat(
            aksi: 'SerahTerimaAset.Diterima',
            jenisEntitas: 'SerahTerimaAset',
            entitasId: $serahTerima->Id,
            dataSesudah: ['KondisiPerAset' => $kondisiPerAset],
        );

        return $serahTerima->refresh();
    }
}
