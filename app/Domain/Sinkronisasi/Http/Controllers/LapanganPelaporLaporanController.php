<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laporan Saya, Lacak laporan, dan Laporan terkirim (DESIGN §36.7 layar 08–10).
 *
 * Pelapor hanya pernah melihat keluhannya sendiri: daftar disaring ke
 * `PelaporId`, dan detail memakai `KeluhanPolicy::view` lalu menolak keluhan
 * orang lain dengan 404 — pemegang `Keluhan.Kelola` pun membuka keluhan orang
 * lain di dasbor, bukan di layar pelapor.
 */
final class LapanganPelaporLaporanController extends Controller
{
    /** Batas tiket di Laporan Saya; laporan yang lebih lama ada di dasbor. */
    private const MAKS_LAPORAN = 100;

    public function index(Request $request, PenyusunLayarPelapor $penyusun): Response
    {
        $pengguna = $request->user('web');
        $kueri = Keluhan::query()->where('PelaporId', $pengguna->Id);

        $jumlah = (clone $kueri)
            ->selectRaw('Status, COUNT(*) as Jumlah')
            ->groupBy('Status')
            ->pluck('Jumlah', 'Status');
        $hitung = fn (array $status): int => (int) collect($status)->sum(fn (StatusKeluhan $satu): int => (int) ($jumlah[$satu->value] ?? 0));

        $laporan = (clone $kueri)
            ->latest('DilaporkanPada')
            ->orderBy('Id')
            ->limit(self::MAKS_LAPORAN)
            ->get();

        return Inertia::render('Lapangan/Pelapor/Laporan', [
            'lokasi' => $penyusun->ringkasLokasi($penyusun->lokasiSaya($pengguna)),
            'laporan' => $penyusun->ringkasDaftarLaporan($laporan),
            'jumlah' => [
                // Yang menunggu konfirmasi masih aktif bagi pelapor (papan pelapor layar 09).
                'Aktif' => $hitung([...PenyusunLayarPelapor::STATUS_TERBUKA, StatusKeluhan::Selesai]),
                'PerluKonfirmasi' => $hitung([StatusKeluhan::Selesai]),
                'Selesai' => $hitung([StatusKeluhan::Ditutup, StatusKeluhan::Ditolak, StatusKeluhan::Dibatalkan]),
            ],
        ]);
    }

    public function show(Request $request, Keluhan $keluhan, PenyusunLayarPelapor $penyusun): Response
    {
        $pengguna = $request->user('web');
        $this->pastikanMilikSendiri($keluhan, $pengguna);

        $keluhan->load(['aset.kategoriAset', 'kategoriKeluhan', 'lokasi']);

        return Inertia::render('Lapangan/Pelapor/Lacak', [
            'laporan' => [
                ...$penyusun->ringkasLaporan($keluhan),
                'Teknisi' => $penyusun->teknisiUntuk([$keluhan->Id])[$keluhan->Id] ?? null,
                'LokasiLabel' => $penyusun->labelLokasi($penyusun->cariLokasi($keluhan->LokasiId)),
            ],
            'riwayat' => $penyusun->riwayat($keluhan, $pengguna),
            'jumlahFoto' => LampiranEntitas::query()
                ->where('JenisEntitas', 'Keluhan')
                ->where('EntitasId', $keluhan->Id)
                ->count(),
        ]);
    }

    public function terkirim(Request $request, Keluhan $keluhan, PenyusunLayarPelapor $penyusun): Response
    {
        $this->pastikanMilikSendiri($keluhan, $request->user('web'));
        $keluhan->load(['aset.kategoriAset', 'kategoriKeluhan', 'lokasi']);

        return Inertia::render('Lapangan/Pelapor/Terkirim', [
            'laporan' => $penyusun->ringkasLaporan($keluhan),
        ]);
    }

    private function pastikanMilikSendiri(Keluhan $keluhan, Pengguna $pengguna): void
    {
        $this->authorize('view', $keluhan);
        abort_if($keluhan->PelaporId !== $pengguna->Id, 404);
    }
}
