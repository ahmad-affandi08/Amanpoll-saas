<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use Illuminate\Support\Str;

final class BuatAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PenjagaBatasLangganan $penjagaBatas,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data, string $dibuatOleh): Aset
    {
        return $this->transaksi->jalankan(function () use ($data, $dibuatOleh): Aset {
            // Batas paket ditegakkan di dalam use-case, bukan di rute, supaya
            // jalur API dan impor massal ikut terjaga (22.05, Gate 22).
            $this->penjagaBatas->pastikanMasihMuat(KatalogFitur::BATAS_ASET);

            /** @var KategoriAset|null $kategoriAset */
            $kategoriAset = KategoriAset::query()->find($data['KategoriAsetId']);

            if ($kategoriAset) {
                $data['UmurManfaatBulan'] ??= $kategoriAset->UmurManfaatBulan;
                $data['MetodePenyusutan'] ??= $kategoriAset->MetodePenyusutanBawaan;

                if (! isset($data['NilaiResidu']) && isset($data['HargaPerolehan']) && $kategoriAset->PersentaseNilaiResidu !== null) {
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
                    'JenisPerpindahan' => JenisRiwayatLokasiAset::Registrasi->value,
                    'DipindahkanOleh' => $dibuatOleh,
                    'DipindahkanPada' => now(),
                ]);
            }

            return $aset;
        });
    }
}
