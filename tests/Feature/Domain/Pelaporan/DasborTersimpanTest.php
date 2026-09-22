<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Domain\Pelaporan\Application\Actions\KelolaDasborTersimpan;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\KomponenDasbor;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Dasbor kustom (21.04): pilihan komponen, urutannya, lebarnya, dan penanda dasbor bawaan. */
final class DasborTersimpanTest extends KasusPelaporan
{
    public function test_komponen_disimpan_sesuai_urutan_yang_dikirim(): void
    {
        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        $this->actingAs($pengguna)
            ->post(route('pelaporan.dasbor.store'), [
                'Nama' => 'Dasbor saya',
                'Komponen' => [
                    ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka', 'Lebar' => 1],
                    ['KunciKpi' => 'aset.kondisi', 'Bentuk' => 'Donat', 'Lebar' => 2, 'Judul' => 'Kondisi aset'],
                    ['KunciKpi' => 'perintah_kerja.selesai', 'Bentuk' => 'Garis', 'Lebar' => 4],
                ],
            ])
            ->assertRedirect();

        $dasbor = DasborTersimpan::query()->where('Nama', 'Dasbor saya')->firstOrFail();
        $komponen = $dasbor->komponen()->orderBy('Urutan')->get();

        $this->assertSame(
            ['perintah_kerja.aktif', 'aset.kondisi', 'perintah_kerja.selesai'],
            $komponen->map(fn (KomponenDasbor $satu): string => $satu->Konfigurasi['KunciKpi'])->all(),
        );
        $this->assertSame([0, 1, 2], $komponen->pluck('Urutan')->all());
        $this->assertSame([1, 2, 4], $komponen->pluck('Lebar')->all());
        $this->assertSame('Kondisi aset', $komponen[1]->Judul);
    }

    public function test_menyimpan_ulang_mengganti_seluruh_susunan_bukan_menambahkan(): void
    {
        $pengguna = $this->buatPengguna();
        $dasbor = $this->simpanDasbor($pengguna, 'Dasbor saya', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
            ['KunciKpi' => 'perintah_kerja.terlambat', 'Bentuk' => 'Angka'],
        ]);

        $this->actingAs($pengguna)
            ->put(route('pelaporan.dasbor.update', $dasbor), [
                'Nama' => 'Dasbor saya',
                'Komponen' => [['KunciKpi' => 'sla.berisiko', 'Bentuk' => 'Angka']],
            ])
            ->assertRedirect();

        $komponen = $dasbor->komponen()->orderBy('Urutan')->get();

        $this->assertCount(1, $komponen, 'Susunan ditulis ulang, tidak ditumpuk.');
        $this->assertSame('sla.berisiko', $komponen[0]->Konfigurasi['KunciKpi']);
        $this->assertSame(0, $komponen[0]->Urutan, 'Urutan dimulai ulang dari nol.');
    }

    public function test_bentuk_yang_tidak_mungkin_digambar_untuk_kpi_ditolak(): void
    {
        // MTTR adalah satu angka tanpa rincian, jadi tidak ada yang dapat digambar sebagai donat.
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.dasbor.store'), [
                'Nama' => 'Bentuk salah',
                'Komponen' => [['KunciKpi' => 'keandalan.mttr', 'Bentuk' => 'Donat']],
            ])
            ->assertStatus(422);

        $this->assertSame(0, DasborTersimpan::query()->where('Nama', 'Bentuk salah')->count());
    }

    public function test_komponen_dengan_kpi_di_luar_kewenangan_ditolak(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.dasbor.store'), [
                'Nama' => 'Curi aset',
                'Komponen' => [['KunciKpi' => 'aset.jumlah', 'Bentuk' => 'Angka']],
            ])
            ->assertStatus(422);

        $this->assertSame(0, DasborTersimpan::query()->where('Nama', 'Curi aset')->count());
    }

    public function test_jumlah_komponen_dibatasi(): void
    {
        $pengguna = $this->buatPengguna();
        $komponen = array_fill(
            0,
            KelolaDasborTersimpan::BATAS_KOMPONEN + 1,
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        );

        $this->actingAs($pengguna)
            ->post(route('pelaporan.dasbor.store'), ['Nama' => 'Kebanyakan', 'Komponen' => $komponen])
            ->assertSessionHasErrors('Komponen');
    }

    public function test_lebar_di_luar_rentang_grid_ditolak(): void
    {
        $pengguna = $this->buatPengguna();
        $dasbor = $this->simpanDasbor($pengguna, 'Lebar aneh', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka', 'Lebar' => 4],
        ]);

        // Nilai di luar rentang ditolak validasi permintaan lebih dulu.
        $this->actingAs($pengguna)
            ->put(route('pelaporan.dasbor.update', $dasbor), [
                'Nama' => 'Lebar aneh',
                'Komponen' => [['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka', 'Lebar' => 9]],
            ])
            ->assertSessionHasErrors('Komponen.0.Lebar');

        $this->assertSame(4, $dasbor->komponen()->first()->Lebar);
    }

    public function test_menandai_bawaan_melepas_penanda_dasbor_lain_milik_pengguna_yang_sama(): void
    {
        $pengguna = $this->buatPengguna();
        $pertama = $this->simpanDasbor($pengguna, 'Pertama', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        ], bawaan: true);

        $this->assertTrue($pertama->refresh()->Bawaan);

        $kedua = $this->simpanDasbor($pengguna, 'Kedua', [
            ['KunciKpi' => 'perintah_kerja.terlambat', 'Bentuk' => 'Angka'],
        ], bawaan: true);

        $this->assertTrue($kedua->refresh()->Bawaan);
        $this->assertFalse($pertama->refresh()->Bawaan, 'Hanya satu dasbor bawaan per pengguna.');
    }

    public function test_penanda_bawaan_orang_lain_tidak_ikut_dilepas(): void
    {
        $satu = $this->buatPengguna();
        $dua = $this->buatPengguna();

        $milikSatu = $this->simpanDasbor($satu, 'Milik satu', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        ], bawaan: true);

        $this->simpanDasbor($dua, 'Milik dua', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        ], bawaan: true);

        $this->assertTrue($milikSatu->refresh()->Bawaan, 'Preferensi bawaan bersifat per pengguna.');
    }

    public function test_dasbor_milik_orang_lain_tidak_terlihat_dan_tidak_dapat_diubah(): void
    {
        $pemilik = $this->buatPengguna();
        $lain = $this->buatPengguna();
        $dasbor = $this->simpanDasbor($pemilik, 'Punya pemilik', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        ]);

        $respons = $this->actingAs($lain)->get(route('pelaporan.dasbor.index'));
        $respons->assertOk();

        $this->assertNotContains(
            'Punya pemilik',
            array_column($respons->viewData('page')['props']['dasbor'], 'Nama'),
        );

        $this->actingAs($lain)
            ->put(route('pelaporan.dasbor.update', $dasbor), [
                'Nama' => 'Diambil alih',
                'Komponen' => [['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka']],
            ])
            ->assertForbidden();

        $this->actingAs($lain)
            ->delete(route('pelaporan.dasbor.destroy', $dasbor))
            ->assertForbidden();
    }

    public function test_menghapus_dasbor_ikut_menghapus_komponennya(): void
    {
        $pengguna = $this->buatPengguna();
        $dasbor = $this->simpanDasbor($pengguna, 'Sementara', [
            ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka'],
        ]);

        $this->actingAs($pengguna)
            ->delete(route('pelaporan.dasbor.destroy', $dasbor))
            ->assertRedirect();

        $this->assertNull(DasborTersimpan::query()->find($dasbor->Id));
        $this->assertSame(0, KomponenDasbor::query()->where('DasborTersimpanId', $dasbor->Id)->count());
    }

    public function test_preset_peran_disiapkan_dari_izin_bukan_nama_peran(): void
    {
        $manajemen = $this->buatPengguna(['Laporan.Lihat']);
        $teknisi = $this->buatPengguna();

        $this->assertSame(
            'manajemen',
            $this->actingAs($manajemen)->get(route('pelaporan.dasbor.index'))
                ->viewData('page')['props']['preset']['Kunci'],
        );

        $this->assertSame(
            'teknisi',
            $this->actingAs($teknisi)->get(route('pelaporan.dasbor.index'))
                ->viewData('page')['props']['preset']['Kunci'],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $komponen
     */
    private function simpanDasbor(
        Pengguna $pemilik,
        string $nama,
        array $komponen,
        bool $bawaan = false,
    ): DasborTersimpan {
        $this->actingAs($pemilik)
            ->post(route('pelaporan.dasbor.store'), [
                'Nama' => $nama,
                'Bawaan' => $bawaan,
                'Komponen' => $komponen,
            ])
            ->assertRedirect();

        return DasborTersimpan::query()
            ->where('PemilikId', $pemilik->Id)
            ->where('Nama', $nama)
            ->firstOrFail();
    }
}
