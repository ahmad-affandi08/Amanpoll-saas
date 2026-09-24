<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Core\Izin\LingkupAkses;
use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpi;
use App\Domain\Pelaporan\Domain\Contracts\PenyediaKpiBerkelompok;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\DefinisiKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Auth;

/** Titik masuk tunggal untuk menghitung KPI (21.01/21.02). */
final class LayananMetrik
{
    /** Naikkan bila bentuk hasil KPI berubah, supaya entri cache lama tidak terbaca. */
    private const VERSI_CACHE = 'v1';

    public function __construct(
        private readonly RegistriKpi $registri,
        private readonly PemeriksaIzin $izin,
        private readonly CacheRepository $cache,
        private readonly KonteksOrganisasi $konteksOrganisasi,
        private readonly LingkupAkses $lingkupAkses,
    ) {}

    /**
     * Menghitung sekumpulan KPI untuk satu pengguna. Kunci yang tidak dikenal
     * atau tidak diizinkan dilewati diam-diam supaya konfigurasi dasbor lama
     * tidak membuat halaman gagal dimuat seluruhnya.
     *
     * Izin diperiksa lebih dulu, di luar cache: cache hanya menyimpan angka,
     * dan angka yang sama tidak pernah diberikan kepada pengguna yang tidak
     * boleh melihat KPI-nya. KPI yang dilayani satu penyedia berkelompok
     * dihitung dalam satu panggilan supaya dataset bersamanya (mis. sesi
     * downtime) diambil sekali per permintaan, bukan sekali per KPI.
     *
     * @param  array<int, string>  $kunci
     * @return array<string, array<string, mixed>>
     */
    public function hitungBanyak(array $kunci, FilterMetrik $filter, Pengguna $pengguna): array
    {
        /** @var array<string, DefinisiKpi> $definisi */
        $definisi = [];

        foreach (array_unique($kunci) as $satuKunci) {
            if (! KatalogKpi::ada($satuKunci) || ! $this->registri->ada($satuKunci)) {
                continue;
            }

            $satuDefinisi = KatalogKpi::ambil($satuKunci);
            if ($this->boleh($satuDefinisi, $pengguna)) {
                $definisi[$satuKunci] = $satuDefinisi;
            }
        }

        $angka = $this->angka(array_keys($definisi), $filter);

        $hasil = [];
        foreach ($definisi as $satuKunci => $satuDefinisi) {
            $hasil[$satuKunci] = [...$satuDefinisi->keArray(), ...$angka[$satuKunci]];
        }

        return $hasil;
    }

    /**
     * Hasil hitungan (Nilai, Rincian, Konteks) per kunci: dari cache bila ada,
     * sisanya dihitung lalu disimpan.
     *
     * @param  list<string>  $kunci
     * @return array<string, array<string, mixed>>
     */
    private function angka(array $kunci, FilterMetrik $filter): array
    {
        if ($kunci === []) {
            return [];
        }

        $detik = (int) config('amanpoll.pelaporan.cache_kpi_detik', 45);
        $awalan = $detik > 0 ? $this->awalanCache($filter) : null;

        $hasil = [];
        $belum = $kunci;

        if ($awalan !== null) {
            $kunciCache = [];
            foreach ($kunci as $satu) {
                $kunciCache[$awalan.$satu] = $satu;
            }

            $belum = [];
            foreach ($this->cache->many(array_keys($kunciCache)) as $kunciSatu => $nilai) {
                if (is_array($nilai)) {
                    $hasil[$kunciCache[$kunciSatu]] = $nilai;
                } else {
                    $belum[] = $kunciCache[$kunciSatu];
                }
            }
        }

        $baru = $this->hitungLangsung($belum, $filter);

        if ($awalan !== null && $baru !== []) {
            $simpan = [];
            foreach ($baru as $satu => $nilai) {
                $simpan[$awalan.$satu] = $nilai;
            }
            $this->cache->putMany($simpan, $detik);
        }

        return [...$hasil, ...$baru];
    }

    /**
     * @param  list<string>  $kunci
     * @return array<string, array<string, mixed>>
     */
    private function hitungLangsung(array $kunci, FilterMetrik $filter): array
    {
        /** @var array<int, array{penyedia: PenyediaKpi, kunci: list<string>}> $kelompok */
        $kelompok = [];
        foreach ($kunci as $satu) {
            $penyedia = $this->registri->untuk($satu);
            $kelompok[spl_object_id($penyedia)]['penyedia'] = $penyedia;
            $kelompok[spl_object_id($penyedia)]['kunci'][] = $satu;
        }

        $hasil = [];
        foreach ($kelompok as ['penyedia' => $penyedia, 'kunci' => $kunciPenyedia]) {
            if ($penyedia instanceof PenyediaKpiBerkelompok) {
                foreach ($penyedia->hitungBanyak($kunciPenyedia, $filter) as $satu => $hasilKpi) {
                    $hasil[$satu] = $hasilKpi->keArray();
                }

                continue;
            }

            foreach ($kunciPenyedia as $satu) {
                $hasil[$satu] = $penyedia->hitung($satu, $filter)->keArray();
            }
        }

        return $hasil;
    }

    /**
     * Awalan kunci cache, atau null bila hasilnya tidak boleh di-cache.
     *
     * Memuat semua yang memengaruhi angka: organisasi (ScopeOrganisasi),
     * lingkup akses pengguna yang sedang masuk (ScopeLingkup menyaring Aset,
     * Keluhan, PerintahKerja, Gudang, dan rencana menurut unit dan ruangan
     * yang ditugaskan), serta seluruh filter. Dua pengguna dengan lingkup yang
     * persis sama memang melihat angka yang sama, jadi boleh berbagi entri;
     * lingkup yang berbeda satu ruangan pun menghasilkan kunci lain.
     */
    private function awalanCache(FilterMetrik $filter): ?string
    {
        $organisasiId = $this->konteksOrganisasi->id();
        if ($organisasiId === null) {
            return null;
        }

        $sidik = hash('sha256', (string) json_encode([
            'Lingkup' => $this->sidikLingkup(),
            'Filter' => $filter->sidik(),
        ]));

        return 'amanpoll:kpi:'.self::VERSI_CACHE.':'.$organisasiId.':'.$sidik.':';
    }

    /**
     * Lingkup data yang berlaku, dibaca dari sumber yang sama dengan ScopeLingkup:
     * pengguna web yang sedang masuk, bukan pengguna yang diteruskan ke
     * hitungBanyak() (keduanya sama pada jalur normal; kalau berbeda, yang
     * menyaring baris adalah pengguna web).
     *
     * @return array{Semua: bool, Unit?: list<string>, Lokasi?: list<string>}
     */
    private function sidikLingkup(): array
    {
        $pengguna = Auth::guard('web')->user();
        if ($pengguna === null) {
            return ['Semua' => true];
        }

        $penggunaId = (string) $pengguna->getAuthIdentifier();
        if ($this->lingkupAkses->tanpaBatas($penggunaId)) {
            return ['Semua' => true];
        }

        $unit = $this->lingkupAkses->unitDiizinkan($penggunaId);
        $lokasi = $this->lingkupAkses->lokasiDiizinkan($penggunaId);
        sort($unit, SORT_STRING);
        sort($lokasi, SORT_STRING);

        return ['Semua' => false, 'Unit' => $unit, 'Lokasi' => $lokasi];
    }

    /**
     * Definisi KPI yang boleh dilihat pengguna, untuk pemilih komponen dasbor
     * kustom dan pemilih laporan.
     *
     * @return list<array<string, mixed>>
     */
    public function katalogUntuk(Pengguna $pengguna): array
    {
        return array_values(array_map(
            fn (DefinisiKpi $definisi): array => $definisi->keArray(),
            array_filter(
                KatalogKpi::semua(),
                fn (DefinisiKpi $definisi): bool => $this->boleh($definisi, $pengguna)
                    && $this->registri->ada($definisi->kunci),
            ),
        ));
    }

    /**
     * @param  array<int, string>  $kunci
     * @return list<string>
     */
    public function saringYangDiizinkan(array $kunci, Pengguna $pengguna): array
    {
        return array_values(array_filter(
            $kunci,
            fn (string $satu): bool => KatalogKpi::ada($satu)
                && $this->boleh(KatalogKpi::ambil($satu), $pengguna),
        ));
    }

    public function boleh(DefinisiKpi $definisi, Pengguna $pengguna): bool
    {
        if ($pengguna->Status !== 'Aktif') {
            return false;
        }

        return $definisi->izin === null || $this->izin->boleh($pengguna->Id, $definisi->izin);
    }
}
