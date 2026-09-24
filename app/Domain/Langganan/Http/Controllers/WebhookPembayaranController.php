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
 * Endpoint webhook penyedia pembayaran (22.06, PRD 8.23).
 *
 * Permintaan utuh diteruskan ke adapter karena tiap penyedia punya bentuk sendiri
 * (JSON, form-urlencoded, atau badan mentah yang ditandatangani). Jawabannya 200
 * JSON dengan `success: true`, bentuk yang diterima semua penyedia (Tripay
 * mensyaratkannya); tanda tangan yang salah dijawab 403 supaya penyedia mencoba lagi.
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

        if (! $penyediaPembayaran->webhookSah($request)) {
            throw new AksesDitolak('Tanda tangan webhook tidak sah.');
        }

        $peristiwa = $penyediaPembayaran->terjemahkanWebhook($request);

        if ($peristiwa->hanyaPemberitahuan()) {
            return response()->json([
                'success' => true,
                'Diterima' => true,
                'Status' => $peristiwa->status->value,
            ]);
        }

        $pembayaran = $aksi->dariPeristiwa($penyedia, $peristiwa);

        return response()->json([
            'success' => true,
            'Diterima' => true,
            'PembayaranId' => $pembayaran->Id,
            'Status' => $pembayaran->Status,
        ]);
    }
}
