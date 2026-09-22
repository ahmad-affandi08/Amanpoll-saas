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
     * @return LengthAwarePaginator<int, TModel>
     */
    public function halaman(int $perHalaman = self::PER_HALAMAN): LengthAwarePaginator
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

        $kueri->orderBy($this->kolomUrut[$this->kunciUrut()] ?? $this->kunciUrut(), $this->arahUrut());

        return $kueri->paginate($perHalaman)->withQueryString();
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

        foreach (array_keys($this->kolomFaset) as $kunci) {
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
