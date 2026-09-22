<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;

/** Timeline gabungan satu prospek (MARKETING.md 7). */
final class PenyusunTimelineProspek
{
    private const BATAS = 200;

    /** @return list<array<string, mixed>> */
    public function untuk(Prospek $prospek): array
    {
        $entri = [
            ...$this->dariPeristiwa($prospek),
            ...$this->dariAktivitas($prospek),
        ];

        usort($entri, fn (array $a, array $b): int => strcmp((string) $b['Pada'], (string) $a['Pada']));

        return array_slice($entri, 0, self::BATAS);
    }

    /** @return list<array<string, mixed>> */
    private function dariPeristiwa(Prospek $prospek): array
    {
        if ($prospek->PengenalPengunjung === null && $prospek->OrganisasiId === null) {
            return [];
        }

        // Dua kunci, satu orang: sebelum ia menjadi tenant dan sesudahnya.
        return array_values(EventPemasaran::query()
            ->where(function ($q) use ($prospek): void {
                if ($prospek->PengenalPengunjung !== null) {
                    $q->orWhere('PengenalPengunjung', $prospek->PengenalPengunjung);
                }

                if ($prospek->OrganisasiId !== null) {
                    $q->orWhere('OrganisasiId', $prospek->OrganisasiId);
                }
            })
            ->orderByDesc('TerjadiPada')
            ->limit(self::BATAS)
            ->get()
            ->map(fn (EventPemasaran $satu): array => [
                'Sumber' => 'Peristiwa',
                'Jenis' => $satu->Jenis,
                'Judul' => $satu->Jenis,
                'Isi' => $satu->Url,
                'Pada' => $satu->TerjadiPada->toIso8601String(),
            ])
            ->all());
    }

    /** @return list<array<string, mixed>> */
    private function dariAktivitas(Prospek $prospek): array
    {
        return array_values(AktivitasProspek::query()
            ->where('ProspekId', $prospek->Id)
            ->orderByDesc('TerjadiPada')
            ->limit(self::BATAS)
            ->get()
            ->map(fn (AktivitasProspek $satu): array => [
                'Sumber' => 'Aktivitas',
                'Jenis' => $satu->Jenis,
                'Judul' => $satu->Judul,
                'Isi' => $satu->Isi,
                'Pada' => $satu->TerjadiPada->toIso8601String(),
            ])
            ->all());
    }
}
