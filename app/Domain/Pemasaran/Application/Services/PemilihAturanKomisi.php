<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use Carbon\CarbonImmutable;

/** Memilih satu aturan komisi yang berlaku: yang khusus partner mengalahkan bawaan programnya (MARKETING.md 21). */
final class PemilihAturanKomisi
{
    public function untuk(Partner $partner, CarbonImmutable $saat): ?AturanKomisiPartner
    {
        $kandidat = AturanKomisiPartner::query()
            ->where('ProgramPartnerId', $partner->ProgramPartnerId)
            ->where(function ($kueri) use ($partner): void {
                $kueri->whereNull('PartnerId')->orWhere('PartnerId', $partner->Id);
            })
            ->where('Aktif', true)
            ->orderByDesc('DibuatPada')
            ->get();

        $berlaku = $kandidat->filter(fn (AturanKomisiPartner $satu): bool => $satu->berlakuPada($saat));

        return $berlaku->firstWhere('PartnerId', $partner->Id) ?? $berlaku->first();
    }
}
