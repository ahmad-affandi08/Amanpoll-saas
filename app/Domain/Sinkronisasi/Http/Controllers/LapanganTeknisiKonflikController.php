<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Domain\Sinkronisasi\Http\Resources\AntrianSinkronisasiResource;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Layar "Pilih versi" (DESIGN §36.6 layar 18) untuk satu mutasi offline yang konflik.
 *
 * Keputusannya dikirim klien ke `offline.antrian.konflik` lewat
 * `useSinkronisasiOffline().selesaikanKonflik` (FASE 20.06), yang mencatat
 * keputusan dan versi yang tidak dipilih di audit. Di sini hanya disusun
 * konteks kedua versi: siapa dan kapan mengubahnya di server.
 */
final class LapanganTeknisiKonflikController extends Controller
{
    public function __invoke(Request $request, string $kunci, PenyusunLayarTeknisi $penyusun): Response
    {
        $pengguna = $request->user('web');

        // Hanya antrean perangkat milik pengguna ini; milik orang lain sama dengan tidak ada.
        $antrian = AntrianSinkronisasi::query()
            ->where('KunciOperasi', $kunci)
            ->whereHas('perangkatPengguna', fn ($perangkat) => $perangkat->where('PenggunaId', $pengguna->Id))
            ->firstOrFail();
        $this->authorize('view', $antrian);

        $perintahKerja = $this->perintahKerja($antrian);
        $perubahanServer = $perintahKerja === null ? null : RiwayatStatusPerintahKerja::query()
            ->with('diubahOleh:Id,Nama,Jabatan')
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->orderByDesc('DiubahPada')
            ->orderByDesc('Id')
            ->first();

        if ($perintahKerja !== null) {
            $perintahKerja = $penyusun->muatRelasi(PerintahKerja::query()->whereKey($perintahKerja->Id), $pengguna)->first();
        }

        return Inertia::render('Lapangan/Teknisi/Konflik', [
            'antrian' => (new AntrianSinkronisasiResource($antrian))->resolve(),
            'tiket' => $perintahKerja === null ? null : $penyusun->ringkas($perintahKerja, $pengguna),
            'perubahanServer' => $perubahanServer === null ? null : [
                'Status' => $perubahanServer->StatusSesudah,
                'Catatan' => $perubahanServer->Catatan,
                'Oleh' => $perubahanServer->diubahOleh?->Nama,
                'Jabatan' => $perubahanServer->diubahOleh?->Jabatan,
                'Pada' => $perubahanServer->DiubahPada->toIso8601String(),
            ],
        ]);
    }

    private function perintahKerja(AntrianSinkronisasi $antrian): ?PerintahKerja
    {
        if ($antrian->EntitasId === null) {
            return null;
        }

        if ($antrian->JenisEntitas === 'PelaksanaanDaftarPeriksa') {
            $perintahKerjaId = PelaksanaanDaftarPeriksa::query()->whereKey($antrian->EntitasId)->value('PerintahKerjaId');

            return is_string($perintahKerjaId) ? PerintahKerja::query()->find($perintahKerjaId) : null;
        }

        return $antrian->JenisEntitas === 'PerintahKerja' ? PerintahKerja::query()->find($antrian->EntitasId) : null;
    }
}
