<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananSesiDemo;
use App\Domain\Pemasaran\Application\Services\PenyusunFunnelGrowth;
use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Domain\Enums\TahapFunnelGrowth;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiDemo;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Siklus hidup sesi demo beserta batas-batasnya (Gate 38.03). */
final class SesiDemoTest extends KasusDemo
{
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Inti Gate 38.03: satu sesi utuh terbaca di tahap Demo funnel growth. */
    public function test_sesi_utuh_terbaca_di_funnel_growth(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);

        $sesi = $layanan->mulai($demo, $this->pengunjung());
        $layanan->catat($sesi, JenisEventDemo::AsetDilihat, ModulDemo::Aset);
        $layanan->selesaikan($sesi);

        $funnel = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertSame(1, $funnel[TahapFunnelGrowth::Demo->value]);
        $this->assertSame(1, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::DEMO_DIMULAI)->count());
        $this->assertSame(1, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::DEMO_SELESAI)->count());
        $this->assertSame(StatusSesiDemo::Selesai, $sesi->fresh()?->Status);
    }

    /** Sebelum FASE ini tahap Demo selalu nol karena tidak ada yang menulis peristiwanya. */
    public function test_tahap_demo_nol_bila_tidak_ada_sesi(): void
    {
        $this->buatDemo();

        $funnel = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertSame(0, $funnel[TahapFunnelGrowth::Demo->value]);
    }

    public function test_demo_yang_dimatikan_tidak_dapat_dimulai(): void
    {
        $demo = $this->buatDemo(aktif: false);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());
    }

    /** Batas sesi serentak ditegakkan, bukan sekadar disimpan sebagai angka di setelan. */
    public function test_batas_sesi_serentak_ditegakkan(): void
    {
        $demo = $this->buatDemo(maksSesiSerentak: 2);
        $layanan = app(LayananSesiDemo::class);

        $layanan->mulai($demo, $this->pengunjung());
        $layanan->mulai($demo, $this->pengunjung());

        $this->expectException(AturanBisnisDilanggar::class);
        $layanan->mulai($demo, $this->pengunjung());
    }

    /** Sesi yang sudah selesai melepaskan kuotanya; batas itu tentang yang sedang berjalan. */
    public function test_sesi_yang_selesai_melepaskan_kuotanya(): void
    {
        $demo = $this->buatDemo(maksSesiSerentak: 1);
        $layanan = app(LayananSesiDemo::class);

        $pertama = $layanan->mulai($demo, $this->pengunjung());
        $layanan->selesaikan($pertama);

        $kedua = $layanan->mulai($demo, $this->pengunjung());

        $this->assertSame(StatusSesiDemo::Berjalan, $kedua->Status);
        $this->assertSame(1, $layanan->sesiBerjalan($demo));
    }

    /** Batas durasi ditegakkan saat sesi dipakai, tidak menunggu job terjadwal kebetulan lewat. */
    public function test_sesi_yang_lewat_durasinya_ditolak_saat_dipakai(): void
    {
        $demo = $this->buatDemo(maksDurasiMenit: 15);
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(16));

        try {
            $layanan->catat($sesi, JenisEventDemo::AsetDilihat, ModulDemo::Aset);
            $this->fail('Sesi yang lewat durasinya seharusnya ditolak.');
        } catch (AturanBisnisDilanggar) {
            $this->assertSame(StatusSesiDemo::Kedaluwarsa, $sesi->fresh()?->Status);
        }
    }

    public function test_sesi_kedaluwarsa_tidak_memenuhi_kuota_sesi_baru(): void
    {
        $demo = $this->buatDemo(maksSesiSerentak: 1, maksDurasiMenit: 15);
        $layanan = app(LayananSesiDemo::class);
        $layanan->mulai($demo, $this->pengunjung());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(16));
        $baru = $layanan->mulai($demo, $this->pengunjung());

        $this->assertSame(StatusSesiDemo::Berjalan, $baru->Status);
        $this->assertSame(1, SesiDemo::query()->where('Status', StatusSesiDemo::Kedaluwarsa->value)->count());
    }

    /** Modul yang tidak ditampilkan tidak boleh diam-diam terekam sebagai sudah dibuka. */
    public function test_modul_yang_tidak_ditampilkan_ditolak(): void
    {
        $demo = $this->buatDemo();
        $sesi = app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananSesiDemo::class)->catat($sesi, JenisEventDemo::FiturDibuka, ModulDemo::Kalibrasi);
    }

    /** Batas sesi hanya boleh ditulis layanannya sendiri, supaya funnel tidak dapat digelembungkan. */
    public function test_peristiwa_batas_sesi_tidak_dapat_dikirim_dari_luar(): void
    {
        $demo = $this->buatDemo();
        $sesi = app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananSesiDemo::class)->catat($sesi, JenisEventDemo::DemoDimulai);
    }

    public function test_peristiwa_demo_tidak_dapat_diubah_setelah_tercatat(): void
    {
        $demo = $this->buatDemo();
        app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        $event = EventDemo::query()->firstOrFail();

        $this->expectException(AturanBisnisDilanggar::class);
        $event->update(['Jenis' => JenisEventDemo::CtaDiklik->value]);
    }

    /** Delapan peristiwa bagian 11 harus benar-benar dapat dicatat, bukan sekadar ada di enumnya. */
    public function test_seluruh_peristiwa_dalam_sesi_dapat_dicatat(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());

        $dalamSesi = array_filter(
            JenisEventDemo::cases(),
            fn (JenisEventDemo $satu): bool => ! $satu->ditulisLayanan(),
        );

        foreach ($dalamSesi as $satu) {
            $layanan->catat($sesi, $satu);
        }

        $layanan->selesaikan($sesi);

        $this->assertSame(
            count(JenisEventDemo::cases()),
            EventDemo::query()->distinct()->count('Jenis'),
        );
    }

    /** CTA yang diklik di dalam demo ikut tercatat sebagai peristiwa pemasaran. */
    public function test_cta_diklik_ikut_ke_event_pemasaran(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());

        $layanan->catat($sesi, JenisEventDemo::CtaDiklik);

        $this->assertSame(1, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::CTA_DIKLIK)->count());
    }

    /** Peristiwa di dalam demo tidak boleh membanjiri EventPemasaran; hanya tiga yang menyeberang. */
    public function test_peristiwa_dalam_demo_tidak_membanjiri_event_pemasaran(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());

        $layanan->catat($sesi, JenisEventDemo::AsetDilihat, ModulDemo::Aset);
        $layanan->catat($sesi, JenisEventDemo::QrDilihat, ModulDemo::Aset);
        $layanan->catat($sesi, JenisEventDemo::PreventifDilihat, ModulDemo::Preventif);

        $this->assertSame(4, EventDemo::query()->count());
        $this->assertSame(1, EventPemasaran::query()->count());
    }

    public function test_sesi_yang_sudah_selesai_tidak_menerima_peristiwa_baru(): void
    {
        $demo = $this->buatDemo();
        $layanan = app(LayananSesiDemo::class);
        $sesi = $layanan->mulai($demo, $this->pengunjung());
        $layanan->selesaikan($sesi);

        $this->expectException(AturanBisnisDilanggar::class);
        $layanan->catat($sesi, JenisEventDemo::AsetDilihat, ModulDemo::Aset);
    }

    private function filter(): FilterGrowth
    {
        return new FilterGrowth(
            CarbonImmutable::now()->subDays(30),
            CarbonImmutable::now()->addDay(),
        );
    }
}
