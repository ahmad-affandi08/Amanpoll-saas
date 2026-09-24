<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Controllers;

use App\Domain\Notifikasi\Application\Actions\KirimEmailUjiPenyedia;
use App\Domain\Notifikasi\Domain\Contracts\PenyediaEmail;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;
use App\Http\Controllers\Controller;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Tombol "Kirim email uji" di konsol penyedia layanan platform (PRD 8.23). */
final class EmailUjiPenyediaController extends Controller
{
    /** Mengirim email uji ke admin yang sedang masuk memakai kredensial tersimpan, walau penyedianya belum aktif. */
    public function kirim(
        Request $request,
        string $kode,
        KatalogPenyediaLayanan $katalog,
        KirimEmailUjiPenyedia $aksi,
    ): JsonResponse {
        $penyedia = $katalog->untuk(KategoriPenyediaLayanan::Email, $kode);

        if (! $penyedia instanceof PenyediaEmail) {
            throw new DataTidakDitemukan("Penyedia email {$kode} tidak dikenal.");
        }

        $baris = PenyediaLayananPlatform::query()
            ->where('Kategori', KategoriPenyediaLayanan::Email->value)
            ->where('Kode', $kode)
            ->first();

        if ($baris === null || $baris->nilaiKredensial() === []) {
            return response()->json(['Berhasil' => false, 'Pesan' => 'Simpan kredensialnya dulu sebelum mengirim email uji.'], 422);
        }

        $admin = $request->user('platform');
        $tujuan = $admin instanceof AdminPlatform ? (string) $admin->Email : '';

        if ($tujuan === '') {
            return response()->json(['Berhasil' => false, 'Pesan' => 'Akun Anda tidak memiliki alamat email tujuan.'], 422);
        }

        try {
            $hasil = $aksi->jalankan($penyedia, $baris->keKredensial(), $tujuan);
        } catch (Throwable $galat) {
            // Pesan mentah bisa memuat kredensial; yang dicatat hanya jenis galatnya.
            Log::warning('Kirim email uji penyedia gagal.', ['Kode' => $kode, 'Galat' => $galat::class]);

            return response()->json(['Berhasil' => false, 'Pesan' => 'Penyedia email tidak dapat dihubungi. Coba lagi nanti.'], 422);
        }

        return response()->json(['Berhasil' => $hasil->berhasil, 'Pesan' => $hasil->pesan], $hasil->berhasil ? 200 : 422);
    }
}
