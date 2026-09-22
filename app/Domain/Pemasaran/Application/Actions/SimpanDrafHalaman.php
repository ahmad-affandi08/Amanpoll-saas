<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\BlokHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

/** Menyimpan draf halaman sebagai versi baru (MARKETING.md 8). */
final class SimpanDrafHalaman
{
    public function __construct(private readonly TransaksiDatabase $transaksi) {}

    /** @param array<string, mixed> $data */
    public function jalankan(?HalamanPemasaran $halaman, array $data): HalamanPemasaran
    {
        return $this->transaksi->jalankan(function () use ($halaman, $data): HalamanPemasaran {
            $halaman = $halaman === null
                ? $this->buatHalaman($data)
                : $this->perbaruiHalaman($halaman, $data);

            $versi = $this->buatVersi($halaman, $data);
            $this->buatBlok($versi, $this->blokDari($data));

            $halaman->VersiDrafId = $versi->Id;
            $halaman->save();

            return $halaman;
        });
    }

    /** @param array<string, mixed> $data */
    private function buatHalaman(array $data): HalamanPemasaran
    {
        return HalamanPemasaran::create([
            'Slug' => $this->slug($data),
            'Tipe' => $data['Tipe'],
            'Judul' => $data['Judul'],
            'Status' => StatusHalamanPemasaran::Draf,
            'Segmen' => $data['Segmen'] ?? null,
            'KampanyeId' => $data['KampanyeId'] ?? null,
            'NoIndex' => (bool) ($data['NoIndex'] ?? false),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function perbaruiHalaman(HalamanPemasaran $halaman, array $data): HalamanPemasaran
    {
        $halaman->fill([
            'Slug' => $this->slug($data),
            'Tipe' => $data['Tipe'],
            'Judul' => $data['Judul'],
            'Segmen' => $data['Segmen'] ?? null,
            'KampanyeId' => $data['KampanyeId'] ?? null,
            'NoIndex' => (bool) ($data['NoIndex'] ?? false),
        ]);
        $halaman->save();

        return $halaman;
    }

    /** @param array<string, mixed> $data */
    private function buatVersi(HalamanPemasaran $halaman, array $data): VersiHalamanPemasaran
    {
        $nomorTerakhir = (int) VersiHalamanPemasaran::query()
            ->where('HalamanPemasaranId', $halaman->Id)
            ->max('Nomor');

        return VersiHalamanPemasaran::create([
            'HalamanPemasaranId' => $halaman->Id,
            'Nomor' => $nomorTerakhir + 1,
            'Judul' => $data['Judul'],
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

    /** Menyalin blok satu versi ke versi baru. */
    public function salinBlok(VersiHalamanPemasaran $sumber, VersiHalamanPemasaran $tujuan): void
    {
        foreach ($sumber->blok as $blok) {
            BlokHalamanPemasaran::create([
                'VersiHalamanPemasaranId' => $tujuan->Id,
                'Jenis' => $blok->Jenis,
                'Urutan' => $blok->Urutan,
                'Isi' => $blok->Isi,
                'FormulirPemasaranId' => $blok->FormulirPemasaranId,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $blok
     */
    private function buatBlok(VersiHalamanPemasaran $versi, array $blok): void
    {
        foreach ($blok as $urutan => $satu) {
            $jenis = JenisBlokHalaman::from((string) $satu['Jenis']);

            BlokHalamanPemasaran::create([
                'VersiHalamanPemasaranId' => $versi->Id,
                'Jenis' => $jenis,
                'Urutan' => $urutan,
                'Isi' => is_array($satu['Isi'] ?? null) ? $satu['Isi'] : [],
                'FormulirPemasaranId' => $this->formulirUntuk($jenis, $satu),
            ]);
        }
    }

    /**
     * Blok formulir yang tidak menunjuk formulir apa pun akan tampil sebagai
     * kotak kosong di halaman terbit, jadi ditolak di sini.
     *
     * @param  array<string, mixed>  $blok
     */
    private function formulirUntuk(JenisBlokHalaman $jenis, array $blok): ?string
    {
        if (! $jenis->memakaiFormulir()) {
            return null;
        }

        $kode = is_string($blok['FormulirKode'] ?? null) ? $blok['FormulirKode'] : '';
        $formulir = $kode === ''
            ? null
            : FormulirPemasaran::query()->where('Kode', $kode)->first();

        if ($formulir === null) {
            throw new AturanBisnisDilanggar('Blok formulir harus menunjuk formulir yang ada.');
        }

        return $formulir->Id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function blokDari(array $data): array
    {
        $blok = $data['Blok'] ?? [];

        return is_array($blok) ? array_values(array_filter($blok, is_array(...))) : [];
    }

    /** @param array<string, mixed> $data */
    private function slug(array $data): string
    {
        return '/'.trim((string) $data['Slug'], '/');
    }
}
