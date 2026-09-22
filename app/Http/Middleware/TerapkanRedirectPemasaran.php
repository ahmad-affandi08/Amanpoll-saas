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

/** Menerapkan peta redirect situs publik (MARKETING.md 9). */
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

    /** `DiperbaruiPada` disebut ulang dengan nilainya sendiri, bukan dibiarkan terisi otomatis. */
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
