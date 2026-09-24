<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use Illuminate\Support\Facades\DB;

/**
 * Siapa yang diberi tahu tentang sebuah keluhan (PRD 8.21).
 *
 * Seluruh penerima -- routing kategori, eskalasi SLA, pengalihan unit
 * pengelola -- disaring lewat lingkup yang sama dengan daftar keluhan:
 * notifikasi yang tautannya lalu 404 bagi penerimanya hanya derau, dan
 * keluhan antrean IT tidak boleh sampai ke koordinator IPSRS.
 *
 * Organisasi yang tidak memakai lingkup maupun unit pengelola mendapat
 * penerima yang persis sama seperti sebelumnya: semua penggunanya tanpa
 * batas, dan cadangan unit pengelola hanya berlaku bila keluhannya punya
 * unit pengelola.
 */
final class PenerimaNotifikasiKeluhan
{
    public function __construct(private readonly PemeriksaLingkupBaris $pemeriksaLingkup) {}

    /**
     * Penerima notifikasi "Keluhan baru" saat keluhan dibuat.
     *
     * - Kategori menunjuk peran penanggung jawab: pemegang peran itu yang lingkupnya mencakup keluhan.
     * - Kategori tidak menunjuk peran, keluhan punya unit pengelola: koordinator unit pengelola itu
     *   (lihat `koordinatorUnitPengelola`, dengan cadangan tanpa batas).
     * - Selain itu: tidak ada penerima, sama seperti sebelum fitur unit pengelola.
     *
     * Pelapornya sendiri tidak pernah ikut diberi tahu.
     *
     * @return list<string>
     */
    public function untukKeluhanBaru(Keluhan $keluhan, ?string $peranPenanggungJawabId): array
    {
        if ($peranPenanggungJawabId !== null) {
            $pemegangPeran = DB::table('PenggunaPeran')
                ->where('OrganisasiId', $keluhan->OrganisasiId)
                ->where('PeranId', $peranPenanggungJawabId)
                ->where('PenggunaId', '!=', $keluhan->PelaporId)
                ->where(fn ($query) => $query->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now()))
                ->where(fn ($query) => $query->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now()))
                ->distinct()
                ->pluck('PenggunaId')
                ->map(fn ($id): string => (string) $id);

            return $this->yangMencakup($keluhan, $pemegangPeran);
        }

        if ($keluhan->UnitPengelolaId === null) {
            return [];
        }

        return $this->koordinatorUnitPengelola($keluhan, $keluhan->PelaporId, cadanganTanpaBatas: true);
    }

    /**
     * Menyaring calon penerima menjadi yang lingkupnya mencakup keluhan; urutan masukan dipertahankan.
     *
     * @param  iterable<mixed, string>  $penggunaIds
     * @return list<string>
     */
    public function yangMencakup(Keluhan $keluhan, iterable $penggunaIds): array
    {
        return $this->pemeriksaLingkup->penggunaYangMencakup($keluhan, $penggunaIds);
    }

    /**
     * Pemegang `Keluhan.Kelola` yang aktif dan berlingkup unit pengelola keluhan ini.
     *
     * Hanya yang lingkupnya mencakup unit pengelola itu **lewat penetapan
     * berlingkup**. Pemegang tanpa batas (admin, manajer) sengaja dilewati:
     * mereka melihat semua antrean, dan memberi tahu mereka setiap keluhan
     * setiap unit hanya membanjiri kotak masuknya. Perawat berlingkup ruang
     * ICU pun tidak ikut, walau ia bisa melihat keluhan dari ruangannya --
     * yang dicari adalah antrean bagian yang memelihara, bukan ruangan pemakai.
     *
     * `$cadanganTanpaBatas`: bila tidak seorang pun berlingkup unit itu,
     * pemegang tanpa batas yang menerima, supaya keluhan tidak jatuh ke
     * antrean yang tidak dijaga siapa pun tanpa kabar.
     *
     * @return list<string>
     */
    public function koordinatorUnitPengelola(Keluhan $keluhan, ?string $kecualiPenggunaId, bool $cadanganTanpaBatas): array
    {
        if ($keluhan->UnitPengelolaId === null) {
            return [];
        }

        $pemegangKelola = $this->pemegangKelolaAktif((string) $keluhan->OrganisasiId, $kecualiPenggunaId);

        if ($pemegangKelola === []) {
            return [];
        }

        // Baris penguji yang hanya membawa unit pengelolanya: cocok bagi yang
        // berlingkup unit itu (atau induknya) dan bagi yang tanpa batas.
        $hanyaUnitPengelola = $this->barisPenguji($keluhan, $keluhan->UnitPengelolaId);
        // Baris tanpa kolom lingkup apa pun: hanya cocok bagi yang tanpa batas (fail-closed).
        $tanpaKolomLingkup = $this->barisPenguji($keluhan, null);

        $mencakupUnit = $this->pemeriksaLingkup->penggunaYangMencakup($hanyaUnitPengelola, $pemegangKelola);
        $tanpaBatas = $this->pemeriksaLingkup->penggunaYangMencakup($tanpaKolomLingkup, $pemegangKelola);
        $berlingkup = array_values(array_diff($mencakupUnit, $tanpaBatas));

        if ($berlingkup !== [] || ! $cadanganTanpaBatas) {
            return $berlingkup;
        }

        return $tanpaBatas;
    }

    /**
     * Pengguna aktif yang salah satu peran berlakunya memegang `Keluhan.Kelola`.
     *
     * @return list<string>
     */
    private function pemegangKelolaAktif(string $organisasiId, ?string $kecualiPenggunaId): array
    {
        return array_values(DB::table('PenggunaPeran as pp')
            ->join('PeranIzin as pi', 'pi.PeranId', '=', 'pp.PeranId')
            ->join('Izin as i', 'i.Id', '=', 'pi.IzinId')
            ->join('Pengguna as p', 'p.Id', '=', 'pp.PenggunaId')
            ->where('pp.OrganisasiId', $organisasiId)
            ->where('i.Kode', 'Keluhan.Kelola')
            ->where('p.Status', 'Aktif')
            ->whereNull('p.DihapusPada')
            ->when($kecualiPenggunaId !== null, fn ($query) => $query->where('pp.PenggunaId', '!=', $kecualiPenggunaId))
            ->where(fn ($query) => $query->whereNull('pp.BerlakuMulai')->orWhere('pp.BerlakuMulai', '<=', now()))
            ->where(fn ($query) => $query->whereNull('pp.BerlakuSampai')->orWhere('pp.BerlakuSampai', '>=', now()))
            ->orderBy('pp.PenggunaId')
            ->distinct()
            ->pluck('pp.PenggunaId')
            ->map(fn ($id): string => (string) $id)
            ->all());
    }

    /** Keluhan tak tersimpan yang hanya membawa organisasi dan (bila ada) unit pengelola. */
    private function barisPenguji(Keluhan $keluhan, ?string $unitPengelolaId): Keluhan
    {
        return (new Keluhan)->forceFill([
            'OrganisasiId' => $keluhan->OrganisasiId,
            'UnitPengelolaId' => $unitPengelolaId,
        ]);
    }
}
