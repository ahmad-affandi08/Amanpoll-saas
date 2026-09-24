<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Domain\Kolaborasi\Application\Services\RingkasanPenyimpananBerkas;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Halaman langganan milik tenant: paket berjalan, pemakaian terhadap batas, dan tagihannya (22.04/22.06). */
final class LanggananTenantController extends Controller
{
    public function __construct(
        private readonly PemeriksaEntitlement $entitlement,
        private readonly PenjagaBatasLangganan $penjagaBatas,
    ) {}

    /**
     * Tagihan yang tampil di kartu "Tagihan", dipakai bersama halaman dan ekspornya.
     *
     * Batasnya ikut ke ekspor dengan sengaja: `lazy()` menghormati limit yang
     * sudah terpasang pada kueri, jadi berkasnya berisi periode yang sama
     * persis dengan tabelnya, bukan seluruh riwayat.
     *
     * @return Builder<TagihanLangganan>
     */
    private function kueriTagihan(): Builder
    {
        return TagihanLangganan::query()
            ->orderByDesc('PeriodeMulai')
            ->orderBy('Id')
            ->limit(24);
    }

    public function ekspor(Request $request, EksporDaftar $ekspor): StreamedResponse
    {
        $this->authorize('viewAny', Langganan::class);

        return $ekspor->unduh(
            $this->kueriTagihan(),
            [
                KolomEkspor::atribut('Nomor', 'Nomor'),
                KolomEkspor::tanggal('Periode Mulai', 'PeriodeMulai'),
                KolomEkspor::tanggal('Periode Selesai', 'PeriodeSelesai'),
                KolomEkspor::tanggal('Jatuh Tempo', 'JatuhTempo'),
                KolomEkspor::dari('Subtotal', fn (TagihanLangganan $tagihan): float => (float) $tagihan->Subtotal),
                KolomEkspor::dari('Pajak', fn (TagihanLangganan $tagihan): float => (float) $tagihan->Pajak),
                KolomEkspor::dari('Total', fn (TagihanLangganan $tagihan): float => (float) $tagihan->Total),
                KolomEkspor::dari(
                    'Status',
                    fn (TagihanLangganan $tagihan): string => StatusTagihanLangganan::tryFrom((string) $tagihan->Status)?->label()
                        ?? (string) $tagihan->Status,
                ),
            ],
            'daftar-tagihan-langganan',
            EksporDaftar::formatDari($request),
        );
    }

    public function index(RingkasanPenyimpananBerkas $penyimpanan): Response
    {
        $this->authorize('viewAny', Langganan::class);

        return Inertia::render('Langganan/Index', [
            'entitlement' => $this->entitlement->sekarang()->keArray(),
            'pemakaian' => $this->penjagaBatas->pemakaian(),
            // Paket belum membatasi ruang berkas; kartu ini hanya melaporkan pemakaian dan hasil kompresinya (PRD 11.1).
            'penyimpanan' => $penyimpanan->sekarang(),
            'katalogFitur' => array_values(array_map(
                fn (DefinisiFitur $definisi): array => $definisi->keArray(),
                KatalogFitur::semua(),
            )),
            'tagihan' => $this->kueriTagihan()
                ->get()
                ->map(fn (TagihanLangganan $tagihan): array => $this->ringkasTagihan($tagihan))
                ->all(),
        ]);
    }

    /** Memulai pembayaran sebuah tagihan. */
    public function bayar(TagihanLangganan $tagihan, RegistriPenyediaPembayaran $registri): RedirectResponse
    {
        $this->authorize('bayar', Langganan::class);

        $status = StatusTagihanLangganan::tryFrom((string) $tagihan->Status);
        if ($status === null || ! $status->masihDapatDibayar()) {
            throw new AturanBisnisDilanggar('Tagihan ini sudah tidak dapat dibayar.');
        }

        $instruksi = $registri->bawaan()->mulaiPembayaran($tagihan);

        return back()->with('instruksiPembayaran', [
            'Penyedia' => $registri->bawaan()->nama(),
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Instruksi' => $instruksi,
        ]);
    }

    /** @return array<string, mixed> */
    private function ringkasTagihan(TagihanLangganan $tagihan): array
    {
        $status = StatusTagihanLangganan::tryFrom((string) $tagihan->Status);

        return [
            'Id' => $tagihan->Id,
            'Nomor' => $tagihan->Nomor,
            'PeriodeMulai' => $tagihan->PeriodeMulai->toDateString(),
            'PeriodeSelesai' => $tagihan->PeriodeSelesai->toDateString(),
            'JatuhTempo' => $tagihan->JatuhTempo->toDateString(),
            'Subtotal' => (float) $tagihan->Subtotal,
            'Pajak' => (float) $tagihan->Pajak,
            'Total' => (float) $tagihan->Total,
            'Status' => $tagihan->Status,
            'LabelStatus' => $status?->label() ?? (string) $tagihan->Status,
            'DapatDibayar' => $status?->masihDapatDibayar() ?? false,
        ];
    }
}
