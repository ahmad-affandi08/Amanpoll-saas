<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Support\Str;

final class BuatAset
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data, string $dibuatOleh): Aset
    {
        return $this->transaksi->jalankan(function () use ($data, $dibuatOleh): Aset {
            /** @var KategoriAset|null $kategoriAset */
            $kategoriAset = KategoriAset::query()->find($data['KategoriAsetId']);

            if ($kategoriAset) {
                $data['UmurManfaatBulan'] ??= $kategoriAset->UmurManfaatBulan;
                $data['MetodePenyusutan'] ??= $kategoriAset->MetodePenyusutanBawaan;

                if (!isset($data['NilaiResidu']) && isset($data['HargaPerolehan']) && $kategoriAset->PersentaseNilaiResidu !== null) {
                    $data['NilaiResidu'] = round((float) $data['HargaPerolehan'] * (float) $kategoriAset->PersentaseNilaiResidu / 100, 2);
                }
            }

            $data['KodeQr'] = (string) Str::ulid();
            $data['DibuatOleh'] = $dibuatOleh;

            /** @var Aset $aset */
            $aset = Aset::create($data);

            if ($aset->LokasiId !== null) {
                RiwayatLokasiAset::create([
                    'AsetId' => $aset->Id,
                    'LokasiAsalId' => null,
                    'LokasiTujuanId' => $aset->LokasiId,
                    'JenisPerpindahan' => RiwayatLokasiAset::JENIS_REGISTRASI,
                    'DipindahkanOleh' => $dibuatOleh,
                    'DipindahkanPada' => now(),
                ]);
            }

            return $aset;
        });
    }
}
