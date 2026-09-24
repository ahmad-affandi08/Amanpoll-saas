<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Domain\Kolaborasi\Application\Services\RingkasanPenyimpananBerkas;
use App\Domain\Langganan\Application\Actions\MulaiPembayaranLangganan;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Domain\ValueObjects\DefinisiFitur;
use App\Domain\Langganan\Http\Requests\BayarTagihanLanggananRequest;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Infrastructure\Ekspor\EksporDaftar;
use App\Shared\Infrastructure\Ekspor\KolomEkspor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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

    public function index(RingkasanPenyimpananBerkas $penyimpanan, RegistriPenyediaPembayaran $registri): Response
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
            // Hanya kode dan nama: kredensial penyedia tidak pernah ikut ke peramban.
            'metodePembayaran' => array_values(array_map(
                fn (PenyediaPembayaran $penyedia): array => ['Kode' => $penyedia->kode(), 'Nama' => $penyedia->nama()],
                $registri->aktif(),
            )),
            'pembayaranKembali' => $this->ringkasPembayaranKembali(),
        ]);
    }

    /**
     * Memulai pembayaran sebuah tagihan di penyedia pilihan tenant: gateway
     * mengalihkan peramban ke halaman bayarnya, transfer manual menampilkan
     * rinciannya di halaman ini.
     */
    public function bayar(
        BayarTagihanLanggananRequest $request,
        TagihanLangganan $tagihan,
        RegistriPenyediaPembayaran $registri,
        MulaiPembayaranLangganan $aksi,
    ): SymfonyResponse {
        $pembayar = $request->user();
        if (! $pembayar instanceof Pengguna) {
            throw new AksesDitolak('Sesi pengguna tidak dikenal.');
        }

        $penyedia = $registri->aktifUntuk($request->kodePenyedia());
        $instruksi = $aksi->jalankan($tagihan, $penyedia, $pembayar);

        if ($instruksi->urlPembayaran !== null) {
            return Inertia::location($instruksi->urlPembayaran);
        }

        return back()->with('instruksiPembayaran', [
            'Penyedia' => $penyedia->nama(),
            'NomorTagihan' => (string) $tagihan->Nomor,
            'Instruksi' => $instruksi->rincian,
        ]);
    }

    /**
     * Tujuan kembali dari halaman bayar gateway. Parameter yang ditempelkan gateway
     * di URL diabaikan: status yang ditampilkan dibaca dari tagihan, yang hanya
     * berubah lewat webhook bertanda tangan.
     */
    public function kembali(TagihanLangganan $tagihan): RedirectResponse
    {
        $this->authorize('viewAny', Langganan::class);

        return redirect()->route('langganan.index')->with('pembayaranKembali', $tagihan->Id);
    }

    /** @return array{Nomor: string, Lunas: bool, LabelStatus: string}|null */
    private function ringkasPembayaranKembali(): ?array
    {
        $tagihanId = session('pembayaranKembali');
        if (! is_string($tagihanId) || $tagihanId === '') {
            return null;
        }

        $tagihan = TagihanLangganan::query()->find($tagihanId);
        if ($tagihan === null) {
            return null;
        }

        $status = StatusTagihanLangganan::tryFrom((string) $tagihan->Status);

        return [
            'Nomor' => (string) $tagihan->Nomor,
            'Lunas' => $status === StatusTagihanLangganan::Lunas,
            'LabelStatus' => $status?->label() ?? (string) $tagihan->Status,
        ];
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
