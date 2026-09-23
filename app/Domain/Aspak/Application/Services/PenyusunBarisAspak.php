<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aspak\Domain\Enums\KondisiAspak;
use App\Domain\Aspak\Infrastructure\Persistence\Models\PemetaanAspak;
use Illuminate\Database\Eloquent\Model;

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

        $lokasi = $this->relasi($aset, 'lokasi');
        $model = $this->relasi($aset, 'modelAset');
        $merek = $model === null ? null : $this->relasi($model, 'merek');

        return [
            'KodeAlkes' => $alkes['Kode'] ?? '',
            'NamaAlkes' => $alkes['Nama'] ?? '',
            'KodeRuang' => $this->atribut($lokasi, 'KodeRuangAspak'),
            'NamaRuang' => $this->atribut($lokasi, 'Nama'),
            'KodeAset' => (string) $aset->KodeAset,
            'NamaAset' => (string) $aset->Nama,
            'Merek' => $this->atribut($merek, 'Nama'),
            'Tipe' => $this->atribut($model, 'Nama'),
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

    /**
     * Relasi terkait, atau null bila belum terisi.
     *
     * LokasiId, ModelAsetId, dan MerekId boleh kosong di basis data, tetapi
     * tipe relasi belongsTo tidak menyatakannya. Diambil lewat tipe Model dasar
     * supaya nullnya tidak hilang: aset tanpa lokasi harus menghasilkan sel
     * kosong, bukan peringatan "property on null" di tengah ekspor.
     */
    private function relasi(Model $induk, string $nama): ?Model
    {
        $terkait = $induk->getRelationValue($nama);

        return $terkait instanceof Model ? $terkait : null;
    }

    private function atribut(?Model $model, string $atribut): string
    {
        if ($model === null) {
            return '';
        }

        $nilai = $model->getAttribute($atribut);

        return is_scalar($nilai) ? (string) $nilai : '';
    }

    /** Aset tanpa pemetaan tidak dapat dilaporkan; ASPAK menolak baris tanpa kode alat. */
    public function terpetakan(Aset $aset): bool
    {
        $this->muatPemetaan();

        return $this->alkesUntuk($aset) !== [];
    }

    /** @return array<string, string> */
    private function alkesUntuk(Aset $aset): array
    {
        // Model lebih spesifik daripada kategori, jadi ia yang menang.
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
