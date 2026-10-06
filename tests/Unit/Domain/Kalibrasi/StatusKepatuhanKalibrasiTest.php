<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Kalibrasi;

use App\Domain\Kalibrasi\Domain\Enums\StatusKepatuhanKalibrasi;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Aturan jatuh tempo kalibrasi pada batas-batas harinya; satu aturan dipakai seluruh layar dan pengingat. */
final class StatusKepatuhanKalibrasiTest extends TestCase
{
    private const HARI_INI = '2026-06-15';

    /** @return array<string, array{0: bool, 1: string, 2: int, 3: StatusKepatuhanKalibrasi}> */
    public static function kasus(): array
    {
        return [
            'kemarin: terlambat' => [true, '2026-06-14', 30, StatusKepatuhanKalibrasi::Terlambat],
            'hari ini: belum terlambat, tetapi sudah waktunya diingatkan' => [true, '2026-06-15', 0, StatusKepatuhanKalibrasi::SegeraJatuhTempo],
            'tepat di batas peringatan' => [true, '2026-07-15', 30, StatusKepatuhanKalibrasi::SegeraJatuhTempo],
            'sehari di luar batas peringatan' => [true, '2026-07-16', 30, StatusKepatuhanKalibrasi::Valid],
            'besok tanpa jendela peringatan' => [true, '2026-06-16', 0, StatusKepatuhanKalibrasi::Valid],
            'rencana nonaktif tidak pernah terlambat' => [false, '2026-01-01', 30, StatusKepatuhanKalibrasi::TidakAktif],
        ];
    }

    #[DataProvider('kasus')]
    public function test_status_mengikuti_batas_hari_dan_keaktifan(bool $aktif, string $tanggalBerikutnya, int $peringatan, StatusKepatuhanKalibrasi $harapan): void
    {
        $status = StatusKepatuhanKalibrasi::untuk(
            $aktif,
            CarbonImmutable::parse($tanggalBerikutnya),
            $peringatan,
            CarbonImmutable::parse(self::HARI_INI),
        );

        $this->assertSame($harapan, $status);
    }

    public function test_hari_ini_tidak_bergeser_karena_dihitung(): void
    {
        $hariIni = CarbonImmutable::parse(self::HARI_INI);

        StatusKepatuhanKalibrasi::untuk(true, CarbonImmutable::parse('2026-06-20'), 30, $hariIni);

        $this->assertSame(self::HARI_INI, $hariIni->toDateString());
    }
}
