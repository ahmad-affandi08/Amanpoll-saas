<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Services\PenyusunKartuRiwayatAset;
use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * FASE 26.01 — waktu tersimpan ditampilkan menurut `ZonaWaktu`
 * organisasinya (TASK 01.04), termasuk saat konversinya melewati pergantian
 * tanggal. Dokumen yang dipakai adalah kartu riwayat aset: setiap baris
 * riwayatnya adalah waktu tersimpan yang dicetak ulang untuk dibaca manusia.
 */
final class ZonaWaktuTampilanOrganisasiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-30 16:30:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_waktu_riwayat_tersimpan_ditampilkan_menurut_zona_waktu_organisasinya_masing_masing(): void
    {
        // Perpindahan dicatat pada 16:30 UTC lewat jam aplikasi, persis seperti aksi aslinya menulis.
        $jayapura = $this->asetDenganPerpindahan('ZW-JPR', 'Asia/Jayapura');
        $jakarta = $this->asetDenganPerpindahan('ZW-JKT', 'Asia/Jakarta');

        // Kartu dicetak lima belas menit kemudian.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-30 16:45:00', 'UTC'));
        $htmlJayapura = $this->htmlKartu($jayapura);
        $htmlJakarta = $this->htmlKartu($jakarta);

        // UTC+9: pukul 16:30 UTC sudah tanggal 1 Juli di Jayapura.
        $this->assertStringContainsString('01-07-2026 01:30', $htmlJayapura);
        $this->assertStringContainsString('Dicetak pada 01-07-2026 01:45', $htmlJayapura);

        // Pembanding UTC+7 pada instan yang sama: masih 30 Juni.
        $this->assertStringContainsString('30-06-2026 23:30', $htmlJakarta);
        $this->assertStringContainsString('Dicetak pada 30-06-2026 23:45', $htmlJakarta);

        // Nilai UTC mentah tidak pernah bocor ke dokumen yang dibaca manusia.
        $this->assertStringNotContainsString('30-06-2026 16:30', $htmlJayapura);
        $this->assertStringNotContainsString('30-06-2026 16:30', $htmlJakarta);
    }

    private function asetDenganPerpindahan(string $kode, string $zonaWaktu): Aset
    {
        $organisasi = Organisasi::create([
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => 'Organisasi '.$kode,
            'ZonaWaktu' => $zonaWaktu,
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $kategori = KategoriAset::create(['Kode' => 'KAT-'.$kode, 'Nama' => 'Alat Medis']);
        $asal = Lokasi::create(['Kode' => 'LOK-A-'.$kode, 'Nama' => 'Gudang Alat', 'Status' => 'Aktif']);
        $tujuan = Lokasi::create(['Kode' => 'LOK-B-'.$kode, 'Nama' => 'Ruang ICU', 'Status' => 'Aktif']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.$kode,
            'Nama' => 'Monitor Pasien',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
        ]);
        RiwayatLokasiAset::create([
            'AsetId' => $aset->Id,
            'LokasiAsalId' => $asal->Id,
            'LokasiTujuanId' => $tujuan->Id,
            'JenisPerpindahan' => JenisRiwayatLokasiAset::Manual->value,
            'Alasan' => 'Kebutuhan ICU',
            'DipindahkanPada' => now(),
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        return $aset;
    }

    private function htmlKartu(Aset $aset): string
    {
        app(KonteksOrganisasi::class)->tetapkan((string) $aset->OrganisasiId);
        $html = app(PenyusunKartuRiwayatAset::class)->html($aset);
        app(KonteksOrganisasi::class)->bersihkan();

        return $html;
    }
}
