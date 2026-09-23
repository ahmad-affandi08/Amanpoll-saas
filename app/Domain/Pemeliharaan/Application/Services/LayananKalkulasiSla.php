<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\Services;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Application\Services\LayananKalenderKerja;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class LayananKalkulasiSla
{
    public function __construct(private readonly LayananKalenderKerja $kalenderKerja) {}

    /**
     * @return array{respons: CarbonImmutable|null, penyelesaian: CarbonImmutable|null}
     */
    public function hitungBatas(
        string $organisasiId,
        TingkatLayanan $tingkatLayanan,
        AturanTingkatLayanan $aturan,
        CarbonInterface $mulai,
        string $zonaWaktu,
        ?string $lokasiId = null,
    ): array {
        return [
            'respons' => $this->hitungSatuBatas($organisasiId, $tingkatLayanan, $aturan, $mulai, $aturan->MenitRespons, $zonaWaktu, $lokasiId),
            'penyelesaian' => $this->hitungSatuBatas($organisasiId, $tingkatLayanan, $aturan, $mulai, $aturan->MenitPenyelesaian, $zonaWaktu, $lokasiId),
        ];
    }

    private function hitungSatuBatas(
        string $organisasiId,
        TingkatLayanan $tingkatLayanan,
        AturanTingkatLayanan $aturan,
        CarbonInterface $mulai,
        ?int $menit,
        string $zonaWaktu,
        ?string $lokasiId,
    ): ?CarbonImmutable {
        if ($menit === null) {
            return null;
        }

        $waktuMulai = CarbonImmutable::instance($mulai)->setTimezone($zonaWaktu);
        if (! $aturan->MenghitungJamKerja) {
            return $this->keZonaPenyimpanan($waktuMulai->addMinutes($menit));
        }

        $sisaMenit = $menit;
        $waktu = $waktuMulai;
        $hariKerja = $tingkatLayanan->HariKerja ?: [1, 2, 3, 4, 5];

        while (true) {
            if (! $this->hariDihitung($organisasiId, $tingkatLayanan, $waktu, $hariKerja, $lokasiId)) {
                $waktu = $this->awalHariKerjaBerikutnya($waktu, $tingkatLayanan);

                continue;
            }

            $awal = $this->waktuPadaTanggal($waktu, (string) $tingkatLayanan->JamKerjaMulai);
            $akhir = $this->waktuPadaTanggal($waktu, (string) $tingkatLayanan->JamKerjaSelesai);

            if ($waktu->lessThan($awal)) {
                $waktu = $awal;
            }

            if ($waktu->greaterThanOrEqualTo($akhir)) {
                $waktu = $this->awalHariKerjaBerikutnya($waktu, $tingkatLayanan);

                continue;
            }

            $tersedia = (int) $waktu->diffInMinutes($akhir);
            if ($sisaMenit <= $tersedia) {
                return $this->keZonaPenyimpanan($waktu->addMinutes($sisaMenit));
            }

            $sisaMenit -= $tersedia;
            $waktu = $this->awalHariKerjaBerikutnya($waktu, $tingkatLayanan);
        }
    }

    /**
     * @param  list<int>  $hariKerja
     */
    private function hariDihitung(
        string $organisasiId,
        TingkatLayanan $tingkatLayanan,
        CarbonImmutable $tanggal,
        array $hariKerja,
        ?string $lokasiId,
    ): bool {
        if (! in_array($tanggal->dayOfWeekIso, $hariKerja, true)) {
            return false;
        }

        return ! $tingkatLayanan->MemperhitungkanHariLibur
            || ! $this->kalenderKerja->apakahHariLibur($organisasiId, $tanggal, $lokasiId);
    }

    private function awalHariKerjaBerikutnya(CarbonImmutable $waktu, TingkatLayanan $tingkatLayanan): CarbonImmutable
    {
        return $this->waktuPadaTanggal($waktu->addDay(), (string) $tingkatLayanan->JamKerjaMulai);
    }

    private function waktuPadaTanggal(CarbonImmutable $tanggal, string $jam): CarbonImmutable
    {
        return CarbonImmutable::parse($tanggal->format('Y-m-d').' '.$jam, $tanggal->timezone);
    }

    /**
     * Jam kerja dihitung di zona lokasi, tetapi batasnya disimpan sebagai momen
     * UTC seperti seluruh kolom waktu. Disebut langsung, bukan dibaca dari
     * `app.timezone`, supaya hasilnya tidak ikut berubah bila konfigurasi itu
     * disentuh.
     */
    private function keZonaPenyimpanan(CarbonImmutable $waktu): CarbonImmutable
    {
        return $waktu->utc();
    }
}
