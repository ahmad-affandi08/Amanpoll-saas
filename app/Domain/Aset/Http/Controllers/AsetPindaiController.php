<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Controllers;

use App\Domain\Aset\Application\Services\PencariAsetLewatKode;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Enums\ModeLapangan;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ArahkanPenggunaLapangan;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AsetPindaiController extends Controller
{
    public function __construct(
        private readonly PencariAsetLewatKode $pencari,
        private readonly PenentuModeLapangan $penentuModeLapangan,
    ) {}

    /**
     * Scan resolver: menerima kode dari QR/barcode/NFC fisik yang ditempel ke aset.
     *
     * Tujuannya mengikuti tampilan yang dipakai pemindainya (PRD 8.20, 10):
     * mode Teknisi ke lembar "Aset ditemukan" Mode Lapangan, mode Pelapor ke
     * langkah lapor dengan aset terisi, pengguna dasbor ke halaman aset.
     * Otorisasi tetap `AsetPolicy::view` untuk semuanya.
     */
    public function tampilkan(Request $request, string $kode): RedirectResponse
    {
        $aset = $this->pencari->cari($kode);

        if (! $aset) {
            throw new DataTidakDitemukan("Aset dengan kode '{$kode}' tidak ditemukan.");
        }

        $this->authorize('view', $aset);

        return redirect(match ($this->modeLapangan($request)) {
            ModeLapangan::Teknisi => route('lapangan.teknisi.pindai', ['aset' => $aset->Id], false),
            ModeLapangan::Pelapor => route('lapangan.pelapor.lapor', ['aset' => $aset->Id], false),
            null => "/aset/{$aset->Id}",
        });
    }

    /**
     * Mode Lapangan yang sedang dipakai: pengguna lapangan murni selalu, pengguna
     * campuran hanya bila di perangkat ini memilih Mode Lapangan.
     */
    private function modeLapangan(Request $request): ?ModeLapangan
    {
        $pengguna = $request->user('web');
        $mode = $pengguna === null ? null : $this->penentuModeLapangan->mode($pengguna);

        if ($pengguna === null || $mode === null) {
            return null;
        }

        $memilihLapangan = $request->cookie(ArahkanPenggunaLapangan::COOKIE_TAMPILAN) === ArahkanPenggunaLapangan::TAMPILAN_LAPANGAN;

        return $this->penentuModeLapangan->lapanganMurni($pengguna) || $memilihLapangan ? $mode : null;
    }
}
