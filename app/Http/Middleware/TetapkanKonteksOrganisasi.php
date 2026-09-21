<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Organisasi\KonteksOrganisasi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TetapkanKonteksOrganisasi
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user('web');

        if ($pengguna?->OrganisasiId) {
            $this->konteks->tetapkan((string) $pengguna->OrganisasiId);
        }

        try {
            return $next($request);
        } finally {
            $this->konteks->bersihkan();
        }
    }
}
