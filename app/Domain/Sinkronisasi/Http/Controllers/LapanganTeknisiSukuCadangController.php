<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Controllers;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Sinkronisasi\Application\Services\PenyusunLayarTeknisi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suku cadang di Mode Lapangan teknisi (DESIGN §36.6 layar 09, PRD 8.20).
 *
 * Teknisi hanya MEMINTA: permintaannya dikirim layar ke endpoint reservasi
 * dasbor (`pemeliharaan.perintah-kerja.reservasi`, policy `operate`), yang
 * menahan stok tanpa mengurangi jumlah tersedia. Stok baru berkurang saat
 * gudang menyerahkan barang. Jalur "Pakai" (`GunakanSukuCadangPerintahKerja`)
 * sengaja tidak pernah ditawarkan di sini.
 */
final class LapanganTeknisiSukuCadangController extends Controller
{
    /** Hasil pencarian di lembar bawah; lebih dari ini teknisi perlu kata kunci lebih tepat. */
    private const BATAS_HASIL = 20;

    /** Permintaan yang ditampilkan di layar "Suku cadang saya". */
    private const BATAS_PERMINTAAN = 50;

    /** Permintaan suku cadang untuk tiket yang sedang ditugaskan kepada teknisi ini. */
    public function index(Request $request, PenyusunLayarTeknisi $penyusun): Response
    {
        $pengguna = $request->user('web');
        $tiketId = $penyusun->kueriDitugaskan($pengguna)->pluck('Id');

        return Inertia::render('Lapangan/Teknisi/SukuCadang', [
            'permintaan' => array_values(ReservasiSukuCadang::query()
                ->with(['sukuCadang:Id,Kode,Nama,SatuanDasar', 'gudang:Id,Nama', 'perintahKerja:Id,Nomor,Judul'])
                ->whereIn('PerintahKerjaId', $tiketId)
                ->orderByDesc('DibuatPada')
                ->orderBy('Id')
                ->limit(self::BATAS_PERMINTAAN)
                ->get()
                ->map(fn (ReservasiSukuCadang $reservasi): array => [
                    'Id' => $reservasi->Id,
                    'Jumlah' => (float) $reservasi->Jumlah,
                    'Status' => $reservasi->Status,
                    'DibuatPada' => $reservasi->DibuatPada->toIso8601String(),
                    'NamaSukuCadang' => $reservasi->sukuCadang?->Nama,
                    'KodeSukuCadang' => $reservasi->sukuCadang?->Kode,
                    'Satuan' => $reservasi->sukuCadang?->SatuanDasar,
                    'NamaGudang' => $reservasi->gudang?->Nama,
                    'PerintahKerjaId' => $reservasi->PerintahKerjaId,
                    'NomorTiket' => $reservasi->perintahKerja?->Nomor,
                    'JudulTiket' => $reservasi->perintahKerja?->Judul,
                ])
                ->all()),
        ]);
    }

    /**
     * Cari suku cadang beserta stok bersih per gudang untuk satu tiket.
     *
     * Sama dengan data stok yang diberikan halaman tiket dasbor kepada teknisi
     * yang ditugaskan: dijaga `operate` pada tiketnya, bukan `Stok.Kelola`.
     */
    public function cari(Request $request, PerintahKerja $perintahKerja): JsonResponse
    {
        $this->authorize('operate', $perintahKerja);
        $kata = trim((string) $request->query('cari', ''));

        $sukuCadang = SukuCadang::query()
            ->where('Status', StatusSukuCadang::Aktif->value)
            ->when($kata !== '', function ($kueri) use ($kata): void {
                $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $kata).'%';
                $kueri->where(fn ($dalam) => $dalam
                    ->where('Nama', 'like', $pola)
                    ->orWhere('Kode', 'like', $pola)
                    ->orWhere('NomorBagian', 'like', $pola));
            })
            ->orderBy('Nama')
            ->orderBy('Id')
            ->limit(self::BATAS_HASIL)
            ->get(['Id', 'Kode', 'Nama', 'NomorBagian', 'SatuanDasar']);

        $stok = StokSukuCadang::query()
            ->with('gudang:Id,Nama')
            ->whereIn('SukuCadangId', $sukuCadang->pluck('Id'))
            ->get()
            // Gudang di luar lingkup pengguna tidak termuat relasinya; stoknya tidak ditampilkan.
            ->filter(fn (StokSukuCadang $baris): bool => $baris->gudang !== null)
            ->groupBy('SukuCadangId');

        return response()->json([
            'data' => array_values($sukuCadang->map(fn (SukuCadang $satu): array => [
                'Id' => $satu->Id,
                'Kode' => $satu->Kode,
                'Nama' => $satu->Nama,
                'NomorBagian' => $satu->NomorBagian,
                'Satuan' => $satu->SatuanDasar,
                'Stok' => array_values(($stok[$satu->Id] ?? collect())
                    ->groupBy('GudangId')
                    ->map(fn ($baris): array => [
                        'GudangId' => (string) $baris->first()?->GudangId,
                        'NamaGudang' => (string) $baris->first()?->gudang?->Nama,
                        'TersediaBersih' => max(0.0, (float) $baris->sum(fn (StokSukuCadang $b): float => $b->jumlahTersediaBersih())),
                    ])
                    ->sortByDesc('TersediaBersih')
                    ->all()),
            ])->all()),
        ]);
    }
}
