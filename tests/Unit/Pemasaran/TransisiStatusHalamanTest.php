<?php

declare(strict_types=1);

namespace Tests\Unit\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use PHPUnit\Framework\TestCase;

/**
 * Peta transisi status halaman (MARKETING.md 8).
 */
final class TransisiStatusHalamanTest extends TestCase
{
    public function test_draf_dapat_menuju_seluruh_status_lain(): void
    {
        $draf = StatusHalamanPemasaran::Draf;

        $this->assertTrue($draf->bolehPindahKe(StatusHalamanPemasaran::Review));
        $this->assertTrue($draf->bolehPindahKe(StatusHalamanPemasaran::Terjadwal));
        $this->assertTrue($draf->bolehPindahKe(StatusHalamanPemasaran::Terbit));
        $this->assertTrue($draf->bolehPindahKe(StatusHalamanPemasaran::Diarsipkan));
    }

    public function test_halaman_terbit_dapat_diterbitkan_ulang(): void
    {
        $this->assertTrue(
            StatusHalamanPemasaran::Terbit->bolehPindahKe(StatusHalamanPemasaran::Terbit),
        );
    }

    public function test_halaman_diarsipkan_hanya_kembali_sebagai_draf(): void
    {
        $arsip = StatusHalamanPemasaran::Diarsipkan;

        $this->assertSame([StatusHalamanPemasaran::Draf], $arsip->tujuanYangDiizinkan());
        $this->assertFalse($arsip->bolehPindahKe(StatusHalamanPemasaran::Terbit));
        $this->assertFalse($arsip->bolehPindahKe(StatusHalamanPemasaran::Terjadwal));
    }

    public function test_terjadwal_tidak_dapat_kembali_ke_review(): void
    {
        $this->assertFalse(
            StatusHalamanPemasaran::Terjadwal->bolehPindahKe(StatusHalamanPemasaran::Review),
        );
    }

    public function test_hanya_terbit_yang_terlihat_publik(): void
    {
        foreach (StatusHalamanPemasaran::cases() as $status) {
            $this->assertSame(
                $status === StatusHalamanPemasaran::Terbit,
                $status->terlihatPublik(),
                "Status {$status->value} salah menilai keterlihatannya di situs publik.",
            );
        }
    }

    public function test_tidak_ada_status_yang_kehilangan_jalan_keluar(): void
    {
        foreach (StatusHalamanPemasaran::cases() as $status) {
            $this->assertNotEmpty(
                $status->tujuanYangDiizinkan(),
                "Status {$status->value} menjadi jalan buntu.",
            );
        }
    }
}
