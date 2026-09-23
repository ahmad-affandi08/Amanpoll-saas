<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Core\Organisasi\KalenderOrganisasi;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Generator nomor dokumen sequential per organisasi+JenisDokumen. */
final class LayananNomorDokumen
{
    private const MAKS_PERCOBAAN = 5;

    public function __construct(private readonly KalenderOrganisasi $kalender) {}

    public function berikutnya(string $organisasiId, string $jenisDokumen): string
    {
        return DB::transaction(function () use ($organisasiId, $jenisDokumen): string {
            $baris = DB::table('NomorDokumen')
                ->where('OrganisasiId', $organisasiId)
                ->where('JenisDokumen', $jenisDokumen)
                ->lockForUpdate()
                ->first();

            if (! $baris) {
                throw new DataTidakDitemukan("Pola nomor dokumen untuk '{$jenisDokumen}' belum diatur.");
            }

            $sekarang = $this->kalender->sekarang($organisasiId);
            $periodeSaatIni = $this->periodeSaatIni($baris->ResetPeriode, $sekarang);
            $nomorBaru = ($baris->PeriodeAktif === $periodeSaatIni) ? $baris->NomorTerakhir + 1 : 1;

            DB::table('NomorDokumen')->where('Id', $baris->Id)->update([
                'NomorTerakhir' => $nomorBaru,
                'PeriodeAktif' => $periodeSaatIni,
                'DiperbaruiPada' => now(),
            ]);

            return $this->format($baris->FormatNomor, (string) $baris->Awalan, $nomorBaru, $periodeSaatIni, $sekarang);
        }, self::MAKS_PERCOBAAN);
    }

    /** Pratinjau nomor berikutnya TANPA mengubah NomorTerakhir. */
    public function pratinjau(string $organisasiId, string $jenisDokumen): string
    {
        $baris = DB::table('NomorDokumen')
            ->where('OrganisasiId', $organisasiId)
            ->where('JenisDokumen', $jenisDokumen)
            ->first();

        if (! $baris) {
            throw new DataTidakDitemukan("Pola nomor dokumen untuk '{$jenisDokumen}' belum diatur.");
        }

        $sekarang = $this->kalender->sekarang($organisasiId);
        $periodeSaatIni = $this->periodeSaatIni($baris->ResetPeriode, $sekarang);
        $nomorBerikutnya = ($baris->PeriodeAktif === $periodeSaatIni) ? $baris->NomorTerakhir + 1 : 1;

        return $this->format($baris->FormatNomor, (string) $baris->Awalan, $nomorBerikutnya, $periodeSaatIni, $sekarang);
    }

    /**
     * Periode penomoran di kalender organisasi.
     *
     * Tahun dan bulan dibaca di zona rumah sakit: dengan jam UTC, dokumen
     * yang dibuat 1 Januari pukul 06:00 WIB masih bernomor tahun lalu dan
     * melanjutkan urutannya, alih-alih memulai dari 1.
     */
    private function periodeSaatIni(string $resetPeriode, CarbonImmutable $sekarang): string
    {
        return match ($resetPeriode) {
            'Tahunan' => $sekarang->format('Y'),
            'Bulanan' => $sekarang->format('Y-m'),
            default => '',
        };
    }

    private function format(string $formatNomor, string $awalan, int $nomor, string $periode, CarbonImmutable $sekarang): string
    {
        return preg_replace_callback(
            '/\{(Awalan|Nomor|Tahun|TahunPendek|Bulan|Periode)(?::(\d+))?\}/',
            function (array $cocok) use ($awalan, $nomor, $periode, $sekarang): string {
                $lebar = isset($cocok[2]) ? (int) $cocok[2] : null;

                return match ($cocok[1]) {
                    'Awalan' => $awalan,
                    'Nomor' => $lebar ? str_pad((string) $nomor, $lebar, '0', STR_PAD_LEFT) : (string) $nomor,
                    'Tahun' => $sekarang->format('Y'),
                    'TahunPendek' => $sekarang->format('y'),
                    'Bulan' => $sekarang->format('m'),
                    'Periode' => $periode,
                };
            },
            $formatNomor,
        ) ?? $formatNomor;
    }
}
