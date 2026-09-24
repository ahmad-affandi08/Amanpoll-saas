<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Platform\Application\Actions\SimpanPenyediaLayanan;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Contracts\DapatDiujiKoneksi;
use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\IsianKredensial;
use App\Domain\Platform\Http\Requests\SimpanPenyediaLayananRequest;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Pengaturan payment gateway dan WhatsApp di konsol platform (PRD 8.23).
 *
 * Nilai isian rahasia tidak pernah dikirim ke peramban: halaman hanya menerima
 * penanda "tersimpan" dan empat karakter terakhirnya.
 */
final class PenyediaLayananPlatformController extends Controller
{
    public function index(KatalogPenyediaLayanan $katalog): Response
    {
        $tersimpan = PenyediaLayananPlatform::query()->get()
            ->keyBy(fn (PenyediaLayananPlatform $baris): string => $baris->Kategori->value.'|'.$baris->Kode);

        $kategori = array_map(
            fn (KategoriPenyediaLayanan $kategori): array => [
                'Kode' => $kategori->value,
                'Label' => $kategori->label(),
                'BolehBanyakAktif' => $kategori->bolehBanyakAktif(),
                'Penyedia' => array_map(
                    fn (DeskripsiPenyediaLayanan $penyedia): array => $this->ringkas(
                        $penyedia,
                        $tersimpan->get($kategori->value.'|'.$penyedia->kode()),
                    ),
                    $katalog->menurutKategori($kategori),
                ),
            ],
            KategoriPenyediaLayanan::cases(),
        );

        return Inertia::render('Platform/PenyediaLayanan/Index', [
            'kategori' => $kategori,
        ]);
    }

    public function simpan(
        SimpanPenyediaLayananRequest $request,
        string $kategori,
        string $kode,
        SimpanPenyediaLayanan $aksi,
    ): RedirectResponse {
        try {
            $aksi->jalankan(
                $this->kategori($kategori),
                $kode,
                $request->validated(),
                $request->user('platform')?->getAuthIdentifier(),
            );
        } catch (AturanBisnisDilanggar $e) {
            throw ValidationException::withMessages(['Kredensial' => $e->getMessage()]);
        }

        return back()->with('sukses', 'Pengaturan penyedia disimpan.');
    }

    /** Mencoba kredensial yang tersimpan, termasuk milik penyedia yang belum diaktifkan. */
    public function uji(Request $request, string $kategori, string $kode, KatalogPenyediaLayanan $katalog): JsonResponse
    {
        $jenis = $this->kategori($kategori);
        $penyedia = $katalog->untuk($jenis, $kode) ?? throw new DataTidakDitemukan("Penyedia {$kode} tidak dikenal.");

        if (! $penyedia instanceof DapatDiujiKoneksi) {
            return response()->json(['Berhasil' => false, 'Pesan' => 'Penyedia ini tidak menyediakan uji koneksi.'], 422);
        }

        $baris = PenyediaLayananPlatform::query()->where('Kategori', $jenis->value)->where('Kode', $kode)->first();

        if ($baris === null || $baris->nilaiKredensial() === []) {
            return response()->json(['Berhasil' => false, 'Pesan' => 'Simpan kredensialnya dulu sebelum diuji.'], 422);
        }

        try {
            $hasil = $penyedia->ujiKoneksi($baris->keKredensial());
        } catch (AturanBisnisDilanggar $e) {
            return response()->json(['Berhasil' => false, 'Pesan' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            // Pesan mentah bisa memuat URL berisi kredensial; yang dicatat hanya jenis galatnya.
            Log::warning('Uji koneksi penyedia gagal.', ['Kategori' => $jenis->value, 'Kode' => $kode, 'Galat' => $e::class]);

            return response()->json(['Berhasil' => false, 'Pesan' => 'Penyedia tidak dapat dihubungi. Coba lagi nanti.'], 422);
        }

        return response()->json(['Berhasil' => $hasil->berhasil, 'Pesan' => $hasil->pesan], $hasil->berhasil ? 200 : 422);
    }

    /** @return array<string, mixed> */
    private function ringkas(DeskripsiPenyediaLayanan $penyedia, ?PenyediaLayananPlatform $baris): array
    {
        $nilai = $baris?->nilaiKredensial() ?? [];

        return [
            'Kode' => $penyedia->kode(),
            'Nama' => $penyedia->nama(),
            'Keterangan' => $penyedia->keterangan(),
            'Resmi' => $penyedia->resmi(),
            'MendukungModeUji' => $penyedia->mendukungModeUji(),
            'DapatDiuji' => $penyedia instanceof DapatDiujiKoneksi,
            'Aktif' => (bool) ($baris->Aktif ?? false),
            'Utama' => (bool) ($baris->Utama ?? false),
            'ModeUji' => (bool) ($baris->ModeUji ?? true),
            'DiperbaruiPada' => $baris?->DiperbaruiPada?->toIso8601String(),
            'Isian' => array_map(
                fn (IsianKredensial $isian): array => [
                    ...$isian->keArray(),
                    'Nilai' => $isian->rahasia ? null : ($nilai[$isian->kunci] ?? $isian->bawaan ?? ''),
                    'Tersimpan' => ($nilai[$isian->kunci] ?? '') !== '',
                    'Akhiran' => $isian->rahasia && ($nilai[$isian->kunci] ?? '') !== ''
                        ? $this->akhiran($nilai[$isian->kunci])
                        : null,
                ],
                $penyedia->isian(),
            ),
        ];
    }

    /** Empat karakter terakhir, dan hanya bila rahasianya cukup panjang untuk tetap tak tertebak. */
    private function akhiran(string $rahasia): ?string
    {
        return mb_strlen($rahasia) >= 12 ? mb_substr($rahasia, -4) : null;
    }

    private function kategori(string $kode): KategoriPenyediaLayanan
    {
        return KategoriPenyediaLayanan::tryFrom($kode) ?? throw new DataTidakDitemukan('Kategori penyedia tidak dikenal.');
    }
}
