<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Audit\LayananCatatanAkses;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup sesi yang pemiliknya sudah tidak berhak masuk (24, PRD 6).
 *
 * Status diperiksa saat masuk, tetapi sesi berumur panjang: tanpa pemeriksaan
 * ulang per permintaan, menonaktifkan pengguna atau organisasi baru berlaku
 * setelah sesinya kedaluwarsa — dan `IngatSaya` membuat tenggang itu berbulan.
 * PemeriksaIzin pun hanya membaca peran, bukan status, sehingga akun yang sudah
 * dinonaktifkan tetap lolos seluruh gerbang izin.
 */
final class PastikanAkunMasihAktif
{
    private const STATUS_AKTIF = 'Aktif';

    public function __construct(private readonly LayananCatatanAkses $catatanAkses) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user('web');

        if ($pengguna === null) {
            return $next($request);
        }

        if ($pengguna->Status !== self::STATUS_AKTIF) {
            return $this->akhiriSesi($request, 'Akun dinonaktifkan.');
        }

        if (! $this->organisasiAktif((string) $pengguna->OrganisasiId)) {
            return $this->akhiriSesi($request, 'Organisasi dinonaktifkan.');
        }

        return $next($request);
    }

    private function organisasiAktif(string $organisasiId): bool
    {
        $status = Organisasi::query()
            ->whereKey($organisasiId)
            ->value('Status');

        return $status === self::STATUS_AKTIF;
    }

    /**
     * Sesi dibuang seluruhnya, bukan sekadar ditolak, supaya cookie yang
     * tertinggal tidak dapat dipakai lagi bila statusnya sempat dipulihkan.
     */
    private function akhiriSesi(Request $request, string $alasan): Response
    {
        $penggunaId = (string) $request->user('web')->Id;
        $organisasiId = (string) $request->user('web')->OrganisasiId;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->catatanAkses->catat('SesiDiakhiri', $organisasiId, $penggunaId, false, $alasan);

        if ($request->expectsJson()) {
            return response()->json(['pesan' => $alasan], 401);
        }

        return redirect()->route('login')->with('galat', $alasan);
    }
}
