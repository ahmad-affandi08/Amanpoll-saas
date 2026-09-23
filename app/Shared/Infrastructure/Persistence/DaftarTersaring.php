<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Menerapkan pencarian, pengurutan, penyaringan, dan paginasi dari query string.
 *
 * Dipakai daftar yang dapat melampaui BatasDaftar::MAKS, menggantikan pemotongan
 * senyap dengan paginasi sungguhan. Tidak ada mesin pencari terpisah: seluruhnya
 * SQL biasa, supaya tetap berjalan di shared hosting tanpa layanan tambahan.
 *
 * Nama kolom tidak pernah datang dari request. Permintaan hanya menyebut kunci,
 * dan kunci itu harus ada di daftar yang disebutkan controller; kunci asing
 * diabaikan, bukan diteruskan ke SQL.
 *
 * @template TModel of Model
 */
final class DaftarTersaring
{
    private const PER_HALAMAN = 25;

    /** @var list<string> */
    private array $kolomCari = [];

    /** @var array<string, string> */
    private array $kolomUrut = [];

    private ?string $urutBawaan = null;

    private string $arahBawaan = 'asc';

    /** @var array<string, string> */
    private array $kolomFaset = [];

    /** @var array<string, callable(Builder<TModel>, string): void> */
    private array $penyaringKhusus = [];

    /**
     * @param  Builder<TModel>  $kueri
     */
    private function __construct(
        private readonly Request $permintaan,
        private readonly Builder $kueri,
    ) {}

    /**
     * @template TBaru of Model
     *
     * @param  Builder<TBaru>  $kueri
     * @return self<TBaru>
     */
    public static function untuk(Request $permintaan, Builder $kueri): self
    {
        return new self($permintaan, $kueri);
    }

    /**
     * Kolom yang ikut dicari oleh satu kotak pencarian.
     *
     * @param  list<string>  $kolom
     * @return self<TModel>
     */
    public function cari(array $kolom): self
    {
        $this->kolomCari = $kolom;

        return $this;
    }

    /**
     * Kunci urut yang diizinkan; kunci yang tidak disebut di sini diabaikan.
     *
     * @param  array<string, string>|list<string>  $kolom  kunci => kolom SQL, atau daftar kolom
     * @return self<TModel>
     */
    public function urut(array $kolom, string $bawaan, string $arahBawaan = 'asc'): self
    {
        foreach ($kolom as $kunci => $sql) {
            $this->kolomUrut[is_int($kunci) ? $sql : $kunci] = $sql;
        }

        $this->urutBawaan = $bawaan;
        $this->arahBawaan = $arahBawaan;

        return $this;
    }

    /**
     * Kolom yang dapat disaring dengan pilihan ganda dari toolbar.
     *
     * @param  array<string, string>|list<string>  $kolom
     * @return self<TModel>
     */
    public function faset(array $kolom): self
    {
        foreach ($kolom as $kunci => $sql) {
            $this->kolomFaset[is_int($kunci) ? $sql : $kunci] = $sql;
        }

        return $this;
    }

    /**
     * Penyaring yang tidak dapat dinyatakan sebagai satu kolom, mis. lewat relasi.
     *
     * Nilainya tetap hanya diteruskan ke closure milik controller, jadi aturan
     * yang sama berlaku: permintaan menyebut nilai, bukan nama kolom.
     *
     * @param  callable(Builder<TModel>, string): void  $terapkan
     * @return self<TModel>
     */
    public function saring(string $kunci, callable $terapkan): self
    {
        $this->penyaringKhusus[$kunci] = $terapkan;

        return $this;
    }

    /**
     * Bentuk paginasi yang sama dengan Eloquent API Resource, untuk daftar yang
     * barisnya disusun sendiri oleh controller alih-alih lewat Resource.
     *
     * @template TKeluar
     *
     * @param  callable(TModel): TKeluar  $peta
     * @return array{data: list<TKeluar>, meta: array{current_page: int, last_page: int, per_page: int, total: int}, links: array{first: string, last: string, prev: string|null, next: string|null}}
     */
    public function halamanTerpeta(callable $peta, int $perHalaman = self::PER_HALAMAN): array
    {
        return self::paginasi($this->halaman($perHalaman), $peta);
    }

    /**
     * Membungkus paginator yang sudah di tangan, untuk controller yang perlu
     * memakai barisnya lebih dulu -- mis. menghitung agregat khusus halaman ini.
     *
     * @template TBaris of Model
     * @template TKeluar
     *
     * @param  LengthAwarePaginator<int, TBaris>  $halaman
     * @param  callable(TBaris): TKeluar  $peta
     * @return array{data: list<TKeluar>, meta: array{current_page: int, last_page: int, per_page: int, total: int}, links: array{first: string, last: string, prev: string|null, next: string|null}}
     */
    public static function paginasi(LengthAwarePaginator $halaman, callable $peta): array
    {
        return [
            'data' => array_values(array_map($peta, $halaman->items())),
            'meta' => [
                'current_page' => $halaman->currentPage(),
                'last_page' => $halaman->lastPage(),
                'per_page' => $halaman->perPage(),
                'total' => $halaman->total(),
            ],
            'links' => [
                'first' => $halaman->url(1),
                'last' => $halaman->url($halaman->lastPage()),
                'prev' => $halaman->previousPageUrl(),
                'next' => $halaman->nextPageUrl(),
            ],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function halaman(int $perHalaman = self::PER_HALAMAN): LengthAwarePaginator
    {
        $kueri = $this->kueriTersaring();

        // Nomor halaman dibaca dari permintaan yang diserahkan ke kelas ini, bukan dari
        // resolver global Laravel, supaya hasilnya hanya bergantung pada apa yang dioper.
        $halaman = max(1, (int) $this->permintaan->query('page', '1'));

        return $kueri->paginate($perHalaman, ['*'], 'page', $halaman)->withQueryString();
    }

    /**
     * Kueri yang sudah membawa seluruh penyaring dan urutan, tetapi belum
     * dipaginasi.
     *
     * Dipakai ekspor, supaya berkas yang diunduh berisi tepat apa yang tampil
     * di layar. Menyusun ulang penyaringnya di sisi ekspor cepat atau lambat
     * akan berselisih dengan daftarnya, dan yang memegang berkasnya tidak punya
     * cara tahu bahwa isinya bukan yang ia lihat.
     *
     * @return Builder<TModel>
     */
    public function kueriTersaring(): Builder
    {
        $kueri = $this->kueri;

        $cari = trim((string) $this->permintaan->query('cari', ''));

        if ($cari !== '' && $this->kolomCari !== []) {
            // Satu grup supaya pencarian tidak melonggarkan penyaring lain di sekitarnya.
            $kueri->where(function (Builder $dalam) use ($cari): void {
                foreach ($this->kolomCari as $kolom) {
                    $dalam->orWhere($kolom, 'like', '%'.$this->lolosLike($cari).'%');
                }
            });
        }

        foreach ($this->kolomFaset as $kunci => $kolom) {
            $nilai = array_filter(explode(',', (string) $this->permintaan->query($kunci, '')));

            if ($nilai !== []) {
                $kueri->whereIn($kolom, $nilai);
            }
        }

        foreach ($this->penyaringKhusus as $kunci => $terapkan) {
            $nilai = (string) $this->permintaan->query($kunci, '');

            if ($nilai !== '') {
                $terapkan($kueri, $nilai);
            }
        }

        $kueri->orderBy($this->kolomUrut[$this->kunciUrut()] ?? $this->kunciUrut(), $this->arahUrut());

        // Kunci utama sebagai pemutus seri. Tanpa urutan total yang pasti, dua baris
        // dengan nilai urut yang sama boleh ditukar MySQL antar permintaan, sehingga
        // satu baris muncul di dua halaman sementara baris lain tidak muncul sama sekali.
        $kueri->orderBy($kueri->getModel()->getQualifiedKeyName());

        return $kueri;
    }

    /**
     * Nilai filter yang sedang berlaku, untuk dikirim balik ke tabel di frontend.
     *
     * @return array<string, string>
     */
    public function filterBerlaku(): array
    {
        $filter = [
            'cari' => (string) $this->permintaan->query('cari', ''),
            // Kunci yang dikirim balik adalah kunci kolom di tabel, bukan nama kolom SQL-nya.
            'urut' => $this->kunciUrut(),
            'arah' => $this->arahUrut(),
        ];

        foreach ([...array_keys($this->kolomFaset), ...array_keys($this->penyaringKhusus)] as $kunci) {
            $filter[$kunci] = (string) $this->permintaan->query($kunci, '');
        }

        return array_filter($filter, static fn (string $nilai): bool => $nilai !== '');
    }

    /** Kunci urut yang diminta bila dikenal; selain itu bawaan yang ditetapkan controller. */
    private function kunciUrut(): string
    {
        $diminta = (string) $this->permintaan->query('urut', '');

        return isset($this->kolomUrut[$diminta]) ? $diminta : (string) $this->urutBawaan;
    }

    /** @return 'asc'|'desc' */
    private function arahUrut(): string
    {
        $diminta = $this->kunciUrut() === (string) $this->permintaan->query('urut', '')
            ? (string) $this->permintaan->query('arah', '')
            : $this->arahBawaan;

        return $diminta === 'desc' ? 'desc' : 'asc';
    }

    /** Karakter jokernya sendiri harus dinetralkan; tanpa ini '%' dari pengguna mencocokkan segalanya. */
    private function lolosLike(string $nilai): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $nilai);
    }
}
