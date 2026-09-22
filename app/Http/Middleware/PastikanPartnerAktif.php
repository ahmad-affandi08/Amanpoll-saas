<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Status partner diperiksa tiap permintaan, bukan hanya saat masuk.
 *
 * Sesi portal berumur panjang; tanpa ini penangguhan seorang partner baru
 * berlaku setelah sesinya kedaluwarsa sendiri (MARKETING.md 21).
 */
final class PastikanPartnerAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = $request->user('partner');

        if ($partner instanceof Partner && ! $partner->Status->bolehMasuk()) {
            Auth::guard('partner')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('partner.login');
        }

        return $next($request);
    }
}
