<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Laporan tersimpan (21.03): kepemilikan, pembagian, dan normalisasi konfigurasi. */
final class LaporanTersimpanTest extends KasusPelaporan
{
    public function test_pengguna_dapat_menyimpan_laporan_beserta_filternya(): void
    {
        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Aset per unit',
                'Pribadi' => true,
                'Konfigurasi' => [
                    'KunciKpi' => ['aset.jumlah', 'perintah_kerja.aktif'],
                    'Filter' => ['Dari' => '2026-01-01', 'Sampai' => '2026-03-31'],
                ],
            ])
            ->assertRedirect();

        $laporan = LaporanTersimpan::query()->where('Nama', 'Aset per unit')->firstOrFail();

        $this->assertSame($pengguna->Id, $laporan->PemilikId);
        $this->assertSame(['aset.jumlah', 'perintah_kerja.aktif'], $laporan->Konfigurasi['KunciKpi']);
        $this->assertSame('2026-01-01', $laporan->Konfigurasi['Filter']['Dari']);
        $this->assertSame('2026-03-31', $laporan->Konfigurasi['Filter']['Sampai']);
    }

    public function test_kpi_di_luar_kewenangan_penyimpan_dibuang_dari_konfigurasi(): void
    {
        // Tanpa Aset.Lihat, "aset.jumlah" tidak boleh mengendap di konfigurasi.
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Campuran',
                'Pribadi' => true,
                'Konfigurasi' => ['KunciKpi' => ['aset.jumlah', 'perintah_kerja.aktif']],
            ])
            ->assertRedirect();

        $laporan = LaporanTersimpan::query()->where('Nama', 'Campuran')->firstOrFail();

        $this->assertSame(['perintah_kerja.aktif'], $laporan->Konfigurasi['KunciKpi']);
    }

    public function test_laporan_tanpa_satu_pun_kpi_yang_diizinkan_ditolak(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Hanya aset',
                'Pribadi' => true,
                'Konfigurasi' => ['KunciKpi' => ['aset.jumlah']],
            ])
            ->assertStatus(422);

        $this->assertSame(0, LaporanTersimpan::query()->where('Nama', 'Hanya aset')->count());
    }

    public function test_kunci_kpi_yang_tidak_ada_di_katalog_gagal_validasi(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Kunci karangan',
                'Pribadi' => true,
                'Konfigurasi' => ['KunciKpi' => ['aset.tidak_ada']],
            ])
            ->assertSessionHasErrors('Konfigurasi.KunciKpi.0');
    }

    public function test_laporan_pribadi_tidak_terlihat_pengguna_lain(): void
    {
        $pemilik = $this->buatPengguna();
        $lain = $this->buatPengguna(['Laporan.Lihat']);

        $pribadi = $this->simpanLaporan($pemilik, 'Rahasia saya', pribadi: true);
        $dibagikan = $this->simpanLaporan($pemilik, 'Untuk tim', pribadi: false);

        $respons = $this->actingAs($lain)->get(route('pelaporan.laporan.index'));
        $respons->assertOk();

        $nama = array_column($respons->viewData('page')['props']['laporan'], 'Nama');

        $this->assertContains($dibagikan->Nama, $nama);
        $this->assertNotContains($pribadi->Nama, $nama);
    }

    public function test_laporan_dibagikan_hanya_dapat_diubah_pemiliknya(): void
    {
        $pemilik = $this->buatPengguna();
        $lain = $this->buatPengguna(['Laporan.Lihat']);
        $laporan = $this->simpanLaporan($pemilik, 'Untuk tim', pribadi: false);

        $muatan = [
            'Nama' => 'Diambil alih',
            'Pribadi' => false,
            'Konfigurasi' => ['KunciKpi' => ['perintah_kerja.aktif']],
        ];

        $this->actingAs($lain)
            ->put(route('pelaporan.laporan.update', $laporan), $muatan)
            ->assertForbidden();

        $this->actingAs($pemilik)
            ->put(route('pelaporan.laporan.update', $laporan), ['Nama' => 'Nama baru'] + $muatan)
            ->assertRedirect();

        $this->assertSame('Nama baru', $laporan->refresh()->Nama);
        $this->assertSame($pemilik->Id, $laporan->PemilikId, 'Kepemilikan tidak berpindah lewat pembaruan.');
    }

    public function test_hanya_pemilik_yang_dapat_menghapus_laporan(): void
    {
        $pemilik = $this->buatPengguna();
        $lain = $this->buatPengguna(['Laporan.Lihat']);
        $laporan = $this->simpanLaporan($pemilik, 'Untuk tim', pribadi: false);

        $this->actingAs($lain)
            ->delete(route('pelaporan.laporan.destroy', $laporan))
            ->assertForbidden();

        $this->actingAs($pemilik)
            ->delete(route('pelaporan.laporan.destroy', $laporan))
            ->assertRedirect();

        $this->assertNull(LaporanTersimpan::query()->find($laporan->Id));
    }

    public function test_membuka_laporan_menghitung_metriknya(): void
    {
        $pengguna = $this->buatPengguna();
        $laporan = $this->simpanLaporan($pengguna, 'Pekerjaan aktif', pribadi: true);

        $respons = $this->actingAs($pengguna)
            ->get(route('pelaporan.laporan.index', ['laporan' => $laporan->Id]));

        $respons->assertOk();
        $metrik = $respons->viewData('page')['props']['metrik'];

        $this->assertArrayHasKey('perintah_kerja.aktif', $metrik);
        $this->assertArrayHasKey('Formula', $metrik['perintah_kerja.aktif']);
        $this->assertNotSame('', $metrik['perintah_kerja.aktif']['Formula']);
    }

    /**
     * Kewenangan melihat laporan dibagikan pindah dari saringan koleksi ke
     * syarat kueri saat ekspor dibuat -- saringan di luar kueri tidak dapat
     * ikut ke unduhan yang dialirkan per potongan.
     *
     * Yang sudah dijaga sebelumnya hanya pengguna BERIZIN. Justru kebalikannya
     * yang menentukan: tanpa Laporan.Lihat, laporan dibagikan milik orang lain
     * tidak boleh terlihat -- di layar maupun di berkas.
     */
    public function test_tanpa_izin_lihat_laporan_dibagikan_orang_lain_tidak_terlihat(): void
    {
        $pemilik = $this->buatPengguna();
        $tanpaIzin = $this->buatPengguna();

        $dibagikan = $this->simpanLaporan($pemilik, 'Untuk tim', pribadi: false);
        $miliknyaSendiri = $this->simpanLaporan($tanpaIzin, 'Punya saya', pribadi: true);

        $respons = $this->actingAs($tanpaIzin)->get(route('pelaporan.laporan.index'));
        $respons->assertOk();
        $nama = array_column($respons->viewData('page')['props']['laporan'], 'Nama');

        $this->assertContains($miliknyaSendiri->Nama, $nama, 'Laporannya sendiri harus tetap terlihat.');
        $this->assertNotContains($dibagikan->Nama, $nama);
    }

    /** Berkas ekspornya tunduk pada batas yang sama dengan layarnya. */
    public function test_ekspor_daftar_laporan_menghormati_batas_kewenangan_yang_sama(): void
    {
        $pemilik = $this->buatPengguna();
        $tanpaIzin = $this->buatPengguna();
        $berizin = $this->buatPengguna(['Laporan.Lihat']);

        $dibagikan = $this->simpanLaporan($pemilik, 'Untuk tim', pribadi: false);
        $pribadi = $this->simpanLaporan($pemilik, 'Rahasia saya', pribadi: true);

        $isiTanpaIzin = $this->actingAs($tanpaIzin)
            ->get(route('pelaporan.laporan.ekspor'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringNotContainsString($dibagikan->Nama, $isiTanpaIzin);
        $this->assertStringNotContainsString($pribadi->Nama, $isiTanpaIzin);

        // Pembanding: yang berizin memang mendapat laporan dibagikan, jadi
        // ketiadaan di atas bukan karena ekspornya tidak pernah memuat apa pun.
        $isiBerizin = $this->actingAs($berizin)
            ->get(route('pelaporan.laporan.ekspor'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($dibagikan->Nama, $isiBerizin);
        $this->assertStringNotContainsString($pribadi->Nama, $isiBerizin);
    }

    private function simpanLaporan(
        Pengguna $pemilik,
        string $nama,
        bool $pribadi,
    ): LaporanTersimpan {
        $this->actingAs($pemilik)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => $nama,
                'Pribadi' => $pribadi,
                'Konfigurasi' => ['KunciKpi' => ['perintah_kerja.aktif']],
            ])
            ->assertRedirect();

        return LaporanTersimpan::query()->where('Nama', $nama)->firstOrFail();
    }
}
