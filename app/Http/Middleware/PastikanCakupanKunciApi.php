<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Membatasi endpoint API key sesuai Cakupan yang ditetapkan saat kunci dibuat (mis. */
final class PastikanCakupanKunciApi
{
    public function handle(Request $request, Closure $next, string $cakupan): Response
    {
        $cakupanDiizinkan = $request->attributes->get('CakupanKunciApi', []);

        abort_unless(
            is_array($cakupanDiizinkan) && in_array($cakupan, $cakupanDiizinkan, true),
            403,
            'Kunci API tidak memiliki cakupan yang dibutuhkan.',
        );

        return $next($request);
    }
}
