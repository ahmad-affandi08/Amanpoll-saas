<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aspak\Domain\Enums\KondisiAspak;
use App\Domain\Aspak\Infrastructure\Persistence\Models\PemetaanAspak;
use App\Shared\Infrastructure\Persistence\BacaRelasi;

/**
 * Mengubah satu aset menjadi nilai per ruas ASPAK.
 *
 * Pemetaan dimuat sekali di muka lalu dipakai ulang, karena ekspor berjalan
 * per potongan ratusan baris dan mencari pemetaan per aset akan memukul basis
 * data sekali untuk tiap barisnya.
 */
final class PenyusunBarisAspak
{
    /** @var array<string, array{Kode: string, Nama: string}> */
    private array $perModel = [];

    /** @var array<string, array{Kode: string, Nama: string}> */
    private array $perKategori = [];

    private bool $dimuat = false;

    /** @return array<string, string> */
    public function untuk(Aset $aset): array
    {
        $this->muatPemetaan();

        $alkes = $this->alkesUntuk($aset);
        $kalibrasi = $aset->pelaksanaanKalibrasi->sortByDesc('TanggalKalibrasi')->first();

        $lokasi = BacaRelasi::model($aset, 'lokasi');
        $model = BacaRelasi::model($aset, 'modelAset');
        $merek = $model === null ? null : BacaRelasi::model($model, 'merek');

        return [
            'KodeAlkes' => $alkes['Kode'] ?? '',
            'NamaAlkes' => $alkes['Nama'] ?? '',
            'KodeRuang' => BacaRelasi::teks($lokasi, 'KodeRuangAspak'),
            'NamaRuang' => BacaRelasi::teks($lokasi, 'Nama'),
            'KodeAset' => (string) $aset->KodeAset,
            'NamaAset' => (string) $aset->Nama,
            'Merek' => BacaRelasi::teks($merek, 'Nama'),
            'Tipe' => BacaRelasi::teks($model, 'Nama'),
            'NomorSeri' => (string) ($aset->NomorSeri ?? ''),
            'TahunPerolehan' => $aset->TanggalPerolehan?->format('Y') ?? '',
            'Kondisi' => KondisiAspak::dariKondisiAset($aset->Kondisi)->value,
            // ASPAK mencatat per unit alat, jadi satu baris selalu satu buah.
            'Jumlah' => '1',
            'HargaPerolehan' => $aset->HargaPerolehan === null ? '' : (string) $aset->HargaPerolehan,
            'SumberDana' => (string) ($aset->SumberDana ?? ''),
            'TanggalKalibrasiTerakhir' => $kalibrasi?->TanggalKalibrasi?->format('Y-m-d') ?? '',
            'BerlakuKalibrasiSampai' => $kalibrasi?->TanggalBerlakuSampai?->format('Y-m-d') ?? '',
        ];
    }

    /** Aset tanpa pemetaan tidak dapat dilaporkan; ASPAK menolak baris tanpa kode alat. */
    public function terpetakan(Aset $aset): bool
    {
        $this->muatPemetaan();

        return $this->alkesUntuk($aset) !== [];
    }

    /**
     * Nomenklatur yang berlaku bagi satu aset.
     *
     * Urutannya dari yang paling khusus: nomenklatur yang dipilih di aset itu
     * sendiri, lalu pemetaan modelnya, lalu pemetaan kategorinya. Aset yang
     * menyimpang dari modelnya karena itu tidak perlu memaksa seluruh model
     * dipetakan ulang.
     *
     * @return array<string, string>
     */
    private function alkesUntuk(Aset $aset): array
    {
        if ($aset->AlkesAspakId !== null) {
            $sendiri = BacaRelasi::model($aset, 'alkesAspak');

            if ($sendiri !== null) {
                return [
                    'Kode' => BacaRelasi::teks($sendiri, 'Kode'),
                    'Nama' => BacaRelasi::teks($sendiri, 'Nama'),
                ];
            }
        }

        if ($aset->ModelAsetId !== null && isset($this->perModel[$aset->ModelAsetId])) {
            return $this->perModel[$aset->ModelAsetId];
        }

        return $this->perKategori[$aset->KategoriAsetId] ?? [];
    }

    private function muatPemetaan(): void
    {
        if ($this->dimuat) {
            return;
        }

        foreach (PemetaanAspak::query()->with('alkes')->get() as $satu) {
            $alkes = $satu->alkes;

            if ($alkes === null) {
                continue;
            }

            $nilai = ['Kode' => (string) $alkes->Kode, 'Nama' => (string) $alkes->Nama];

            if ($satu->ModelAsetId !== null) {
                $this->perModel[$satu->ModelAsetId] = $nilai;
            }

            if ($satu->KategoriAsetId !== null) {
                $this->perKategori[$satu->KategoriAsetId] = $nilai;
            }
        }

        $this->dimuat = true;
    }
}
