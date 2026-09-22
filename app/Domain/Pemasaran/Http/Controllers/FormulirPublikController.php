<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Actions\KirimFormulirPemasaran;
use App\Domain\Pemasaran\Application\Services\PenerbitTautanUnduhan;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Penerimaan formulir di host publik (MARKETING.md 10). */
final class FormulirPublikController extends Controller
{
    public function __construct(private readonly PenerbitTautanUnduhan $penerbit) {}

    public function __invoke(
        Request $request,
        FormulirPemasaran $formulir,
        KirimFormulirPemasaran $aksi,
    ): RedirectResponse {
        $pengenal = $request->attributes->get('pengenalPengunjung');

        /** @var array<string, mixed> $masukan */
        $masukan = $request->except(['_token', '_method']);

        $hasil = $aksi->jalankan(
            $formulir,
            $masukan,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            alamatIp: $request->ip(),
            agenPengguna: $request->userAgent(),
        );

        $pesan = $formulir->PesanSukses ?? 'Terima kasih, pesan Anda sudah kami terima.';

        // Tautan unduhan lahir dari pengirimannya, jadi kiriman yang ditolak spam tidak mendapatkannya.
        $unduhan = $hasil->pengiriman === null ? null : $this->penerbit->untuk($hasil->pengiriman);

        if ($formulir->UrlRedirect !== null) {
            return redirect()->away($formulir->UrlRedirect)->with('sukses', $pesan);
        }

        return back()->with('sukses', $pesan)->with('unduhan', $unduhan);
    }
}
