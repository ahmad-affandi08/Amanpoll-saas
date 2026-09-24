<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Pemeliharaan\Application\Actions\KonfirmasiPenyelesaianKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Http\Policies\PerintahKerjaPolicy;
use App\Domain\Pemeliharaan\Http\Requests\KonfirmasiPenyelesaianKeluhanRequest;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarPelapor;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\VersiDataBerubah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Konfirmasi selesai dan layar terima kasih (DESIGN §36.7 layar 12–13).
 * Aturannya milik Pemeliharaan: `KeluhanPolicy::konfirmasi` dan
 * `KonfirmasiPenyelesaianKeluhan`.
 */
final class LapanganPelaporKonfirmasiController extends Controller
{
    /** Batas foto Sesudah di galeri konfirmasi. */
    private const MAKS_FOTO_SESUDAH = 6;

    public function create(Request $request, Keluhan $keluhan, PenyusunLayarPelapor $penyusun): Response|RedirectResponse
    {
        $pengguna = $request->user('web');
        $this->pastikanMilikSendiri($keluhan, $pengguna);

        // Tautan lama (notifikasi, beranda) ke keluhan yang sudah dikonfirmasi atau dibuka lagi.
        if (! $pengguna->can('konfirmasi', $keluhan)) {
            return redirect()->route('lapangan.pelapor.laporan.show', $keluhan);
        }

        $keluhan->load(['aset.kategoriAset', 'kategoriKeluhan', 'lokasi']);

        return Inertia::render('Lapangan/Pelapor/Konfirmasi', [
            'laporan' => [
                ...$penyusun->ringkasLaporan($keluhan),
                'Teknisi' => $penyusun->teknisiUntuk([$keluhan->Id])[$keluhan->Id] ?? null,
            ],
            'foto' => $this->fotoKeluhan($keluhan),
            'fotoSesudah' => $this->fotoSesudah($keluhan),
        ]);
    }

    public function store(
        KonfirmasiPenyelesaianKeluhanRequest $request,
        Keluhan $keluhan,
        KonfirmasiPenyelesaianKeluhan $aksi,
    ): RedirectResponse {
        $pengguna = $request->user('web');
        $this->pastikanMilikSendiri($keluhan, $pengguna);
        $this->authorize('konfirmasi', $keluhan);

        $beres = $request->boolean('Beres');

        try {
            $aksi->jalankan(
                $keluhan,
                $beres,
                $beres ? $request->integer('Rating') : null,
                $request->validated('Ulasan'),
                $request->integer('Versi'),
                $pengguna->Id,
            );
        } catch (VersiDataBerubah $e) {
            return back()->with('gagal', $e->getMessage());
        }

        return $beres
            ? redirect()->route('lapangan.pelapor.laporan.terima-kasih', $keluhan)
            : redirect()->route('lapangan.pelapor.laporan.show', $keluhan)
                ->with('sukses', 'Terima kasih. Laporanmu dibuka lagi dan tim teknik akan memeriksanya.');
    }

    public function terimaKasih(Request $request, Keluhan $keluhan, PenyusunLayarPelapor $penyusun): Response|RedirectResponse
    {
        $this->pastikanMilikSendiri($keluhan, $request->user('web'));

        if ($keluhan->Status !== StatusKeluhan::Ditutup->value) {
            return redirect()->route('lapangan.pelapor.laporan.show', $keluhan);
        }

        $keluhan->load(['aset.kategoriAset', 'kategoriKeluhan', 'lokasi']);

        return Inertia::render('Lapangan/Pelapor/TerimaKasih', [
            'laporan' => $penyusun->ringkasLaporan($keluhan),
        ]);
    }

    private function pastikanMilikSendiri(Keluhan $keluhan, Pengguna $pengguna): void
    {
        $this->authorize('view', $keluhan);
        abort_if($keluhan->PelaporId !== $pengguna->Id, 404);
    }

    /**
     * Foto yang terlampir pada keluhan ini (dari pelapor atau yang dilampirkan
     * tim teknik ke keluhannya). Foto perintah kerja ada di `fotoSesudah()`.
     *
     * @return list<array{BerkasId: string, Kategori: string|null, Nama: string|null}>
     */
    private function fotoKeluhan(Keluhan $keluhan): array
    {
        return array_values(LampiranEntitas::query()
            ->with('berkas')
            ->where('JenisEntitas', 'Keluhan')
            ->where('EntitasId', $keluhan->Id)
            ->oldest('DibuatPada')
            ->limit(4)
            ->get()
            ->filter(fn (LampiranEntitas $satu): bool => str_starts_with((string) $satu->berkas?->JenisMime, 'image/'))
            ->map(fn (LampiranEntitas $satu): array => [
                'BerkasId' => (string) $satu->BerkasId,
                'Kategori' => $satu->Kategori,
                'Nama' => $satu->berkas?->NamaAsli,
            ])
            ->all());
    }

    /**
     * Foto Sesudah dari teknisi pada perintah kerja yang berasal dari keluhan ini
     * (PRD 8.20). Hanya kategori itu; lampiran lain di perintah kerja tetap milik
     * teknisi dan koordinator. Unduhannya lolos `PerintahKerjaPolicy::lihatLampiran`.
     *
     * @return list<array{BerkasId: string, Nama: string|null}>
     */
    private function fotoSesudah(Keluhan $keluhan): array
    {
        $perintahKerjaId = PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->pluck('Id');

        if ($perintahKerjaId->isEmpty()) {
            return [];
        }

        return array_values(LampiranEntitas::query()
            ->with('berkas')
            ->where('JenisEntitas', 'PerintahKerja')
            ->whereIn('EntitasId', $perintahKerjaId)
            ->where('Kategori', PerintahKerjaPolicy::KATEGORI_FOTO_SESUDAH)
            ->oldest('DibuatPada')
            ->orderBy('Id')
            ->limit(self::MAKS_FOTO_SESUDAH)
            ->get()
            ->filter(fn (LampiranEntitas $satu): bool => str_starts_with((string) $satu->berkas?->JenisMime, 'image/'))
            ->map(fn (LampiranEntitas $satu): array => [
                'BerkasId' => (string) $satu->BerkasId,
                'Nama' => $satu->berkas?->NamaAsli,
            ])
            ->values()
            ->all());
    }
}
