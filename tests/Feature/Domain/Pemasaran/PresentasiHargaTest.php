<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Application\Services\PenyimpanIsiHalaman;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use Illuminate\Support\Str;

/**
 * Harga yang tampil selalu harga paket di Langganan, dan mengubah presentasinya
 * tidak pernah mengubah angka yang ditagihkan (Gate 38.07).
 */
final class PresentasiHargaTest extends KasusHalaman
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::CMS);
    }

    private function buatPaket(string $kode, float $bulanan, float $tahunan = 0.0, bool $aktif = true): PaketLangganan
    {
        return PaketLangganan::create([
            'Kode' => $kode,
            'Nama' => 'Paket '.Str::title($kode),
            'Deskripsi' => 'Paket untuk uji presentasi.',
            'HargaBulanan' => $bulanan,
            'HargaTahunan' => $tahunan === 0.0 ? $bulanan * 10 : $tahunan,
            'MataUang' => 'IDR',
            'Aktif' => $aktif,
        ]);
    }

    private function beriFitur(PaketLangganan $paket, string $kodeFitur, bool $diizinkan, ?float $batas = null): void
    {
        $definisi = KatalogFitur::ambil($kodeFitur);

        $fitur = FiturPaket::query()->firstOrCreate(
            ['Kode' => $kodeFitur],
            ['Nama' => $definisi->nama, 'TipeBatas' => $definisi->tipeBatas->value],
        );

        PaketFitur::create([
            'PaketLanggananId' => $paket->Id,
            'FiturPaketId' => $fitur->Id,
            'Diizinkan' => $diizinkan,
            'BatasNilai' => $batas,
        ]);
    }

    private function pengelolaHalaman(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::HALAMAN_LIHAT,
            KatalogIzinPemasaran::HALAMAN_KELOLA,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $paket
     * @return array<string, mixed>
     */
    private function muatanBlokHarga(array $paket): array
    {
        return [
            'Slug' => '/harga-konsol',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Harga',
            'Blok' => [[
                'Jenis' => JenisBlokHalaman::Harga->value,
                'Isi' => ['judul' => 'Harga', 'paket' => $paket],
            ]],
        ];
    }

    /** @param array<string, mixed> $isi */
    private function halamanHarga(array $isi): void
    {
        $halaman = $this->buatDraf('/harga', 'Harga', [[
            'Jenis' => JenisBlokHalaman::Harga->value,
            'Isi' => $isi,
        ]]);

        app(TerbitkanHalaman::class)->jalankan($halaman);
    }

    /** @return array<string, mixed> */
    private function blokTampil(string $jalur = '/harga'): array
    {
        $props = $this->get($this->urlPublik($jalur))->assertOk()->viewData('page')['props'];

        return $props['halaman']['Blok'][0]['Isi'];
    }

    /** Inti Gate 38.07: harga yang tampil sama dengan harga paket. */
    public function test_harga_yang_tampil_sama_dengan_harga_paket(): void
    {
        $paket = $this->buatPaket('pro', 750_000);
        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $kartu = $this->blokTampil()['Paket'][0];

        $this->assertSame((float) $paket->HargaBulanan, $kartu['Harga']);
        $this->assertSame('Paket Pro', $kartu['Nama']);
        $this->assertSame('IDR', $kartu['MataUang']);
    }

    /** Sisi lain Gate 38.07: mengubah harga paket langsung terlihat, tanpa menyunting halaman. */
    public function test_mengubah_harga_paket_langsung_mengubah_yang_tampil(): void
    {
        $paket = $this->buatPaket('pro', 750_000);
        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $this->assertSame(750_000.0, $this->blokTampil()['Paket'][0]['Harga']);

        $paket->HargaBulanan = 900_000;
        $paket->save();

        $this->assertSame(900_000.0, $this->blokTampil()['Paket'][0]['Harga']);
    }

    /** Isi halaman disimpan sementara; harganya tidak boleh ikut tersimpan di sana. */
    public function test_harga_tidak_pernah_ikut_tersimpan_di_cache_halaman(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $this->get($this->urlPublik('/harga'))->assertOk();

        $tersimpan = app(PenyimpanIsiHalaman::class)->untukSlug('/harga');

        $this->assertNotNull($tersimpan);
        $this->assertArrayNotHasKey('Paket', $tersimpan['Blok'][0]['Isi']);
    }

    public function test_siklus_tahunan_memakai_harga_tahunan(): void
    {
        $paket = $this->buatPaket('pro', 750_000, 7_500_000);
        $this->halamanHarga(['siklus' => 'Tahunan', 'paket' => [['kode' => 'pro']]]);

        $kartu = $this->blokTampil()['Paket'][0];

        $this->assertSame((float) $paket->HargaTahunan, $kartu['Harga']);
        $this->assertSame('per tahun', $kartu['LabelSiklus']);
    }

    /** Paket yang dinonaktifkan tidak boleh tampil sebagai kartu kosong. */
    public function test_paket_nonaktif_tidak_tampil(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->buatPaket('usang', 100_000, aktif: false);

        $this->halamanHarga(['paket' => [['kode' => 'usang'], ['kode' => 'pro']]]);

        $kartu = $this->blokTampil()['Paket'];

        $this->assertCount(1, $kartu);
        $this->assertSame('pro', $kartu[0]['Kode']);
    }

    public function test_urutan_kartu_mengikuti_urutan_yang_diatur(): void
    {
        $this->buatPaket('mahal', 900_000);
        $this->buatPaket('murah', 100_000);

        $this->halamanHarga(['paket' => [['kode' => 'mahal'], ['kode' => 'murah']]]);

        $kartu = $this->blokTampil()['Paket'];

        $this->assertSame('mahal', $kartu[0]['Kode']);
        $this->assertSame('murah', $kartu[1]['Kode']);
    }

    public function test_sorotan_badge_dan_cta_ikut_tampil(): void
    {
        $this->buatPaket('pro', 750_000);

        $this->halamanHarga(['paket' => [[
            'kode' => 'pro',
            'disorot' => true,
            'badge' => 'Paling dipilih',
            'ringkasan' => 'Untuk pabrik menengah.',
            'ctaTeks' => 'Coba gratis',
            'ctaUrl' => '/daftar',
        ]]]);

        $kartu = $this->blokTampil()['Paket'][0];

        $this->assertTrue($kartu['Disorot']);
        $this->assertSame('Paling dipilih', $kartu['Badge']);
        $this->assertSame('Untuk pabrik menengah.', $kartu['Ringkasan']);
        $this->assertSame('Coba gratis', $kartu['CtaTeks']);
        $this->assertSame('/daftar', $kartu['CtaUrl']);
    }

    /** Daftar fitur kartu dibaca dari PaketFitur, bukan diketik di blok. */
    public function test_fitur_kartu_dibaca_dari_paket_fitur(): void
    {
        $paket = $this->buatPaket('pro', 750_000);
        $this->beriFitur($paket, KatalogFitur::MODUL_KALIBRASI, true);
        $this->beriFitur($paket, KatalogFitur::MODUL_KEPATUHAN, false);
        $this->beriFitur($paket, KatalogFitur::BATAS_ASET, true, 500);

        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $fitur = $this->blokTampil()['Paket'][0]['Fitur'];

        // Daftarnya diperiksa persis: fitur yang tidak diizinkan tidak boleh muncul sebagai apa pun.
        $this->assertSame(['Modul Kalibrasi', '500 aset'], $fitur);
    }

    /** Tabel perbandingan disusun dari PaketFitur, bukan diketik ulang. */
    public function test_perbandingan_disusun_dari_paket_fitur(): void
    {
        $murah = $this->buatPaket('murah', 100_000);
        $mahal = $this->buatPaket('mahal', 900_000);
        $this->beriFitur($murah, KatalogFitur::MODUL_KALIBRASI, false);
        $this->beriFitur($mahal, KatalogFitur::MODUL_KALIBRASI, true);

        $halaman = $this->buatDraf('/banding', 'Banding', [[
            'Jenis' => JenisBlokHalaman::Perbandingan->value,
            'Isi' => ['paket' => ['murah', 'mahal'], 'fitur' => [KatalogFitur::MODUL_KALIBRASI]],
        ]]);
        app(TerbitkanHalaman::class)->jalankan($halaman);

        $isi = $this->blokTampil('/banding');

        $this->assertSame(['Paket Murah', 'Paket Mahal'], $isi['kolom']);
        $this->assertSame('Modul Kalibrasi', $isi['baris'][0]['label']);
        $this->assertSame(['—', 'Modul Kalibrasi'], $isi['baris'][0]['nilai']);
    }

    /** Blok perbandingan tanpa kode paket dibiarkan memakai isinya sendiri. */
    public function test_perbandingan_tanpa_kode_paket_tidak_diubah(): void
    {
        $halaman = $this->buatDraf('/banding', 'Banding', [[
            'Jenis' => JenisBlokHalaman::Perbandingan->value,
            'Isi' => ['kolom' => ['Kami', 'Mereka'], 'baris' => [['label' => 'Harga', 'nilai' => ['A', 'B']]]],
        ]]);
        app(TerbitkanHalaman::class)->jalankan($halaman);

        $isi = $this->blokTampil('/banding');

        $this->assertSame(['Kami', 'Mereka'], $isi['kolom']);
    }

    public function test_harga_dilihat_tercatat_saat_halaman_harga_dibuka(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $this->get($this->urlPublik('/harga'))->assertOk();

        $event = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::HARGA_DILIHAT)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('/harga', $event->DataTambahan['Slug'] ?? null);
    }

    /** Halaman tanpa blok harga tidak boleh menghitung satu HargaDilihat pun. */
    public function test_halaman_tanpa_blok_harga_tidak_mencatat_harga_dilihat(): void
    {
        $this->buatTerbit('/tentang', 'Tentang');

        $this->get($this->urlPublik('/tentang'))->assertOk();

        $this->assertSame(0, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::HARGA_DILIHAT)->count());
    }

    public function test_cta_diklik_tercatat_lewat_endpoint_publik(): void
    {
        $this->post($this->urlPublik('/cta'), [
            'Label' => 'Coba gratis',
            'Tujuan' => '/daftar',
            'Sumber' => 'Harga',
        ])->assertOk();

        $event = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::CTA_DIKLIK)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('Coba gratis', $event->DataTambahan['Label'] ?? null);
        $this->assertSame('Harga', $event->DataTambahan['Sumber'] ?? null);
    }

    public function test_klik_cta_yang_terperangkap_honeypot_tidak_dicatat(): void
    {
        $this->post($this->urlPublik('/cta'), [
            'Label' => 'Coba gratis',
            PerangkapSpam::FIELD => 'http://spam.test',
        ])->assertOk();

        $this->assertSame(0, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::CTA_DIKLIK)->count());
    }

    /** Kode paket yang tidak dikenal ditolak konsol, bukan diam-diam tidak tampil. */
    public function test_kode_paket_asing_ditolak_konsol(): void
    {
        $this->buatPaket('pro', 750_000);

        $this->actingAs($this->pengelolaHalaman(), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatanBlokHarga([['kode' => 'karangan']]))
            ->assertSessionHasErrors('Blok.0.Isi.paket.0.kode');
    }

    public function test_kode_paket_yang_dikenal_diterima_konsol(): void
    {
        $this->buatPaket('pro', 750_000);

        $this->actingAs($this->pengelolaHalaman(), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatanBlokHarga([['kode' => 'pro']]))
            ->assertRedirect();
    }

    /** Sorotan menandai satu paket yang direkomendasikan; dua sorotan berarti tidak ada. */
    public function test_dua_paket_disorot_ditolak_konsol(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->buatPaket('bisnis', 1_500_000);

        $this->actingAs($this->pengelolaHalaman(), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatanBlokHarga([
                ['kode' => 'pro', 'disorot' => true],
                ['kode' => 'bisnis', 'disorot' => true],
            ]))
            ->assertSessionHasErrors('Blok.0.Isi.paket');
    }

    public function test_satu_paket_disorot_diterima_konsol(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->buatPaket('bisnis', 1_500_000);

        $this->actingAs($this->pengelolaHalaman(), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatanBlokHarga([
                ['kode' => 'pro', 'disorot' => true],
                ['kode' => 'bisnis'],
            ]))
            ->assertRedirect();
    }

    public function test_siklus_asing_ditolak_konsol(): void
    {
        $this->buatPaket('pro', 750_000);

        $muatan = $this->muatanBlokHarga([['kode' => 'pro']]);
        $muatan['Blok'][0]['Isi']['siklus'] = 'Mingguan';

        $this->actingAs($this->pengelolaHalaman(), 'platform')
            ->post(route('pemasaran.halaman.store'), $muatan)
            ->assertSessionHasErrors('Blok.0.Isi.siklus');
    }

    /** Presentasi harga tidak menyimpan angka apa pun di domain Pemasaran. */
    public function test_blok_harga_tidak_menyimpan_angka_di_domain_pemasaran(): void
    {
        $this->buatPaket('pro', 750_000);
        $this->halamanHarga(['paket' => [['kode' => 'pro']]]);

        $tersimpan = app(PenyimpanIsiHalaman::class)->untukSlug('/harga');
        $isi = $tersimpan['Blok'][0]['Isi'] ?? [];

        $this->assertSame([['kode' => 'pro']], $isi['paket']);
        $this->assertStringNotContainsString('750000', json_encode($isi, JSON_THROW_ON_ERROR));
    }
}
