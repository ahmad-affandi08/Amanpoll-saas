<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Audit\LayananAudit;
use App\Domain\Pemasaran\Domain\Enums\StatusEksperimen;
use App\Domain\Pemasaran\Domain\ValueObjects\PenilaianEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Pemenang tidak pernah dinyatakan sebelum tiap varian mencapai sampel minimumnya (MARKETING.md 22). */
final class PenilaiEksperimen
{
    public const BELUM_CUKUP_SAMPEL = 'Sampel minimum belum tercapai di seluruh varian.';

    public const TIDAK_ADA_SELISIH = 'Metrik utama seluruh varian sama; tidak ada yang menang.';

    public function __construct(
        private readonly PenghitungHasilEksperimen $penghitung,
        private readonly LayananAudit $audit,
    ) {}

    public function nilai(EksperimenPemasaran $eksperimen): PenilaianEksperimen
    {
        $this->penghitung->hitung($eksperimen);

        /** @var list<VarianEksperimen> $varian */
        $varian = $eksperimen->varian()->get()->all();

        if (count($varian) < 2) {
            return new PenilaianEksperimen(false, null, 'Eksperimen memerlukan setidaknya dua varian.');
        }

        $peserta = [];

        foreach ($varian as $satu) {
            $peserta[$satu->Id] = $this->penghitung->peserta($satu);
        }

        // Yang dibandingkan hanyalah varian yang sudah cukup sampel; satu saja kurang, tidak ada pemenang.
        if (min($peserta) < $eksperimen->MinimumSampel) {
            return new PenilaianEksperimen(false, null, self::BELUM_CUKUP_SAMPEL, $peserta);
        }

        $terbaik = null;
        $rasioTerbaik = -1.0;
        $seri = false;

        foreach ($varian as $satu) {
            $rasio = $this->rasio($satu, $eksperimen);

            if ($rasio > $rasioTerbaik) {
                $terbaik = $satu;
                $rasioTerbaik = $rasio;
                $seri = false;

                continue;
            }

            if ($rasio === $rasioTerbaik) {
                $seri = true;
            }
        }

        if ($seri || $terbaik === null) {
            return new PenilaianEksperimen(false, null, self::TIDAK_ADA_SELISIH, $peserta);
        }

        return new PenilaianEksperimen(true, $terbaik, null, $peserta);
    }

    /** Menyatakan pemenang hanya boleh lewat sini, dan penilaiannya tidak dapat dilewati. */
    public function nyatakanPemenang(EksperimenPemasaran $eksperimen): VarianEksperimen
    {
        $penilaian = $this->nilai($eksperimen);

        if (! $penilaian->bolehDinyatakan || $penilaian->pemenang === null) {
            throw new AturanBisnisDilanggar(
                $penilaian->alasan ?? 'Pemenang belum dapat dinyatakan.',
            );
        }

        $eksperimen->PemenangVarianId = $penilaian->pemenang->Id;
        $eksperimen->AlasanKeputusan = "Metrik {$eksperimen->MetrikUtama->label()} tertinggi pada sampel yang cukup.";
        $eksperimen->DiputuskanPada = CarbonImmutable::now();
        $eksperimen->Status = StatusEksperimen::Selesai;
        $eksperimen->SelesaiPada = CarbonImmutable::now();
        $eksperimen->save();

        $this->audit->catat(
            'Eksperimen.PemenangDinyatakan',
            'EksperimenPemasaran',
            $eksperimen->Id,
            dataSesudah: [
                'Varian' => $penilaian->pemenang->Kode,
                'MinimumSampel' => $eksperimen->MinimumSampel,
            ],
        );

        return $penilaian->pemenang;
    }

    private function rasio(VarianEksperimen $varian, EksperimenPemasaran $eksperimen): float
    {
        $penyebut = $this->penghitung->peserta($varian);

        if ($penyebut === 0) {
            return 0.0;
        }

        return round($this->penghitung->pembilang($varian, $eksperimen->MetrikUtama) / $penyebut, 4);
    }
}
