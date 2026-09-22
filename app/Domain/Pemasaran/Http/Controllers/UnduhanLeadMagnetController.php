<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unduhan lead magnet. Rutenya bertanda tangan dan kuncinya satu pengiriman
 * formulir, jadi berkasnya tidak dapat diambil tanpa mengisi formulirnya (MARKETING.md 10).
 */
final class UnduhanLeadMagnetController extends Controller
{
    public function __construct(private readonly PerekamEventPemasaran $event) {}

    public function __invoke(Request $request, PengirimanFormulir $pengiriman): StreamedResponse
    {
        $formulir = $pengiriman->formulir;

        if ($formulir === null || ! $formulir->punyaBerkas()) {
            throw new DataTidakDitemukan('Formulir ini tidak menjanjikan berkas unduhan.');
        }

        $disk = Storage::disk(BerkasLeadMagnet::disk());
        $lokasi = (string) $formulir->BerkasLokasi;

        // Baris formulir boleh saja masih menunjuk berkas yang sudah lenyap dari disk.
        if (! $disk->exists($lokasi)) {
            throw new DataTidakDitemukan('Berkas unduhan tidak ditemukan.');
        }

        $this->catatUnduhan($request, $pengiriman);

        return $disk->download($lokasi, (string) $formulir->BerkasNamaAsli);
    }

    private function catatUnduhan(Request $request, PengirimanFormulir $pengiriman): void
    {
        $formulir = $pengiriman->formulir;
        $pengenal = $pengiriman->PengenalPengunjung ?? $request->attributes->get('pengenalPengunjung');

        $this->event->catat(
            KatalogPeristiwaPemasaran::TEMPLATE_DIUNDUH,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            url: $request->fullUrl(),
            dataTambahan: [
                'FormulirKode' => $formulir?->Kode,
                'PengirimanId' => $pengiriman->Id,
                'NamaBerkas' => $formulir?->BerkasNamaAsli,
            ],
        );
    }
}
