<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Actions\ProsesWebhookWhatsApp;
use App\Domain\Pemasaran\Application\Services\RegistriPenyediaWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenerimaWebhookWhatsApp;
use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Endpoint webhook penyedia WhatsApp: verifikasi langganan (GET) dan kabar status/pesan masuk (POST) (MARKETING.md 16). */
final class WebhookWhatsAppController extends Controller
{
    public function __construct(private readonly RegistriPenyediaWhatsApp $registri) {}

    public function verifikasi(Request $request, string $penyedia): Response
    {
        $tantangan = $this->penerima($penyedia)->verifikasiLangganan($request->query->all());

        if ($tantangan === null) {
            throw new AksesDitolak('Verifikasi webhook WhatsApp ditolak.');
        }

        return response($tantangan, 200, ['Content-Type' => 'text/plain']);
    }

    public function terima(Request $request, string $penyedia, ProsesWebhookWhatsApp $aksi): JsonResponse
    {
        $penerima = $this->penerima($penyedia);

        $header = [];

        foreach ($request->headers->all() as $nama => $nilai) {
            $header[$nama] = (string) ($nilai[0] ?? '');
        }

        if (! $penerima->webhookSah($request->getContent(), $header, $request->query->all())) {
            throw new AksesDitolak('Tanda tangan atau token webhook WhatsApp tidak sah.');
        }

        // Badan saja, tanpa kueri: token webhook ikut di URL dan tidak boleh terbaca sebagai isi.
        $muatan = $request->isJson() ? $request->json()->all() : $request->request->all();

        $hasil = $aksi->jalankan($penerima, $penerima->terjemahkanWebhook($muatan));

        return response()->json(['Diterima' => true, ...$hasil]);
    }

    private function penerima(string $kode): PenyediaWhatsApp&PenerimaWebhookWhatsApp
    {
        $penyedia = $this->registri->ada($kode) ? $this->registri->untuk($kode) : null;

        if (! $penyedia instanceof PenerimaWebhookWhatsApp) {
            throw new AksesDitolak('Penyedia WhatsApp tidak dikenal.');
        }

        return $penyedia;
    }
}
