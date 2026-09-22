<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Pemasaran\Application\Actions\DaftarkanTrial;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Application\Services\PenjagaKartuTrial;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TetapkanSesiPengunjung;
use App\Http\Requests\Auth\DaftarTrialRequest;
use App\Shared\Infrastructure\Validasi\AturanWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pendaftaran trial mandiri (MARKETING.md 34.1).
 *
 * Berada di host dashboard, bukan host publik: sesi dan cookie yang terbentuk
 * harus sudah benar sejak awal, dan halaman `/trial` di situs publik hanya
 * menjelaskan lalu menyeberang ke sini.
 */
final class DaftarTrialController extends Controller
{
    public function __construct(
        private readonly PembacaKonfigurasiTrial $konfigurasi,
        private readonly PenjagaKartuTrial $penjagaKartu,
    ) {}

    public function create(): Response
    {
        $setelan = $this->konfigurasi->berlaku();

        return Inertia::render('Auth/DaftarTrial', [
            'durasiHari' => $setelan->durasiHari,
            'namaPaket' => $setelan->namaPaket,
            'kartuDiminta' => $this->penjagaKartu->kartuDiminta(),
            'penyediaSiapKartu' => $this->penjagaKartu->penyediaSiapMenerimaKartu(),
            'wajib' => ['trial' => AturanWajib::untuk(DaftarTrialRequest::class)],
        ]);
    }

    public function store(DaftarTrialRequest $request, DaftarkanTrial $aksi): RedirectResponse
    {
        $trial = $aksi->jalankan($request->validated(), $this->pengenalPengunjung($request));

        return redirect()
            ->route('login')
            ->with('sukses', "Workspace Anda siap. Masuk dengan kode organisasi {$trial->organisasi?->Kode}.");
    }

    /** Pengenal kunjungan dibawa dari cookie berdomain induk supaya attribution tidak putus. */
    private function pengenalPengunjung(Request $request): ?string
    {
        $dariAtribut = $request->attributes->get('pengenalPengunjung');

        if (is_string($dariAtribut) && $dariAtribut !== '') {
            return $dariAtribut;
        }

        $dariCookie = $request->cookie(TetapkanSesiPengunjung::NAMA_COOKIE);

        return is_string($dariCookie) && $dariCookie !== '' ? $dariCookie : null;
    }
}
