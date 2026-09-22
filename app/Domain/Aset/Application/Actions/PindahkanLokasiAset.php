<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/** Satu-satunya jalur yang boleh mengubah Aset.LokasiId -- UbahAset sengaja mengabaikan field LokasiId. */
final class PindahkanLokasiAset
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    public function jalankan(Aset $aset, ?string $lokasiTujuanId, ?string $alasan, string $dipindahkanOleh): Aset
    {
        return $this->transaksi->jalankan(function () use ($aset, $lokasiTujuanId, $alasan, $dipindahkanOleh): Aset {
            $lokasiAsalId = $aset->LokasiId;

            RiwayatLokasiAset::create([
                'AsetId' => $aset->Id,
                'LokasiAsalId' => $lokasiAsalId,
                'LokasiTujuanId' => $lokasiTujuanId,
                'JenisPerpindahan' => JenisRiwayatLokasiAset::Manual->value,
                'Alasan' => $alasan,
                'DipindahkanOleh' => $dipindahkanOleh,
                'DipindahkanPada' => now(),
            ]);

            $aset->LokasiId = $lokasiTujuanId;
            $aset->save();

            return $aset;
        });
    }
}
