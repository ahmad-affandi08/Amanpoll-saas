<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Audit\KorelasiId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Satu ID korelasi per request, dipakai LayananAudit supaya beberapa baris
 * CatatanAudit yang berasal dari satu aksi pengguna (mis. operasi massal)
 * bisa dikaitkan tanpa perlu passing manual di setiap pemanggilan.
 */
final class TetapkanKorelasiId
{
    public function handle(Request $request, Closure $next): Response
    {
        $nilai = $request->header('X-Korelasi-Id') ?: (string) Str::ulid();

        app(KorelasiId::class)->tetapkan($nilai);

        return $next($request);
    }
}
