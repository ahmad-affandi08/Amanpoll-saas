<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Idempotensi\LayananIdempotensi;
use App\Core\Organisasi\KonteksOrganisasi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menjaga endpoint tulis agar aman diulang. */
final class PastikanIdempoten
{
    public function __construct(
        private readonly LayananIdempotensi $layanan,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function handle(Request $request, Closure $next, string $wajib = 'wajib'): Response
    {
        $kunci = trim((string) $request->header(LayananIdempotensi::HEADER, ''));

        if ($kunci === '') {
            abort_if($wajib === 'wajib', 400, 'Header '.LayananIdempotensi::HEADER.' wajib diisi.');

            return $next($request);
        }

        $organisasiId = $this->konteks->ada() ? $this->konteks->wajibId() : null;
        $rute = $this->layanan->rute($request);
        $sidikJari = $this->layanan->sidikJari($request);

        $sebelumnya = $this->layanan->daftarkan($organisasiId, $kunci, $rute, $sidikJari);

        if ($sebelumnya !== null) {
            // Permintaan pertama masih berjalan bila responsnya belum tersimpan.
            abort_if($sebelumnya->StatusHttp === null, 409, 'Permintaan dengan kunci idempotensi ini sedang diproses.');

            return response(
                (string) $sebelumnya->Respons,
                $sebelumnya->StatusHttp,
                ['Content-Type' => 'application/json', 'Idempotency-Replayed' => 'true'],
            );
        }

        $respons = $next($request);
        $this->layanan->simpanRespons($organisasiId, $kunci, $rute, $respons);

        return $respons;
    }
}
