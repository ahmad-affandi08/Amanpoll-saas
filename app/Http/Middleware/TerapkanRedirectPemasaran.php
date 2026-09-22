<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menerapkan peta redirect situs publik (MARKETING.md 9).
 *
 * Dipasang pada grup host publik saja. Redirect adalah alat SEO untuk alamat
 * yang pernah diumumkan ke dunia luar; memberlakukannya pada host dashboard
 * berarti satu baris data dapat mengalihkan rute sistem.
 *
 * Berjalan setelah pengenal pengunjung ditetapkan, sehingga cookienya tetap
 * ikut terkirim pada respons pengalihan dan perjalanan pengunjung tidak putus
 * tepat di alamat lama yang sedang dipindahkan.
 */
final class TerapkanRedirectPemasaran
{
    public function __construct(private readonly PencariRedirectPemasaran $pencari) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $redirect = $this->pencari->cari($request->path());

        if ($redirect === null) {
            return $next($request);
        }

        $this->catatPemakaian($redirect);

        if ($redirect->Kode === KodeRedirect::Hilang) {
            abort(410);
        }

        return redirect()->away(
            (string) $redirect->Ke,
            $redirect->Kode->statusHttp(),
        );
    }

    /**
     * `DiperbaruiPada` disebut ulang dengan nilainya sendiri, bukan dibiarkan
     * terisi otomatis: kolomnya memakai `ON UPDATE CURRENT_TIMESTAMP`, sehingga
     * tanpa ini setiap kunjungan akan tampak seperti seseorang baru menyunting
     * aturannya — dan kolom itu yang dipakai untuk menemukan aturan yang sudah
     * lama tidak ditinjau.
     */
    private function catatPemakaian(RedirectPemasaran $redirect): void
    {
        DB::table('RedirectPemasaran')
            ->where('Id', $redirect->Id)
            ->update([
                'JumlahDipakai' => $redirect->JumlahDipakai + 1,
                'TerakhirDipakaiPada' => now(),
                'DiperbaruiPada' => $redirect->DiperbaruiPada,
            ]);
    }
}
