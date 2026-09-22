<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman langganan milik tenant: paket berjalan, pemakaian terhadap batas, dan tagihannya (22.04/22.06). */
final class LanggananTenantController extends Controller
{
    public function __construct(
        private readonly PemeriksaEntitlement $entitlement,
        private readonly PenjagaBatasLangganan $penjagaBatas,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Langganan::class);

        return Inertia::render('Langganan/Index', [
            'entitlement' => $this->entitlement->sekarang()->keArray(),
            'pemakaian' => $this->penjagaBatas->pemakaian(),
            'katalogFitur' => array_values(array_map(
                fn (DefinisiFitur $definisi): array => $definisi->keArray(),
                KatalogFitur::semua(),
            )),
            'tagihan' => TagihanLangganan::query()
                ->orderByDesc('PeriodeMulai')
                ->limit(24)
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
