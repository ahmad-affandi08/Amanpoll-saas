<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Controllers;

use App\Domain\Kalibrasi\Application\Services\LayananPeringatanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class KalibrasiDashboardController extends Controller
{
    public function __construct(
        private readonly LayananPeringatanKalibrasi $layananPeringatan,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RencanaKalibrasi::class);

        $organisasiId = $request->user('web')->OrganisasiId;

        $kepatuhan = $this->layananPeringatan->hitungKepatuhan($organisasiId);

        $hariIni = Carbon::today();

        // Rencana kalibrasi aktif beserta asetnya
        $rencanaList = RencanaKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia'])
            ->where('OrganisasiId', $organisasiId)
            ->where('Aktif', true)
            ->orderBy('TanggalBerikutnya')
            ->limit(BatasDaftar::MAKS)
            ->get()
            ->map(function ($rk) use ($hariIni) {
                $tglBerikutnya = Carbon::parse($rk->TanggalBerikutnya);
                $batasPeringatan = (clone $hariIni)->addDays((int) $rk->PeringatanHariSebelum);

                if ($tglBerikutnya->lt($hariIni)) {
                    $status = 'Terlambat';
                } elseif ($tglBerikutnya->lte($batasPeringatan)) {
                    $status = 'SegeraJatuhTempo';
                } else {
                    $status = 'Valid';
                }

                $rk->StatusKalibrasi = $status;
                $rk->SisaHari = (int) $hariIni->diffInDays($tglBerikutnya, false);

                return $rk;
            });

        // Pelaksanaan kalibrasi terbaru
        $pelaksanaanTerbaru = PelaksanaanKalibrasi::query()
            ->with(['aset', 'jenisKalibrasi', 'penyedia', 'dilaksanakanOleh'])
            ->where('OrganisasiId', $organisasiId)
            ->latest('TanggalKalibrasi')
            ->take(10)
            ->get();

        return Inertia::render('Kalibrasi/Index', [
            'statistik' => $kepatuhan,
            'rencanaKalibrasi' => $rencanaList,
            'pelaksanaanTerbaru' => $pelaksanaanTerbaru,
        ]);
    }
}
