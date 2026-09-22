<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;

/** Menemukan siapa yang dimaksud satu peristiwa (MARKETING.md 17, 23). */
final class PenyusunKonteksOtomasi
{
    public function dariEvent(EventPemasaran $event): KonteksOtomasi
    {
        /** @var array<string, mixed> $data */
        $data = $event->DataTambahan ?? [];
        $prospek = $this->cariProspek($event, $data);

        return new KonteksOtomasi(
            prospek: $prospek,
            organisasiId: $event->OrganisasiId ?? $prospek?->OrganisasiId,
            event: $event,
            dataPeristiwa: $data,
        );
    }

    /** @param array<string, mixed> $data */
    private function cariProspek(EventPemasaran $event, array $data): ?Prospek
    {
        $id = $data['ProspekId'] ?? null;

        if (is_string($id) && $id !== '') {
            $prospek = Prospek::query()->find($id);

            if ($prospek !== null) {
                return $prospek;
            }
        }

        if ($event->PengenalPengunjung !== null) {
            $prospek = Prospek::query()
                ->where('PengenalPengunjung', $event->PengenalPengunjung)
                ->first();

            if ($prospek !== null) {
                return $prospek;
            }
        }

        if ($event->OrganisasiId === null) {
            return null;
        }

        return Prospek::query()->where('OrganisasiId', $event->OrganisasiId)->first();
    }
}
