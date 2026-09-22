<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Actions;

use App\Domain\Pemasaran\Application\Services\PenyusunKonteksOtomasi;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Melahirkan eksekusi dari satu peristiwa; indeks unik yang menahan peristiwa sama melahirkan yang kedua (MARKETING.md 17). */
final class MulaiEksekusiOtomasi
{
    public function __construct(private readonly PenyusunKonteksOtomasi $penyusun) {}

    /** @return list<EksekusiOtomasiPemasaran> */
    public function dariEvent(EventPemasaran $event): array
    {
        $pemicu = KatalogPemicuOtomasi::pemicuUntukPeristiwa((string) $event->Jenis);

        if ($pemicu === []) {
            return [];
        }

        $otomasi = OtomasiPemasaran::query()
            ->whereIn('Pemicu', $pemicu)
            ->where('Aktif', true)
            ->whereNotNull('VersiAktifId')
            ->get();

        $lahir = [];

        foreach ($otomasi as $satu) {
            $eksekusi = $this->untuk($satu, $event);

            if ($eksekusi !== null) {
                $lahir[] = $eksekusi;
            }
        }

        return $lahir;
    }

    public function untuk(OtomasiPemasaran $otomasi, EventPemasaran $event): ?EksekusiOtomasiPemasaran
    {
        if (! $otomasi->siapJalan()) {
            return null;
        }

        $konteks = $this->penyusun->dariEvent($event);
        $kunci = "otomasi:{$otomasi->VersiAktifId}:event:{$event->Id}";

        try {
            return EksekusiOtomasiPemasaran::create([
                'OtomasiPemasaranId' => $otomasi->Id,
                'VersiOtomasiPemasaranId' => $otomasi->VersiAktifId,
                'EventPemasaranId' => $event->Id,
                'ProspekId' => $konteks->prospek?->Id,
                'OrganisasiId' => $konteks->organisasiId,
                'KunciIdempotensi' => $kunci,
                'Status' => StatusEksekusiOtomasi::Berjalan,
                'LangkahBerikutnya' => 0,
                'Percobaan' => 0,
                'DimulaiPada' => CarbonImmutable::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Sudah pernah lahir dari peristiwa ini; itu justru hasil yang diinginkan.
            return EksekusiOtomasiPemasaran::query()->where('KunciIdempotensi', $kunci)->first();
        }
    }
}
