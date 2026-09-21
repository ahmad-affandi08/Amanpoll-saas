<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Sinkronisasi\Application\Actions\DaftarkanPerangkatPengguna;
use App\Domain\Sinkronisasi\Application\Services\LayananAntrianSinkronisasi;
use App\Domain\Sinkronisasi\Domain\Enums\KeputusanKonflikSinkronisasi;
use App\Domain\Sinkronisasi\Http\Requests\DaftarkanPerangkatRequest;
use App\Domain\Sinkronisasi\Http\Requests\DorongAntrianSinkronisasiRequest;
use App\Domain\Sinkronisasi\Http\Resources\AntrianSinkronisasiResource;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Jalur masuk antrean mutasi offline (20.03) dan penyelesaian konfliknya
 * (20.06).
 *
 * Endpoint dorong aman diulang: mutasi dikenali lewat KunciOperasi buatan
 * klien, jadi pengiriman ulang setelah koneksi putus di tengah jalan tidak
 * pernah menghasilkan transaksi bisnis kedua.
 */
final class AntrianSinkronisasiController extends Controller
{
    public function dorong(
        DorongAntrianSinkronisasiRequest $request,
        DaftarkanPerangkatPengguna $daftarkan,
        LayananAntrianSinkronisasi $layanan,
    ): JsonResponse {
        $pengguna = $request->user();
        $data = $request->validated();
        $perangkat = $daftarkan->jalankan($pengguna, $data);

        foreach ($data['Mutasi'] as $mutasi) {
            $layanan->antrikan($perangkat, $mutasi);
        }

        $layanan->prosesAntrean($perangkat, $pengguna);

        return response()->json([
            'Antrean' => AntrianSinkronisasiResource::collection(
                AntrianSinkronisasi::query()
                    ->where('PerangkatPenggunaId', $perangkat->Id)
                    ->orderBy('DiterimaPada')
                    ->get()
            )->resolve(),
        ]);
    }

    public function status(DaftarkanPerangkatRequest $request, DaftarkanPerangkatPengguna $daftarkan): JsonResponse
    {
        $perangkat = $daftarkan->jalankan($request->user(), $request->validated());

        return response()->json([
            'Antrean' => AntrianSinkronisasiResource::collection(
                AntrianSinkronisasi::query()
                    ->where('PerangkatPenggunaId', $perangkat->Id)
                    ->orderBy('DiterimaPada')
                    ->get()
            )->resolve(),
        ]);
    }

    public function selesaikanKonflik(
        Request $request,
        AntrianSinkronisasi $antrianSinkronisasi,
        LayananAntrianSinkronisasi $layanan,
    ): JsonResponse {
        $this->authorize('update', $antrianSinkronisasi);

        $data = $request->validate([
            'Keputusan' => ['required', Rule::enum(KeputusanKonflikSinkronisasi::class)],
        ]);

        $hasil = $layanan->selesaikanKonflik(
            $antrianSinkronisasi,
            KeputusanKonflikSinkronisasi::from($data['Keputusan']),
            $request->user(),
        );

        return response()->json(['Mutasi' => (new AntrianSinkronisasiResource($hasil))->resolve()]);
    }
}
