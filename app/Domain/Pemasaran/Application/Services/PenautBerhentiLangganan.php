<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use Illuminate\Support\Facades\URL;

/** Tautan berhenti langganan: bertanda tangan agar tidak dapat ditebak, tanpa kedaluwarsa agar email lama tetap berguna (MARKETING.md 27). */
final class PenautBerhentiLangganan
{
    public function __construct(private readonly PetaHost $host) {}

    public function untuk(PengirimanEmailPemasaran $pengiriman): ?string
    {
        if (! $this->host->situsPublikAktif()) {
            return null;
        }

        return URL::signedRoute('publik.berhenti-langganan', ['pengiriman' => $pengiriman->Id]);
    }
}
