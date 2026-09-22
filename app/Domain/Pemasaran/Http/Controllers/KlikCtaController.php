<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Controllers;

use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Klik CTA di situs publik. Tautannya tetap tautan biasa; endpoint ini hanya
 * mencatat bahwa ia diklik, dan kegagalannya tidak boleh menahan navigasi
 * pengunjung (MARKETING.md 23).
 */
final class KlikCtaController extends Controller
{
    public function __construct(
        private readonly PerekamEventPemasaran $event,
        private readonly PerangkapSpam $perangkap,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array{Label?: string|null, Tujuan?: string|null, Sumber?: string|null} $sah */
        $sah = $request->validate([
            'Label' => ['nullable', 'string', 'max:120'],
            'Tujuan' => ['nullable', 'string', 'max:500'],
            'Sumber' => ['nullable', 'string', 'max:190'],
            PerangkapSpam::FIELD => ['nullable', 'string', 'max:190'],
        ]);

        if ($this->perangkap->terperangkap($request->all())) {
            return response()->json(['tercatat' => false]);
        }

        $pengenal = $request->attributes->get('pengenalPengunjung');

        $this->event->catat(
            KatalogPeristiwaPemasaran::CTA_DIKLIK,
            pengenalPengunjung: is_string($pengenal) ? $pengenal : null,
            url: $request->headers->get('referer'),
            dataTambahan: [
                'Label' => $sah['Label'] ?? null,
                'Tujuan' => $sah['Tujuan'] ?? null,
                'Sumber' => $sah['Sumber'] ?? null,
            ],
        );

        return response()->json(['tercatat' => true]);
    }
}
