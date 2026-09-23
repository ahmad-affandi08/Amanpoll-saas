<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\SimpanDrafKonten;
use App\Domain\Pemasaran\Application\Services\KalkulatorKeandalanPublik;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\ToolPublik;
use App\Domain\Pemasaran\Http\Requests\BuatQrAsetRequest;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Qr\PembuatQrAset;

/** Angka kalkulator publik sama dengan KPI yang sama di dalam aplikasi (Gate 38.05). */
final class KalkulatorPublikTest extends KasusLeadMagnet
{
    private function kalkulator(): KalkulatorKeandalanPublik
    {
        return app(KalkulatorKeandalanPublik::class);
    }

    public function test_halaman_kalkulator_terbuka_untuk_anonim(): void
    {
        foreach ([ToolPublik::KalkulatorMttr, ToolPublik::KalkulatorMtbf, ToolPublik::KalkulatorDowntime] as $tool) {
            $this->get($this->urlPublik($tool->jalur()))
                ->assertOk()
                ->assertInertia(fn ($halaman) => $halaman
                    ->component('Publik/Kalkulator')
                    ->where('tool.Kode', $tool->value));
        }
    }

    /** Tiga jalur, satu rumus; yang berbeda hanya angka yang disorot. */
    public function test_setiap_kalkulator_menyorot_metriknya_sendiri(): void
    {
        $this->assertSame('Mttr', ToolPublik::KalkulatorMttr->metrikSorotan());
        $this->assertSame('Mtbf', ToolPublik::KalkulatorMtbf->metrikSorotan());
        $this->assertSame('TotalJam', ToolPublik::KalkulatorDowntime->metrikSorotan());
        $this->assertNull(ToolPublik::QrAset->metrikSorotan());
    }

    public function test_perhitungan_dikembalikan_lewat_sesi(): void
    {
        $this->post($this->urlPublik('/tools/kalkulator'), [
            'JumlahAset' => 1,
            'HariRentang' => 1,
            'JumlahKegagalan' => 2,
            'MenitDowntime' => 180,
        ])->assertRedirect()->assertSessionHas('hasil');

        $hasil = session('hasil');

        $this->assertIsArray($hasil);
        // Sesi menyimpannya lewat serialisasi, jadi dibandingkan sebagai angka, bukan sebagai tipe.
        $this->assertEqualsWithDelta(3.0, $hasil['TotalJam'], 0.001);
        $this->assertEqualsWithDelta(1.5, $hasil['Mttr'], 0.001);
    }

    /** Hasil yang dikirim lewat flash sesi harus sampai ke halaman sebagai prop, bukan hanya ke sesi. */
    public function test_hasil_perhitungan_tampil_di_halaman_setelah_kembali(): void
    {
        $halaman = $this->urlPublik(ToolPublik::KalkulatorMttr->jalur());

        $this->from($halaman)
            ->followingRedirects()
            ->post($this->urlPublik('/tools/kalkulator'), [
                'JumlahAset' => 1,
                'HariRentang' => 1,
                'JumlahKegagalan' => 2,
                'MenitDowntime' => 180,
            ])
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->component('Publik/Kalkulator')
                ->where('hasil.Mttr', fn ($nilai): bool => abs((float) $nilai - 1.5) < 0.001));
    }

    /** Formulir HTML mengirim angka sebagai teks; aturan `integer` menerimanya, jadi controller wajib mengubahnya ke int. */
    public function test_masukan_angka_berupa_teks_tetap_dihitung(): void
    {
        $this->post($this->urlPublik('/tools/kalkulator'), [
            'JumlahAset' => '1',
            'HariRentang' => '1',
            'JumlahKegagalan' => '2',
            'MenitDowntime' => '180',
        ])
            ->assertRedirect()
            ->assertSessionHas('hasil', fn (array $hasil): bool => abs((float) $hasil['Mttr'] - 1.5) < 0.001);
    }

    /** Tanpa kegagalan, MTTR dan MTBF dinyatakan belum tersedia, bukan dijawab nol. */
    public function test_tanpa_kegagalan_mttr_dan_mtbf_belum_tersedia(): void
    {
        $hasil = $this->kalkulator()->hitung(10, 30, 0, 0);

        $this->assertNull($hasil->mttr);
        $this->assertNull($hasil->mtbf);
        $this->assertSame(KalkulatorKeandalanPublik::TANPA_KEGAGALAN, $hasil->alasanMttr);
        $this->assertSame(KalkulatorKeandalanPublik::TANPA_KEGAGALAN, $hasil->alasanMtbf);
    }

    public function test_tanpa_waktu_operasional_ketersediaan_belum_tersedia(): void
    {
        $hasil = $this->kalkulator()->hitung(0, 30, 2, 120);

        $this->assertNull($hasil->ketersediaan);
        $this->assertNull($hasil->mtbf);
        $this->assertSame(KalkulatorKeandalanPublik::TANPA_WAKTU_OPERASIONAL, $hasil->alasanKetersediaan);
        $this->assertSame(KalkulatorKeandalanPublik::TANPA_WAKTU_OPERASIONAL, $hasil->alasanMtbf);

        // MTTR tidak butuh waktu operasional, jadi tetap terjawab.
        $this->assertSame(1.0, $hasil->mttr);
    }

    public function test_rentang_nol_hari_juga_menutup_ketersediaan(): void
    {
        $hasil = $this->kalkulator()->hitung(10, 0, 2, 120);

        $this->assertNull($hasil->ketersediaan);
        $this->assertSame(KalkulatorKeandalanPublik::TANPA_WAKTU_OPERASIONAL, $hasil->alasanKetersediaan);
    }

    /** Downtime yang melampaui waktu operasional adalah masukan keliru, dan dikatakan. */
    public function test_downtime_melampaui_waktu_operasional_diperingatkan(): void
    {
        $hasil = $this->kalkulator()->hitung(1, 1, 1, 2000);

        $this->assertSame(KalkulatorKeandalanPublik::DOWNTIME_MELAMPAUI, $hasil->peringatan);
        $this->assertSame(0.0, $hasil->ketersediaan);
        $this->assertSame(0.0, $hasil->mtbf);
    }

    public function test_masukan_wajar_tidak_diperingatkan(): void
    {
        $this->assertNull($this->kalkulator()->hitung(10, 30, 4, 480)->peringatan);
    }

    public function test_masukan_negatif_ditolak(): void
    {
        $this->post($this->urlPublik('/tools/kalkulator'), [
            'JumlahAset' => -1,
            'HariRentang' => 30,
            'JumlahKegagalan' => 1,
            'MenitDowntime' => 10,
        ])->assertSessionHasErrors('JumlahAset');
    }

    public function test_masukan_bukan_angka_ditolak(): void
    {
        $this->post($this->urlPublik('/tools/kalkulator'), [
            'JumlahAset' => 'sepuluh',
            'HariRentang' => 30,
            'JumlahKegagalan' => 1,
            'MenitDowntime' => 10,
        ])->assertSessionHasErrors('JumlahAset');
    }

    public function test_honeypot_membatalkan_perhitungan(): void
    {
        $this->post($this->urlPublik('/tools/kalkulator'), [
            'JumlahAset' => 10,
            'HariRentang' => 30,
            'JumlahKegagalan' => 4,
            'MenitDowntime' => 480,
            PerangkapSpam::FIELD => 'http://spam.test',
        ])->assertRedirect()->assertSessionMissing('hasil');
    }

    public function test_halaman_qr_terbuka_untuk_anonim(): void
    {
        $this->get($this->urlPublik(ToolPublik::QrAset->jalur()))
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Publik/QrAset')
                ->where('batas.MaksKode', BuatQrAsetRequest::MAKS_KODE));
    }

    public function test_qr_dibuat_untuk_setiap_kode(): void
    {
        $this->post($this->urlPublik('/tools/qr'), ['Kode' => ['AST-0001', 'AST-0002']])
            ->assertRedirect()
            ->assertSessionHas('qr');

        $qr = session('qr');

        $this->assertIsArray($qr);
        $this->assertCount(2, $qr);
        $this->assertSame('AST-0001', $qr[0]['Kode']);
        $this->assertStringContainsString('<svg', $qr[0]['Svg']);
    }

    public function test_label_qr_tampil_di_halaman_setelah_kembali(): void
    {
        $this->from($this->urlPublik(ToolPublik::QrAset->jalur()))
            ->followingRedirects()
            ->post($this->urlPublik('/tools/qr'), ['Kode' => ['AST-0001']])
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->component('Publik/QrAset')
                ->where('qr.0.Kode', 'AST-0001'));
    }

    /** Kode yang dimasukkan tidak pernah muncul mentah di markup SVG-nya. */
    public function test_kode_tidak_pernah_masuk_ke_markup_svg(): void
    {
        $jahat = '</svg><script>alert(1)</script>';

        $hasil = app(PembuatQrAset::class)->untuk([$jahat], BuatQrAsetRequest::MAKS_KODE);

        $this->assertStringNotContainsString('script', $hasil[0]['Svg']);
        $this->assertStringNotContainsString($jahat, $hasil[0]['Svg']);
    }

    public function test_kode_kembar_disatukan(): void
    {
        $hasil = app(PembuatQrAset::class)->untuk(['AST-1', 'AST-1', ' AST-1 ', 'AST-2'], BuatQrAsetRequest::MAKS_KODE);

        $this->assertCount(2, $hasil);
    }

    public function test_kode_kosong_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(PembuatQrAset::class)->untuk(['', '   '], BuatQrAsetRequest::MAKS_KODE);
    }

    public function test_kode_terlalu_panjang_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(PembuatQrAset::class)->untuk(
            [str_repeat('A', PembuatQrAset::MAKS_PANJANG_KODE + 1)],
            BuatQrAsetRequest::MAKS_KODE,
        );
    }

    /** Endpoint anonim tidak boleh diminta membuat ribuan QR sekaligus. */
    public function test_kode_melebihi_batas_ditolak(): void
    {
        $kode = array_map(fn (int $ke): string => 'AST-'.$ke, range(1, BuatQrAsetRequest::MAKS_KODE + 1));

        $this->post($this->urlPublik('/tools/qr'), ['Kode' => $kode])
            ->assertSessionHasErrors('Kode');
    }

    public function test_honeypot_membatalkan_pembuatan_qr(): void
    {
        $this->post($this->urlPublik('/tools/qr'), [
            'Kode' => ['AST-0001'],
            PerangkapSpam::FIELD => 'http://spam.test',
        ])->assertRedirect()->assertSessionMissing('qr');
    }

    /** Tools mendaftar lebih dulu di rak /tools, jadi konten tidak boleh menutupinya. */
    public function test_konten_tidak_boleh_menempati_jalur_tool(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(SimpanDrafKonten::class)->jalankan(null, [
            'Slug' => ToolPublik::KalkulatorMttr->value,
            'Jenis' => JenisKontenPemasaran::FreeTool->value,
            'Judul' => 'Kalkulator Palsu',
            'IsiMarkdown' => 'Isi.',
        ]);
    }

    public function test_jalur_tool_tetap_dilayani_controllernya(): void
    {
        $this->get($this->urlPublik(ToolPublik::KalkulatorMttr->jalur()))
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->component('Publik/Kalkulator'));
    }
}
