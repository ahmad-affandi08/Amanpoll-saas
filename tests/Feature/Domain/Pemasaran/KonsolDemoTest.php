<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananSesiDemo;
use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Domain\Pemasaran\Infrastructure\Services\DatasetDemoManufaktur;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Http\Middleware\TetapkanSesiPengunjung;
use Illuminate\Support\Facades\DB;

/** Konsol setelan demo dan rute publiknya (MARKETING.md 11, 34.1). */
final class KonsolDemoTest extends KasusDemo
{
    /** Kiriman JSON dikirim lewat post() biasa; postJson() melepas cookie pengunjungnya. */
    private const JSON = ['Accept' => 'application/json'];

    public function test_konsol_menampilkan_demo_beserta_sesinya(): void
    {
        $demo = $this->buatDemo();
        app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.demo.index'))
            ->viewData('page')['props'];

        $this->assertSame('produk', $props['demo'][0]['Kode']);
        $this->assertSame(1, $props['demo'][0]['SesiBerjalan']);
        $this->assertTrue($props['demo'][0]['TenantDemo']['Demo']);
    }

    public function test_izin_lihat_tidak_cukup_untuk_mengubah_setelan(): void
    {
        $demo = $this->buatDemo();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PEMASARAN_LIHAT]), 'platform')
            ->put(route('pemasaran.demo.update', $demo), $this->muatan())
            ->assertForbidden();
    }

    public function test_dataset_di_luar_registri_ditolak_saat_disimpan(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.demo.store'), [...$this->muatan(), 'Dataset' => 'karangan'])
            ->assertSessionHasErrors('Dataset');

        $this->assertSame(0, DemoPemasaran::query()->count());
    }

    /** Demo aktif tanpa satu pun modul hanya menampilkan layar kosong kepada calon pelanggan. */
    public function test_demo_aktif_tanpa_modul_ditolak(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.demo.store'), [...$this->muatan(), 'Aktif' => true, 'ModulTampil' => []])
            ->assertSessionHasErrors('ModulTampil');
    }

    public function test_modul_di_luar_daftar_tertutup_ditolak(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.demo.store'), [...$this->muatan(), 'ModulTampil' => ['Karangan']])
            ->assertSessionHasErrors('ModulTampil.0');
    }

    public function test_reset_manual_membangun_ulang_datasetnya(): void
    {
        $demo = $this->buatDemo();

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.demo.reset', $demo))
            ->assertRedirect();

        $this->assertSame(5, DB::table('Aset')->where('OrganisasiId', $demo->OrganisasiDemoId)->count());
    }

    public function test_konsol_menghitung_peristiwa_demo_per_jenisnya(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());
        $layanan->catat($sesi, JenisEventDemo::AsetDilihat, ModulDemo::Aset);
        $layanan->catat($sesi, JenisEventDemo::QrDilihat, ModulDemo::Aset);

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.demo.index'))
            ->viewData('page')['props'];

        $this->assertSame(1, $props['peristiwa'][JenisEventDemo::DemoDimulai->value]);
        $this->assertSame(1, $props['peristiwa'][JenisEventDemo::AsetDilihat->value]);
        $this->assertSame(1, $props['peristiwa'][JenisEventDemo::QrDilihat->value]);
        // Jenis yang belum pernah terjadi tetap muncul sebagai nol, bukan hilang dari daftarnya.
        $this->assertSame(0, $props['peristiwa'][JenisEventDemo::DemoSelesai->value]);
    }

    /** Sesi milik pengunjung lain tidak boleh dikendalikan dari tebakan id. */
    public function test_sesi_pengunjung_lain_tidak_dapat_dikendalikan(): void
    {
        $demo = $this->buatDemo();
        $sesi = app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        $this->post(route('publik.demo.selesai', $sesi), [], self::JSON)->assertForbidden();

        $this->assertSame(StatusSesiDemo::Berjalan, $sesi->fresh()?->Status);
    }

    public function test_rute_publik_menjalankan_sesi_dari_mulai_sampai_selesai(): void
    {
        $demo = $this->buatDemo();

        $mulai = $this->post(route('publik.demo.mulai', $demo), [], self::JSON)->assertCreated();
        $pengenal = (string) $mulai->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();
        $sesiId = $mulai->json('SesiDemoId');

        $this->assertIsString($sesiId);
        $sesi = SesiDemo::query()->findOrFail($sesiId);

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->post(route('publik.demo.event', $sesi), [
                'Jenis' => JenisEventDemo::AsetDilihat->value,
                'Modul' => ModulDemo::Aset->value,
            ], self::JSON)->assertCreated();

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->post(route('publik.demo.selesai', $sesi), [], self::JSON)
            ->assertOk();

        $this->assertSame(StatusSesiDemo::Selesai, $sesi->fresh()?->Status);
        $this->assertSame(3, EventDemo::query()->where('SesiDemoId', $sesi->Id)->count());
    }

    public function test_rute_publik_menolak_jenis_peristiwa_karangan(): void
    {
        $demo = $this->buatDemo();
        $mulai = $this->post(route('publik.demo.mulai', $demo), [], self::JSON);
        $pengenal = (string) $mulai->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->post(route('publik.demo.event', $mulai->json('SesiDemoId')), ['Jenis' => 'Karangan'], self::JSON)
            ->assertUnprocessable();
    }

    /** @return array<string, mixed> */
    private function muatan(): array
    {
        return [
            'Kode' => 'produk-baru',
            'Nama' => 'Demo Produk Baru',
            'Aktif' => false,
            'Dataset' => DatasetDemoManufaktur::KODE,
            'ResetIntervalMenit' => 60,
            'ModulTampil' => [ModulDemo::Aset->value],
            'FiturDibatasi' => [],
            'MaksDurasiMenit' => 30,
            'MaksSesiSerentak' => 5,
        ];
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::PEMASARAN_LIHAT,
            KatalogIzinPemasaran::PEMASARAN_KELOLA,
        ]);
    }
}
