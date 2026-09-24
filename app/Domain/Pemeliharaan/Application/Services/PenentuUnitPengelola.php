<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Izin\ScopeLingkup;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;

/**
 * Menurunkan unit pengelola sebuah tiket dari data yang sudah ada (PRD 8.21).
 *
 * Dipanggil Action pembuat tiket, bukan controller, supaya dasbor, Mode
 * Lapangan, dan antrian offline mendapat hasil yang sama untuk masukan yang
 * sama. Karena itu aset dibaca lepas dari ScopeLingkup: hasilnya tidak boleh
 * bergantung pada siapa yang kebetulan membuat tiketnya. Tenancy tetap
 * berlaku lewat model.
 *
 * Seluruh hasilnya null bila organisasi tidak memakai unit pengelola -- tiket
 * tercatat persis seperti sebelum fitur ini.
 */
final class PenentuUnitPengelola
{
    /** Batas pendakian induk kategori; sama dengan batas penelusuran hierarki di LingkupAkses. */
    private const BATAS_TINGKAT = 20;

    /** Urutan: kategori keluhan (naik ke induk sampai ketemu) → aset → kosong. */
    public function untukKeluhan(?string $kategoriKeluhanId, ?string $asetId): ?string
    {
        return $this->dariKategori($kategoriKeluhanId) ?? $this->dariAset($asetId);
    }

    /**
     * Urutan: isian eksplisit → keluhan asal → aset → rencana preventif/kalibrasi asal → kosong.
     *
     * Aset yang dipakai adalah `$asetId`, atau aset keluhan asal bila tidak
     * diberikan: keluhan lama yang tercatat sebelum fitur ini belum punya unit
     * pengelola, padahal asetnya mungkin sudah.
     */
    public function untukPerintahKerja(?string $isian, ?Keluhan $keluhan, ?string $asetId, ?string $unitRencana): ?string
    {
        if (filled($isian)) {
            return $isian;
        }

        if (filled($keluhan?->UnitPengelolaId)) {
            return $keluhan->UnitPengelolaId;
        }

        $dariAset = $this->dariAset(filled($asetId) ? $asetId : $keluhan?->AsetId);

        if ($dariAset !== null) {
            return $dariAset;
        }

        return filled($unitRencana) ? $unitRencana : null;
    }

    /**
     * `PerintahKerja.UnitOrganisasiId` yang kosong diisi unit organisasi asetnya.
     *
     * Terpisah dari unit pengelola: unit organisasi menjawab milik siapa
     * (mis. ICU), unit pengelola menjawab siapa yang memelihara (mis. IT).
     */
    public function unitOrganisasiDariAset(?string $unitOrganisasiId, ?string $asetId): ?string
    {
        if (filled($unitOrganisasiId)) {
            return $unitOrganisasiId;
        }

        $aset = $this->aset($asetId);

        return filled($aset?->UnitOrganisasiId) ? $aset->UnitOrganisasiId : null;
    }

    /**
     * Naik dari kategori ke induknya sampai menemukan unit pengelola.
     *
     * Hierarki yang rusak (induk melingkar) dihentikan oleh daftar kunjungan
     * dan batas tingkat, bukan dibiarkan berputar selamanya.
     */
    private function dariKategori(?string $kategoriKeluhanId): ?string
    {
        $dikunjungi = [];
        $kategoriId = $kategoriKeluhanId;

        for ($tingkat = 0; $tingkat < self::BATAS_TINGKAT && filled($kategoriId); $tingkat++) {
            if (isset($dikunjungi[$kategoriId])) {
                return null;
            }

            $dikunjungi[$kategoriId] = true;

            $kategori = KategoriKeluhan::query()->find($kategoriId, ['Id', 'IndukId', 'UnitPengelolaId']);

            if ($kategori === null) {
                return null;
            }

            if (filled($kategori->UnitPengelolaId)) {
                return $kategori->UnitPengelolaId;
            }

            $kategoriId = $kategori->IndukId;
        }

        return null;
    }

    private function dariAset(?string $asetId): ?string
    {
        $aset = $this->aset($asetId);

        return filled($aset?->UnitPengelolaId) ? $aset->UnitPengelolaId : null;
    }

    private function aset(?string $asetId): ?Aset
    {
        if (! filled($asetId)) {
            return null;
        }

        return Aset::query()
            ->withoutGlobalScope(ScopeLingkup::class)
            ->find($asetId, ['Id', 'UnitOrganisasiId', 'UnitPengelolaId']);
    }
}
