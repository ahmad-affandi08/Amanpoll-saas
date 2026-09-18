<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Organisasi\KonteksOrganisasi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class AutentikasiKunciApi
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && str_contains($token, '.'), 401, 'Kunci API tidak valid.');

        [$prefix] = explode('.', $token, 2);
        $kunci = DB::table('KunciApi')
            ->where('AwalanKunci', $prefix)
            ->where('Status', 'Aktif')
            ->first();

        abort_unless($kunci, 401, 'Kunci API tidak valid.');
        abort_if($kunci->KadaluarsaPada && now()->greaterThan($kunci->KadaluarsaPada), 401, 'Kunci API kedaluwarsa.');
        abort_unless(hash_equals((string) $kunci->HashKunci, hash('sha256', $token)), 401, 'Kunci API tidak valid.');

        $ipDiizinkan = $kunci->AlamatIpDiizinkan ? json_decode((string) $kunci->AlamatIpDiizinkan, true) : null;
        if (is_array($ipDiizinkan) && $ipDiizinkan !== []) {
            abort_unless(in_array($request->ip(), $ipDiizinkan, true), 403, 'Alamat IP tidak diizinkan.');
        }

        $this->konteks->tetapkan((string) $kunci->OrganisasiId);
        $request->attributes->set('KunciApiId', (string) $kunci->Id);
        $request->attributes->set('CakupanKunciApi', $kunci->Cakupan ? json_decode((string) $kunci->Cakupan, true) : []);

        try {
            return $next($request);
        } finally {
            $this->konteks->bersihkan();
        }
    }
}
