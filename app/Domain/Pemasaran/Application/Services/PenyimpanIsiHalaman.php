<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\BlokHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Menyusun dan menyimpan isi halaman terbit (MARKETING.md 8).
 *
 * Yang di-cache adalah isi halamannya, bukan respons HTTP-nya. Perbedaan itu
 * penting: badan respons Inertia memuat token CSRF milik satu sesi, sehingga
 * menyajikan ulang respons yang sama kepada orang lain akan membagikan token
 * yang salah. Isi halaman tidak memuat apa pun yang mengikat ke seseorang, jadi
 * aman dipakai bersama — sementara setiap pengunjung tetap menerima respons
 * miliknya sendiri, lengkap dengan cookie dan tokennya.
 *
 * Cache dibuang pada setiap penerbitan dan pengarsipan. Draf tidak pernah
 * masuk ke sini: pratinjau harus selalu menampilkan apa yang baru saja ditulis.
 */
final class PenyimpanIsiHalaman
{
    private const UMUR_DETIK = 300;

    public function __construct(
        private readonly Cache $cache,
        private readonly PenyusunFormulirPublik $formulir,
    ) {}

    /**
     * Isi halaman terbit pada satu slug, atau null bila tidak ada.
     *
     * @return array<string, mixed>|null
     */
    public function untukSlug(string $slug): ?array
    {
        $kunci = $this->kunci($slug);

        /** @var array<string, mixed>|null $tersimpan */
        $tersimpan = $this->cache->get($kunci);

        if ($tersimpan !== null) {
            return $tersimpan;
        }

        $halaman = HalamanPemasaran::query()->where('Slug', $slug)->first();

        if ($halaman === null || ! $halaman->terbit()) {
            return null;
        }

        $versi = $halaman->versiTerbit;

        if ($versi === null) {
            return null;
        }

        $isi = $this->susun($halaman, $versi);
        $this->cache->put($kunci, $isi, self::UMUR_DETIK);

        return $isi;
    }

    /**
     * Isi satu versi apa pun statusnya. Dipakai pratinjau draf, yang memang
     * harus melewati cache supaya penulisnya melihat tulisannya sendiri.
     *
     * @return array<string, mixed>
     */
    public function untukVersi(HalamanPemasaran $halaman, VersiHalamanPemasaran $versi): array
    {
        return $this->susun($halaman, $versi);
    }

    public function buang(string $slug): void
    {
        $this->cache->forget($this->kunci($slug));
    }

    /** @return array<string, mixed> */
    private function susun(HalamanPemasaran $halaman, VersiHalamanPemasaran $versi): array
    {
        $versi->loadMissing('blok.formulir.field');

        return [
            'Slug' => $halaman->Slug,
            'Tipe' => $halaman->Tipe,
            'Judul' => $versi->Judul,
            'Segmen' => $halaman->Segmen,
            'NoIndex' => $halaman->NoIndex,
            'VersiNomor' => $versi->Nomor,
            'Meta' => [
                'Judul' => $versi->MetaJudul ?? $versi->Judul,
                'Deskripsi' => $versi->MetaDeskripsi,
                'Kanonik' => $versi->Kanonik,
                'OgJudul' => $versi->OgJudul ?? $versi->MetaJudul ?? $versi->Judul,
                'OgDeskripsi' => $versi->OgDeskripsi ?? $versi->MetaDeskripsi,
                'OgGambar' => $versi->OgGambar,
                'SkemaTipe' => $versi->SkemaTipe,
            ],
            'Blok' => $versi->blok
                ->map(fn (BlokHalamanPemasaran $blok): array => [
                    'Id' => $blok->Id,
                    'Jenis' => $blok->Jenis->value,
                    'Isi' => $blok->Isi ?? [],
                    'Formulir' => $blok->formulir === null
                        ? null
                        : $this->formulir->susun($blok->formulir),
                ])
                ->values()
                ->all(),
        ];
    }

    private function kunci(string $slug): string
    {
        return 'pemasaran:halaman:'.sha1($slug);
    }
}
