<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Host\PetaHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Hanya host publik yang boleh diindeks (MARKETING.md 1.2). */
final class TandaiHostTidakTerindeks
{
    public function __construct(private readonly PetaHost $host) {}

    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        if (! $this->host->adalahHostPublik($request->getHost())) {
            $respons->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $respons;
    }
}
