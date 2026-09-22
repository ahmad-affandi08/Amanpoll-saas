<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PartisipasiEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Menetapkan varian kepada pengunjung, sekali dan selamanya (MARKETING.md 22). */
final class PenetapVarianEksperimen
{
    public function untuk(EksperimenPemasaran $eksperimen, string $pengenalPengunjung): ?VarianEksperimen
    {
        // Yang sudah pernah ditetapkan selalu dijawab dari barisnya, apa pun status eksperimennya sekarang.
        $tercatat = $this->tercatat($eksperimen, $pengenalPengunjung);

        if ($tercatat !== null) {
            return $tercatat;
        }

        if (! $eksperimen->Status->menerimaPeserta()) {
            return null;
        }

        $varian = $this->pilih($eksperimen, $pengenalPengunjung);

        try {
            PartisipasiEksperimen::create([
                'EksperimenPemasaranId' => $eksperimen->Id,
                'VarianEksperimenId' => $varian->Id,
                'PengenalPengunjung' => $pengenalPengunjung,
                'DitetapkanPada' => CarbonImmutable::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua permintaan berbarengan; yang menang lebih dulu tetap yang berlaku.
            return $this->tercatat($eksperimen, $pengenalPengunjung);
        }

        return $varian;
    }

    public function tercatat(EksperimenPemasaran $eksperimen, string $pengenalPengunjung): ?VarianEksperimen
    {
        return PartisipasiEksperimen::query()
            ->where('EksperimenPemasaranId', $eksperimen->Id)
            ->where('PengenalPengunjung', $pengenalPengunjung)
            ->first()
            ?->varian;
    }

    /** Pemilihan pertama deterministik dari hash pengunjung, bukan acak. */
    public function pilih(EksperimenPemasaran $eksperimen, string $pengenalPengunjung): VarianEksperimen
    {
        // Pengunjung yang sama jatuh ke varian yang sama bahkan sebelum barisnya sempat tertulis.
        /** @var list<VarianEksperimen> $varian */
        $varian = $eksperimen->varian->all();

        if ($varian === []) {
            throw new AturanBisnisDilanggar("Eksperimen {$eksperimen->Kode} belum punya varian.");
        }

        $totalBobot = 0;

        foreach ($varian as $satu) {
            $totalBobot += max($satu->Bobot, 0);
        }

        if ($totalBobot < 1) {
            throw new AturanBisnisDilanggar("Seluruh varian eksperimen {$eksperimen->Kode} berbobot nol.");
        }

        $titik = $this->titik($eksperimen->Kode, $pengenalPengunjung, $totalBobot);
        $berjalan = 0;

        foreach ($varian as $satu) {
            $berjalan += max($satu->Bobot, 0);

            if ($titik < $berjalan) {
                return $satu;
            }
        }

        return $varian[array_key_last($varian)];
    }

    /** Titik pada roda bobot; kodenya ikut dihash agar satu pengunjung tidak selalu di varian pertama. */
    private function titik(string $kodeEksperimen, string $pengenalPengunjung, int $totalBobot): int
    {
        $sidik = hash('sha256', $kodeEksperimen.':'.$pengenalPengunjung);

        return (int) (hexdec(substr($sidik, 0, 8)) % $totalBobot);
    }
}
