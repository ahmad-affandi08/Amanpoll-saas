<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Infrastructure\Clock\LayananZonaWaktu;
use Tests\TestCase;

class LayananZonaWaktuTest extends TestCase
{
    private LayananZonaWaktu $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layanan = new LayananZonaWaktu();
    }

    public function test_konversi_ke_zona_waktu_lokal_yang_masih_hari_sama(): void
    {
        $lokal = $this->layanan->keZonaWaktu('2026-01-01 16:30:00', 'Asia/Jakarta');

        $this->assertSame('2026-01-01 23:30:00', $lokal->format('Y-m-d H:i:s'));
    }

    public function test_konversi_ke_zona_waktu_lokal_yang_melewati_pergantian_tanggal(): void
    {
        $lokal = $this->layanan->keZonaWaktu('2026-01-01 17:30:00', 'Asia/Jakarta');

        $this->assertSame('2026-01-02 00:30:00', $lokal->format('Y-m-d H:i:s'));
    }

    public function test_konversi_ke_zona_waktu_dengan_offset_negatif_mundur_ke_hari_sebelumnya(): void
    {
        $lokal = $this->layanan->keZonaWaktu('2026-01-01 03:00:00', 'America/New_York');

        $this->assertSame('2025-12-31 22:00:00', $lokal->format('Y-m-d H:i:s'));
    }

    public function test_konversi_dari_lokal_kembali_ke_utc_konsisten(): void
    {
        $utc = $this->layanan->keUtc('2026-01-02 00:30:00', 'Asia/Jakarta');

        $this->assertSame('2026-01-01 17:30:00', $utc->format('Y-m-d H:i:s'));
    }

    public function test_zona_waktu_efektif_memakai_lokasi_bila_ada(): void
    {
        $this->assertSame('Asia/Makassar', $this->layanan->zonaWaktuEfektif('Asia/Makassar', 'Asia/Jakarta'));
    }

    public function test_zona_waktu_efektif_memakai_organisasi_bila_lokasi_kosong(): void
    {
        $this->assertSame('Asia/Jakarta', $this->layanan->zonaWaktuEfektif(null, 'Asia/Jakarta'));
    }
}
