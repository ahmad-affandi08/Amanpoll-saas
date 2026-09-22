<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Actions\KirimFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penerimaan formulir di host publik (MARKETING.md 10).
 *
 * Anonim, tetapi tetap berada di grup `web`: tokennya diperiksa seperti
 * formulir lain, sehingga halaman pihak ketiga tidak dapat mengirim atas nama
 * pengunjung yang sedang membuka situs ini.
 *
 * Pengiriman yang tertangkap honeypot dijawab persis seperti yang berhasil.
 * Memberi tahu pengirimnya bahwa ia tertangkap sama saja dengan membuang
 * perangkapnya.
 */
final class FormulirPublikController extends Controller
{
    public function __invoke(
        Request $request,
        FormulirPemasaran $formulir,
        KirimFormulirPemasaran $aksi,
    ): RedirectResponse {
        $pengenal = $request->attributes->get('pengenalPengunjung');

        /** @var array<string, mixed> $masukan */
        $masukan = $request->except(['_token', '_method']);

        $aksi->jalankan(
            $formulir,
            $masukan,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            alamatIp: $request->ip(),
            agenPengguna: $request->userAgent(),
        );

        $pesan = $formulir->PesanSukses ?? 'Terima kasih, pesan Anda sudah kami terima.';

        if ($formulir->UrlRedirect !== null) {
            return redirect()->away($formulir->UrlRedirect)->with('sukses', $pesan);
        }

        return back()->with('sukses', $pesan);
    }
}
