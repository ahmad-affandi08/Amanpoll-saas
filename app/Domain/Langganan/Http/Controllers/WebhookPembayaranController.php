<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Controllers;

use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Application\Services\RegistriPenyediaPembayaran;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint webhook penyedia pembayaran (22.06).
 *
 * Tiga hal yang membuatnya aman dipanggil publik: keabsahan dibuktikan lewat
 * tanda tangan penyedia sebelum muatannya dipercaya; pemrosesannya idempoten,
 * sehingga pengiriman ulang tidak menggandakan pembayaran; dan ia tidak pernah
 * mempercayai OrganisasiId dari muatan, melainkan menurunkannya dari tagihan
 * yang nomornya disebut.
 */
final class WebhookPembayaranController extends Controller
{
    public function __invoke(
        Request $request,
        string $penyedia,
        RegistriPenyediaPembayaran $registri,
        CatatPembayaranLangganan $aksi,
    ): JsonResponse {
        if (! $registri->ada($penyedia)) {
            throw new AksesDitolak('Penyedia pembayaran tidak dikenal.');
        }

        $penyediaPembayaran = $registri->untuk($penyedia);

        /** @var array<string, mixed> $muatan */
        $muatan = $request->json()->all();

        /** @var array<string, string> $header */
        $header = array_map(
            fn (array $nilai): string => (string) ($nilai[0] ?? ''),
            $request->headers->all(),
        );

        if (! $penyediaPembayaran->webhookSah($muatan, $header)) {
            throw new AksesDitolak('Tanda tangan webhook tidak sah.');
        }

        $pembayaran = $aksi->dariPeristiwa($penyedia, $penyediaPembayaran->terjemahkanWebhook($muatan));

        return response()->json([
            'Diterima' => true,
            'PembayaranId' => $pembayaran->Id,
            'Status' => $pembayaran->Status,
        ]);
    }
}
