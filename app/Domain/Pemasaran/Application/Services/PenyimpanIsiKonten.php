<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use Illuminate\Contracts\Cache\Repository as Cache;

/** Menyusun dan menyimpan isi konten terbit (MARKETING.md 9). */
final class PenyimpanIsiKonten
{
    private const UMUR_DETIK = 300;

    public function __construct(private readonly Cache $cache) {}

    /**
     * Isi konten terbit pada satu slug, atau null bila tidak ada.
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

        $konten = KontenPemasaran::query()->where('Slug', $slug)->first();

        if ($konten === null || ! $konten->tayang()) {
            return null;
        }

        $versi = $konten->versiTerbit;

        if ($versi === null) {
            return null;
        }

        $isi = $this->susun($konten, $versi);
        $this->cache->put($kunci, $isi, self::UMUR_DETIK);

        return $isi;
    }

    /**
     * Isi satu versi apa pun statusnya, dipakai pratinjau draf.
     *
     * @return array<string, mixed>
     */
    public function untukVersi(KontenPemasaran $konten, VersiKontenPemasaran $versi): array
    {
        return $this->susun($konten, $versi);
    }

    public function buang(string $slug): void
    {
        $this->cache->forget($this->kunci($slug));
    }

    /** @return array<string, mixed> */
    private function susun(KontenPemasaran $konten, VersiKontenPemasaran $versi): array
    {
        return [
            'Slug' => $konten->Slug,
            'Jenis' => $konten->Jenis->value,
            'Judul' => $versi->Judul,
            'Ringkasan' => $versi->Ringkasan,
            'IsiMarkdown' => $versi->IsiMarkdown,
            'PenulisNama' => $konten->PenulisNama,
            'NoIndex' => $konten->NoIndex,
            'VersiNomor' => $versi->Nomor,
            'TerbitPada' => $konten->TerbitPada?->toIso8601String(),
            'Meta' => [
                'Judul' => $versi->MetaJudul ?? $versi->Judul,
                'Deskripsi' => $versi->MetaDeskripsi ?? $versi->Ringkasan,
                'Kanonik' => $versi->Kanonik,
                'OgJudul' => $versi->OgJudul ?? $versi->MetaJudul ?? $versi->Judul,
                'OgDeskripsi' => $versi->OgDeskripsi ?? $versi->MetaDeskripsi ?? $versi->Ringkasan,
                'OgGambar' => $versi->OgGambar,
                'SkemaTipe' => $versi->SkemaTipe,
            ],
        ];
    }

    private function kunci(string $slug): string
    {
        return 'pemasaran:konten:'.sha1($slug);
    }
}
