<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiKonten;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/** Menyimpan draf konten sebagai versi baru (MARKETING.md 9). */
final class SimpanDrafKonten
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly PencariRedirectPemasaran $pencari,
        private readonly PenyimpanIsiKonten $isi,
    ) {}

    /** @param array<string, mixed> $data */
    public function jalankan(?KontenPemasaran $konten, array $data): KontenPemasaran
    {
        return $this->transaksi->jalankan(function () use ($konten, $data): KontenPemasaran {
            $jenis = $this->jenis($data);
            $slug = $this->slug($jenis, (string) $data['Slug']);

            $konten = $konten === null
                ? $this->buatKonten($jenis, $slug, $data)
                : $this->perbaruiKonten($konten, $jenis, $slug, $data);

            $versi = $this->buatVersi($konten, $data);

            $konten->VersiDrafId = $versi->Id;
            $konten->save();

            $this->isi->buang($konten->Slug);

            return $konten;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buatKonten(JenisKontenPemasaran $jenis, string $slug, array $data): KontenPemasaran
    {
        $this->pastikanJalurBebas($slug, null);
        $this->bebaskanJalur($slug);

        return KontenPemasaran::create([
            'Slug' => $slug,
            'Jenis' => $jenis,
            'Judul' => $data['Judul'],
            'Status' => StatusHalamanPemasaran::Draf,
            'PenulisNama' => $data['PenulisNama'] ?? null,
            'KampanyeId' => $data['KampanyeId'] ?? null,
            'NoIndex' => (bool) ($data['NoIndex'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perbaruiKonten(
        KontenPemasaran $konten,
        JenisKontenPemasaran $jenis,
        string $slug,
        array $data,
    ): KontenPemasaran {
        $slugLama = $konten->Slug;

        if ($slug !== $slugLama) {
            $this->pastikanJalurBebas($slug, $konten->Id);
            $this->bebaskanJalur($slug);
            $this->isi->buang($slugLama);
        }

        $konten->fill([
            'Slug' => $slug,
            'Jenis' => $jenis,
            'Judul' => $data['Judul'],
            'PenulisNama' => $data['PenulisNama'] ?? null,
            'KampanyeId' => $data['KampanyeId'] ?? null,
            'NoIndex' => (bool) ($data['NoIndex'] ?? false),
        ]);
        $konten->save();

        // Alamat yang pernah terbit tidak boleh mati diam-diam ketika slugnya dipindah.
        if ($slug !== $slugLama && $konten->TerbitPada !== null) {
            $this->alihkan($slugLama, $slug);
        }

        return $konten;
    }

    /**
     * Satu jalur publik hanya boleh punya satu pemilik. Rute konten dikenali
     * sebelum penampung halaman, jadi jalur kembar akan menyembunyikan halamannya.
     */
    private function pastikanJalurBebas(string $slug, ?string $kecualiKontenId): void
    {
        $kueri = KontenPemasaran::query()->where('Slug', $slug);

        if ($kecualiKontenId !== null) {
            $kueri->where('Id', '!=', $kecualiKontenId);
        }

        if ($kueri->exists()) {
            throw new AturanBisnisDilanggar("Jalur {$slug} sudah dipakai konten lain.");
        }

        if (HalamanPemasaran::query()->where('Slug', $slug)->exists()) {
            throw new AturanBisnisDilanggar("Jalur {$slug} sudah dipakai halaman pemasaran.");
        }
    }

    /**
     * Redirect lama yang berangkat dari jalur ini dimatikan lebih dulu, karena
     * peta redirect dijalankan sebelum konten sempat dicari.
     */
    private function bebaskanJalur(string $slug): void
    {
        $terpengaruh = RedirectPemasaran::query()
            ->where('Dari', $slug)
            ->where('Aktif', true)
            ->update(['Aktif' => false]);

        if ($terpengaruh > 0) {
            $this->pencari->buangCache();
        }
    }

    private function alihkan(string $dari, string $ke): void
    {
        RedirectPemasaran::query()->updateOrCreate(
            ['Dari' => $dari],
            [
                'Ke' => $ke,
                'Kode' => KodeRedirect::Permanen,
                'Aktif' => true,
                'Catatan' => 'Dibuat otomatis saat slug konten dipindahkan.',
            ],
        );

        $this->pencari->buangCache();
    }

    /** @param array<string, mixed> $data */
    private function buatVersi(KontenPemasaran $konten, array $data): VersiKontenPemasaran
    {
        $nomorTerakhir = (int) VersiKontenPemasaran::query()
            ->where('KontenPemasaranId', $konten->Id)
            ->max('Nomor');

        return VersiKontenPemasaran::create([
            'KontenPemasaranId' => $konten->Id,
            'Nomor' => $nomorTerakhir + 1,
            'Judul' => $data['Judul'],
            'Ringkasan' => $data['Ringkasan'] ?? null,
            'IsiMarkdown' => (string) ($data['IsiMarkdown'] ?? ''),
            'MetaJudul' => $data['MetaJudul'] ?? null,
            'MetaDeskripsi' => $data['MetaDeskripsi'] ?? null,
            'Kanonik' => $data['Kanonik'] ?? null,
            'OgJudul' => $data['OgJudul'] ?? null,
            'OgDeskripsi' => $data['OgDeskripsi'] ?? null,
            'OgGambar' => $data['OgGambar'] ?? null,
            'SkemaTipe' => $data['SkemaTipe'] ?? null,
            'Catatan' => $data['Catatan'] ?? null,
            'DibuatOlehPlatformId' => Auth::guard('platform')->id(),
            'DibuatPada' => CarbonImmutable::now(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function jenis(array $data): JenisKontenPemasaran
    {
        $jenis = $data['Jenis'];

        return $jenis instanceof JenisKontenPemasaran
            ? $jenis
            : JenisKontenPemasaran::from((string) $jenis);
    }

    /** Jalur publik selalu lahir dari jenisnya, jadi rak dan alamatnya tidak pernah berselisih. */
    private function slug(JenisKontenPemasaran $jenis, string $segmen): string
    {
        return $jenis->awalanJalur().'/'.trim($segmen, '/');
    }
}
