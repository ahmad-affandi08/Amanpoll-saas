<?php

declare(strict_types=1);

namespace App\Core\Izin;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Batas data seorang pengguna: seluruh organisasi, atau hanya unit dan ruangan
 * yang ditugaskan kepadanya.
 *
 * `PenggunaPeran` sejak awal menyimpan `UnitOrganisasiId` dan `LokasiId`, dan
 * formulir penugasan peran sudah mengisinya, tetapi tidak ada satu pun kueri
 * yang membacanya. Akibatnya penugasan "Teknisi, ruang Poli Umum" tampak
 * terbatas di layar padahal penggunanya tetap melihat seluruh rumah sakit.
 * Kelas ini yang menutup jarak itu.
 *
 * Aturannya sengaja permisif di satu titik: satu saja penugasan tanpa cakupan
 * (kedua kolomnya kosong) berarti pengguna itu melihat seluruh organisasi.
 * Tanpa itu, setiap tenant yang sudah berjalan -- yang seluruh penugasannya
 * memang tanpa cakupan -- akan kehilangan aksesnya begitu versi ini naik.
 */
final class LingkupAkses
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly KonteksOrganisasi $konteksOrganisasi,
    ) {}

    /** Pengguna tanpa batas melihat seluruh organisasinya, seperti sebelum fitur ini ada. */
    public function tanpaBatas(string $penggunaId): bool
    {
        return $this->lingkup($penggunaId)['TanpaBatas'];
    }

    /** @return list<string> */
    public function unitDiizinkan(string $penggunaId): array
    {
        return $this->lingkup($penggunaId)['Unit'];
    }

    /** @return list<string> */
    public function lokasiDiizinkan(string $penggunaId): array
    {
        return $this->lingkup($penggunaId)['Lokasi'];
    }

    /**
     * tanpaBatas() untuk banyak pengguna sekaligus, dipakai halaman daftar.
     *
     * Jawabannya cukup dari penugasan yang berlaku -- tanpa peran, atau satu
     * penugasan tanpa cakupan, berarti tanpa batas -- jadi tidak perlu
     * menelusuri hierarki unit dan ruangan per pengguna: satu kueri untuk
     * seluruh halaman, bukan satu per baris.
     *
     * @param  iterable<mixed, string>  $penggunaIds
     * @return array<string, bool> kunci: Id pengguna
     */
    public function tanpaBatasUntuk(iterable $penggunaIds): array
    {
        $hasil = [];

        foreach ($penggunaIds as $penggunaId) {
            $hasil[(string) $penggunaId] = true;
        }

        $organisasiId = $this->konteksOrganisasi->id();

        if ($organisasiId === null || $hasil === []) {
            return $hasil;
        }

        $penugasan = $this->kueriPenugasanBerlaku($organisasiId)
            ->whereIn('PenggunaId', array_keys($hasil))
            ->get(['PenggunaId', 'UnitOrganisasiId', 'LokasiId'])
            ->groupBy('PenggunaId');

        foreach ($penugasan as $penggunaId => $milikPengguna) {
            $hasil[(string) $penggunaId] = $milikPengguna->contains(
                fn (object $satu): bool => $satu->UnitOrganisasiId === null && $satu->LokasiId === null,
            );
        }

        return $hasil;
    }

    public function bersihkanCache(string $organisasiId, string $penggunaId): void
    {
        $this->cache->forget($this->kunciCache($organisasiId, $penggunaId));
    }

    /**
     * @return array{TanpaBatas: bool, Unit: list<string>, Lokasi: list<string>}
     */
    private function lingkup(string $penggunaId): array
    {
        $organisasiId = $this->konteksOrganisasi->id();

        if ($organisasiId === null) {
            return ['TanpaBatas' => true, 'Unit' => [], 'Lokasi' => []];
        }

        /** @var array{TanpaBatas: bool, Unit: list<string>, Lokasi: list<string>} */
        return $this->cache->remember(
            $this->kunciCache($organisasiId, $penggunaId),
            now()->addMinutes(5),
            fn (): array => $this->hitung($organisasiId, $penggunaId),
        );
    }

    /**
     * @return array{TanpaBatas: bool, Unit: list<string>, Lokasi: list<string>}
     */
    private function hitung(string $organisasiId, string $penggunaId): array
    {
        $penugasan = $this->kueriPenugasanBerlaku($organisasiId)
            ->where('PenggunaId', $penggunaId)
            ->get(['UnitOrganisasiId', 'LokasiId']);

        // Tanpa peran sama sekali berarti tanpa izin apa pun; membatasi datanya
        // tidak menambah keamanan dan hanya menyulitkan penelusuran.
        if ($penugasan->isEmpty()) {
            return ['TanpaBatas' => true, 'Unit' => [], 'Lokasi' => []];
        }

        $unit = [];
        $lokasi = [];

        foreach ($penugasan as $satu) {
            if ($satu->UnitOrganisasiId === null && $satu->LokasiId === null) {
                return ['TanpaBatas' => true, 'Unit' => [], 'Lokasi' => []];
            }

            if ($satu->UnitOrganisasiId !== null) {
                $unit[] = (string) $satu->UnitOrganisasiId;
            }

            if ($satu->LokasiId !== null) {
                $lokasi[] = (string) $satu->LokasiId;
            }
        }

        $unit = $this->denganKeturunan($organisasiId, 'UnitOrganisasi', $unit);

        // Ruangan milik unit yang diizinkan ikut terbawa: menugaskan orang ke
        // satu instalasi tanpa memberinya ruangan instalasi itu tidak masuk akal.
        $lokasiUnit = $unit === [] ? [] : DB::table('Lokasi')
            ->where('OrganisasiId', $organisasiId)
            ->whereIn('UnitOrganisasiId', $unit)
            ->pluck('Id')
            ->all();

        $lokasi = $this->denganKeturunan(
            $organisasiId,
            'Lokasi',
            array_values(array_unique([...$lokasi, ...array_map(strval(...), $lokasiUnit)])),
        );

        return ['TanpaBatas' => false, 'Unit' => $unit, 'Lokasi' => $lokasi];
    }

    /** Penugasan peran yang sedang berlaku (rentang BerlakuMulai–BerlakuSampai) di satu organisasi. */
    private function kueriPenugasanBerlaku(string $organisasiId): Builder
    {
        return DB::table('PenggunaPeran')
            ->where('OrganisasiId', $organisasiId)
            ->where(function ($q): void {
                $q->whereNull('BerlakuMulai')->orWhere('BerlakuMulai', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('BerlakuSampai')->orWhere('BerlakuSampai', '>=', now());
            });
    }

    /**
     * Menelusuri anak-anak pada tabel self-referencing.
     *
     * Ditelusuri per tingkat memakai `whereIn` karena shared hosting memakai
     * MariaDB tanpa jaminan dukungan CTE rekursif. Batas tingkat mencegah
     * hierarki yang rusak membuat kuerinya berputar selamanya.
     *
     * @param  list<string>  $akar
     * @return list<string>
     */
    private function denganKeturunan(string $organisasiId, string $tabel, array $akar): array
    {
        if ($akar === []) {
            return [];
        }

        $terkumpul = $akar;
        $tingkatIni = $akar;

        for ($tingkat = 0; $tingkat < 20; $tingkat++) {
            $anak = DB::table($tabel)
                ->where('OrganisasiId', $organisasiId)
                ->whereIn('IndukId', $tingkatIni)
                ->whereNull('DihapusPada')
                ->pluck('Id')
                ->all();

            $baru = array_values(array_diff(array_map(strval(...), $anak), $terkumpul));

            if ($baru === []) {
                break;
            }

            $terkumpul = [...$terkumpul, ...$baru];
            $tingkatIni = $baru;
        }

        return array_values(array_unique($terkumpul));
    }

    private function kunciCache(string $organisasiId, string $penggunaId): string
    {
        return "lingkup:{$organisasiId}:{$penggunaId}";
    }
}
