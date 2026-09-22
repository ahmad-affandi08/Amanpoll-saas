<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Menukar klik tautan referral dengan pengalihan; kode asing tetap dialihkan diam-diam agar tidak bisa ditebak (MARKETING.md 20). */
final class KlikReferralController extends Controller
{
    public function __construct(
        private readonly PelacakReferral $pelacak,
        private readonly PetaHost $host,
    ) {}

    public function __invoke(Request $request, string $kode): RedirectResponse
    {
        $pengunjung = (string) $request->attributes->get('pengenalPengunjung', '');

        if ($pengunjung !== '') {
            $this->pelacak->catatKlik($kode, $pengunjung);
        }

        return redirect()->to($this->tujuan($request));
    }

    /** Tujuan hanya boleh jalur relatif di situs publik sendiri, bukan alamat luar. */
    private function tujuan(Request $request): string
    {
        $beranda = $this->host->urlKanonik('/') ?? '/';
        $jalur = (string) $request->query('ke', '');

        if ($jalur === '' || ! str_starts_with($jalur, '/') || str_starts_with($jalur, '//')) {
            return $beranda;
        }

        return rtrim($beranda, '/').$jalur;
    }
}
