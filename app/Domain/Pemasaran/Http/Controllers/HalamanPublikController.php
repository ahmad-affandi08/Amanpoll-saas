<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\PenautHostPengunjung;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiHalaman;
use App\Domain\Pemasaran\Application\Services\PenyusunPresentasiHarga;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman pemasaran di host publik (MARKETING.md 8, 34.1). */
final class HalamanPublikController extends Controller
{
    public function __construct(
        private readonly PenyimpanIsiHalaman $isi,
        private readonly PetaHost $host,
        private readonly PenautHostPengunjung $penaut,
        private readonly PenyusunPresentasiHarga $harga,
        private readonly PerekamEventPemasaran $event,
    ) {}

    /** Akar situs. */
    public function beranda(Request $request): Response
    {
        $isi = $this->isi->untukSlug('/');

        if ($isi === null) {
            return Inertia::render('Publik/Beranda', $this->propsBersama($request));
        }

        return $this->render($request, $isi);
    }

    public function tampil(Request $request, string $jalur): Response
    {
        $isi = $this->isi->untukSlug('/'.trim($jalur, '/'));

        if ($isi === null) {
            throw new DataTidakDitemukan('Halaman tidak ditemukan.');
        }

        return $this->render($request, $isi);
    }

    /** Pratinjau satu versi, terbit maupun tidak. */
    public function pratinjau(
        Request $request,
        HalamanPemasaran $halaman,
        VersiHalamanPemasaran $versi,
    ): Response {
        if ($versi->HalamanPemasaranId !== $halaman->Id) {
            throw new DataTidakDitemukan('Versi tersebut bukan milik halaman ini.');
        }

        return $this->render($request, [
            ...$this->isi->untukVersi($halaman, $versi),
            'NoIndex' => true,
            'Pratinjau' => true,
        ]);
    }

    /** @param array<string, mixed> $isi */
    private function render(Request $request, array $isi): Response
    {
        // Harga disusun di luar cache isi halaman, supaya yang tampil selalu harga paket hari ini.
        $isi = $this->harga->terapkan($isi);

        $this->catatHargaDilihat($request, $isi);

        return Inertia::render('Publik/Halaman', [
            ...$this->propsBersama($request),
            'halaman' => $isi,
        ]);
    }

    /**
     * Halaman yang benar-benar memuat blok harga menghitung satu HargaDilihat.
     *
     * @param  array<string, mixed>  $isi
     */
    private function catatHargaDilihat(Request $request, array $isi): void
    {
        $blok = $isi['Blok'] ?? [];

        if (! is_array($blok) || ! $this->memuatBlokHarga($blok)) {
            return;
        }

        $pengenal = $request->attributes->get('pengenalPengunjung');

        $this->event->catat(
            KatalogPeristiwaPemasaran::HARGA_DILIHAT,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            url: $request->fullUrl(),
            dataTambahan: ['Slug' => $isi['Slug'] ?? null],
        );
    }

    /** @param array<mixed> $blok */
    private function memuatBlokHarga(array $blok): bool
    {
        foreach ($blok as $satu) {
            if (is_array($satu) && ($satu['Jenis'] ?? null) === JenisBlokHalaman::Harga->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prop yang sama pada setiap halaman publik: alamat kanonik dan dua tautan
     * penyeberangan ke host dashboard.
     *
     * @return array<string, mixed>
     */
    private function propsBersama(Request $request): array
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');
        $pengenal = is_string($pengenal) ? $pengenal : null;

        return [
            'kanonik' => $this->host->urlKanonik($request->path()),
            'urlMasuk' => $this->penaut->tautan(route('login'), $pengenal),
            'urlDaftar' => $this->penaut->tautan(route('daftar'), $pengenal),
        ];
    }
}
