<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class HapusDetailPenghapusanAset
{
    public function jalankan(PengajuanPenghapusanAset $pengajuan, DetailPenghapusanAset $detail): void
    {
        if ($pengajuan->Status !== PengajuanPenghapusanAset::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Detail aset hanya boleh dihapus selagi pengajuan masih berupa draft.');
        }

        $detail->delete();
    }
}
