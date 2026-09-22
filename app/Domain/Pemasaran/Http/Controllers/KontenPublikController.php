<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\PenautHostPengunjung;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiKonten;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Konten CMS di host publik (MARKETING.md 9). */
final class KontenPublikController extends Controller
{
    public function __construct(
        private readonly PenyimpanIsiKonten $isi,
        private readonly PetaHost $host,
        private readonly PenautHostPengunjung $penaut,
        private readonly PerekamEventPemasaran $event,
    ) {}

    public function tampil(Request $request, string $rak, string $ruas): Response
    {
        $isi = $this->isi->untukSlug('/'.$rak.'/'.$ruas);

        if ($isi === null) {
            throw new DataTidakDitemukan('Konten tidak ditemukan.');
        }

        $this->catatDilihat($request, $isi);

        return $this->render($request, $isi);
    }

    /** Pratinjau satu versi, terbit maupun tidak; tidak pernah dihitung sebagai artikel dilihat. */
    public function pratinjau(
        Request $request,
        KontenPemasaran $konten,
        VersiKontenPemasaran $versi,
    ): Response {
        if ($versi->KontenPemasaranId !== $konten->Id) {
            throw new DataTidakDitemukan('Versi tersebut bukan milik konten ini.');
        }

        return $this->render($request, [
            ...$this->isi->untukVersi($konten, $versi),
            'NoIndex' => true,
            'Pratinjau' => true,
        ]);
    }

    /** @param array<string, mixed> $isi */
    private function catatDilihat(Request $request, array $isi): void
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');

        $this->event->catat(
            KatalogPeristiwaPemasaran::ARTIKEL_DILIHAT,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            url: $request->fullUrl(),
            dataTambahan: [
                'Slug' => $isi['Slug'],
                'Jenis' => $isi['Jenis'],
                'VersiNomor' => $isi['VersiNomor'],
            ],
        );
    }

    /** @param array<string, mixed> $isi */
    private function render(Request $request, array $isi): Response
    {
        $pengenal = $request->attributes->get('pengenalPengunjung');
        $pengenal = is_string($pengenal) ? $pengenal : null;

        return Inertia::render('Publik/Konten', [
            'kanonik' => $this->host->urlKanonik($request->path()),
            'urlMasuk' => $this->penaut->tautan(route('login'), $pengenal),
            'urlDaftar' => $this->penaut->tautan(route('daftar'), $pengenal),
            'konten' => $isi,
        ]);
    }
}
