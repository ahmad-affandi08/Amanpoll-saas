<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Sumber kebenaran tunggal untuk "apakah tanggal ini hari libur". */
final class LayananKalenderKerja
{
    public function apakahHariLibur(string $organisasiId, CarbonInterface $tanggal, ?string $lokasiId = null): bool
    {
        $tanggalString = $tanggal->format('Y-m-d');

        $query = DB::table('HariLibur')
            ->where('OrganisasiId', $organisasiId)
            ->where(function ($query) use ($lokasiId): void {
                $query->whereNull('LokasiId');
                if ($lokasiId !== null) {
                    $query->orWhere('LokasiId', $lokasiId);
                }
            });

        $adaTanggalPersis = (clone $query)->where('BerulangTahunan', false)->where('Tanggal', $tanggalString)->exists();
        if ($adaTanggalPersis) {
            return true;
        }

        // Dibandingkan di PHP (bukan fungsi tanggal khusus dialek SQL).
        $bulanHari = $tanggal->format('m-d');
        $tanggalBerulang = (clone $query)->where('BerulangTahunan', true)->pluck('Tanggal');

        foreach ($tanggalBerulang as $tgl) {
            if (substr((string) $tgl, 5, 5) === $bulanHari) {
                return true;
            }
        }

        return false;
    }

    public function hariKerjaBerikutnya(string $organisasiId, CarbonInterface $dariTanggal, ?string $lokasiId = null): CarbonInterface
    {
        $tanggal = $dariTanggal->copy()->addDay();

        while ($tanggal->isWeekend() || $this->apakahHariLibur($organisasiId, $tanggal, $lokasiId)) {
            $tanggal = $tanggal->addDay();
        }

        return $tanggal;
    }
}
