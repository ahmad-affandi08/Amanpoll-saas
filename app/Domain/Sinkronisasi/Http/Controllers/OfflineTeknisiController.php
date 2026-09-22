<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Application\Actions\DaftarkanPerangkatPengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananPaketOffline;
use App\Domain\Sinkronisasi\Application\Services\LayananPenandaSinkronisasi;
use App\Domain\Sinkronisasi\Http\Requests\DaftarkanPerangkatRequest;
use App\Domain\Sinkronisasi\Http\Resources\AntrianSinkronisasiResource;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Http\Controllers\Controller;
use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Ruang kerja teknisi yang dapat dipakai tanpa koneksi (20.05). */
final class OfflineTeknisiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sinkronisasi/Teknisi');
    }

    /** Mendaftarkan perangkat lalu mengirim paket kerja terbaru beserta status antrean perangkat itu. */
    public function paket(
        DaftarkanPerangkatRequest $request,
        DaftarkanPerangkatPengguna $daftarkan,
        LayananPaketOffline $paketOffline,
        LayananPenandaSinkronisasi $penanda,
    ): JsonResponse {
        $pengguna = $request->user('web');
        $perangkat = $daftarkan->jalankan($pengguna, $request->validated());
        $paket = $paketOffline->bangun($pengguna);

        return response()->json([
            'Perangkat' => [
                'Id' => $perangkat->Id,
                'NamaPerangkat' => $perangkat->NamaPerangkat,
                'TerakhirSinkronPada' => $perangkat->TerakhirSinkronPada?->toIso8601String(),
            ],
            'Paket' => $paket,
            'Penanda' => $penanda->catatPaket($perangkat, $paket['Token']),
            'Antrean' => AntrianSinkronisasiResource::collection(
                AntrianSinkronisasi::query()
                    ->where('PerangkatPenggunaId', $perangkat->Id)
                    ->whereIn('Status', ['Menunggu', 'Diproses', 'Gagal', 'Konflik'])
                    ->orderBy('DiterimaPada')
                    ->limit(BatasDaftar::MAKS)
                    ->get()
            )->resolve(),
        ]);
    }

    /** Dipanggil klien tepat sebelum keluar, setelah data lokal dihapus (20.02). */
    public function lepaskanPerangkat(
        DaftarkanPerangkatRequest $request,
        DaftarkanPerangkatPengguna $daftarkan,
    ): JsonResponse {
        $pengguna = $request->user('web');
        $perangkat = $daftarkan->jalankan($pengguna, $request->validated());
        $daftarkan->lepaskan($perangkat);

        return response()->json(['Dilepas' => true]);
    }

    /** Ringkasan singkat untuk indikator sinkronisasi di topbar. */
    public function ringkasan(Request $request): JsonResponse
    {
        $pengguna = $request->user('web');

        $antrean = AntrianSinkronisasi::query()
            ->whereIn('PerangkatPenggunaId', PerangkatPengguna::query()
                ->select('Id')
                ->where('PenggunaId', $pengguna->Id)
                ->getQuery())
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');

        return response()->json([
            'Menunggu' => (int) ($antrean['Menunggu'] ?? 0) + (int) ($antrean['Diproses'] ?? 0),
            'Konflik' => (int) ($antrean['Konflik'] ?? 0),
            'Gagal' => (int) ($antrean['Gagal'] ?? 0),
        ]);
    }
}
