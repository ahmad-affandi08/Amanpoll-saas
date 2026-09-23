<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenyusunUlangAttribution;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Domain\Pemasaran\Jobs\HitungAttribution;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

final class KampanyeDanEventTest extends KasusPemasaran
{
    public function test_kampanye_tertutup_saat_flag_analitik_mati(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::KAMPANYE_LIHAT]), 'platform')
            ->get(route('pemasaran.kampanye.index'))
            ->assertNotFound();
    }

    public function test_kampanye_terbuka_saat_flag_analitik_hidup(): void
    {
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::KAMPANYE_LIHAT]), 'platform')
            ->get(route('pemasaran.kampanye.index'))
            ->assertOk();
    }

    public function test_izin_lihat_tidak_cukup_untuk_membuat_kampanye(): void
    {
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::KAMPANYE_LIHAT]), 'platform')
            ->post(route('pemasaran.kampanye.store'), $this->muatanKampanye())
            ->assertForbidden();
    }

    public function test_kampanye_dapat_dibuat_beserta_channelnya(): void
    {
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);

        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::KAMPANYE_LIHAT,
            KatalogIzinPemasaran::KAMPANYE_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.kampanye.store'), $this->muatanKampanye())
            ->assertRedirect();

        $kampanye = Kampanye::query()->with('channel')->firstOrFail();

        $this->assertSame('promo-q1', $kampanye->Kode);
        $this->assertSame(StatusKampanye::Draf, $kampanye->Status);
        $this->assertEqualsCanonicalizing(
            ['GoogleAds', 'LinkedIn'],
            $kampanye->channel->pluck('Channel')->all(),
        );
    }

    public function test_kode_kampanye_tidak_boleh_kembar(): void
    {
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);
        Kampanye::create(['Kode' => 'promo-q1', 'Nama' => 'Lama', 'Objective' => 'Lead', 'Status' => 'Aktif']);

        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::KAMPANYE_LIHAT,
            KatalogIzinPemasaran::KAMPANYE_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.kampanye.store'), $this->muatanKampanye())
            ->assertSessionHasErrors('Kode');
    }

    public function test_peristiwa_di_luar_taxonomy_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(PerekamEventPemasaran::class)->catat('PeristiwaKarangan');
    }

    public function test_peristiwa_dalam_taxonomy_tersimpan(): void
    {
        $event = app(PerekamEventPemasaran::class)->catat(
            KatalogPeristiwaPemasaran::HARGA_DILIHAT,
            pengenalPengunjung: (string) Str::ulid(),
        );

        $this->assertSame(KatalogPeristiwaPemasaran::HARGA_DILIHAT, $event->Jenis);
    }

    public function test_peristiwa_tidak_dapat_diubah_setelah_tercatat(): void
    {
        $event = app(PerekamEventPemasaran::class)->catat(KatalogPeristiwaPemasaran::HARGA_DILIHAT);

        $this->expectException(AturanBisnisDilanggar::class);
        $event->update(['Jenis' => KatalogPeristiwaPemasaran::DEMO_DIMULAI]);
    }

    public function test_taxonomy_peristiwa_tidak_memuat_duplikat(): void
    {
        $semua = KatalogPeristiwaPemasaran::semua();

        $this->assertSame($semua, array_values(array_unique($semua)));
    }

    public function test_penyusunan_ulang_menautkan_kampanye_yang_didaftarkan_belakangan(): void
    {
        $pengenal = (string) Str::ulid();

        $sesi = SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'LandingUrl' => 'http://publik.localhost/?utm_campaign=menyusul',
            'DimulaiPada' => now(),
            'TerakhirAktifPada' => now(),
        ]);
        UtmPemasaran::create([
            'SesiPengunjungId' => $sesi->Id,
            'Source' => 'google',
            'Campaign' => 'menyusul',
        ]);
        app(PenyusunUlangAttribution::class)->susunUlang($pengenal);

        $this->assertNull(
            AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->value('KampanyeIdPertama'),
        );

        $kampanye = Kampanye::create([
            'Kode' => 'menyusul',
            'Nama' => 'Didaftarkan Belakangan',
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        (new HitungAttribution($pengenal))->handle(app(PenyusunUlangAttribution::class));

        $this->assertSame(
            $kampanye->Id,
            AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->value('KampanyeIdPertama'),
        );
    }

    public function test_penyusunan_ulang_menghasilkan_hasil_yang_sama_berapa_kali_pun_dijalankan(): void
    {
        $pengenal = (string) Str::ulid();
        $sesi = SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'LandingUrl' => 'http://publik.localhost/',
            'DimulaiPada' => now(),
            'TerakhirAktifPada' => now(),
        ]);
        UtmPemasaran::create(['SesiPengunjungId' => $sesi->Id, 'Source' => 'google']);

        $penyusun = app(PenyusunUlangAttribution::class);
        $pertama = $penyusun->susunUlang($pengenal)?->only(['SumberPertama', 'SumberTerakhir']);
        $kedua = $penyusun->susunUlang($pengenal)?->only(['SumberPertama', 'SumberTerakhir']);

        $this->assertSame($pertama, $kedua);
        $this->assertSame(1, AttributionPemasaran::query()->count());
    }

    public function test_daftar_kampanye_menampilkan_jumlah_kunjungannya(): void
    {
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);
        $kampanye = Kampanye::create([
            'Kode' => 'dihitung',
            'Nama' => 'Dihitung',
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        foreach (range(1, 3) as $ke) {
            $sesi = SesiPengunjung::create([
                'PengenalPengunjung' => (string) Str::ulid(),
                'DimulaiPada' => now(),
                'TerakhirAktifPada' => now(),
            ]);
            UtmPemasaran::create([
                'SesiPengunjungId' => $sesi->Id,
                'KampanyeId' => $kampanye->Id,
                'Campaign' => 'dihitung',
            ]);
        }

        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::KAMPANYE_LIHAT]), 'platform')
            ->get(route('pemasaran.kampanye.index'))
            ->viewData('page')['props'];

        $this->assertSame(3, $props['kampanye']['data'][0]['JumlahKunjungan']);
    }

    public function test_peristiwa_tidak_dapat_dihapus(): void
    {
        $event = app(PerekamEventPemasaran::class)->catat(KatalogPeristiwaPemasaran::CTA_DIKLIK);

        // Ditangkap, bukan expectException, supaya pemeriksaan barisnya masih
        // ada tetap dieksekusi: ditolak di muka tidak menjamin tidak terhapus.
        try {
            $event->delete();

            $this->fail('Peristiwa pemasaran seharusnya tidak dapat dihapus.');
        } catch (AturanBisnisDilanggar) {
            // diharapkan
        }

        $this->assertSame(1, EventPemasaran::query()->count());
    }

    /** @return array<string, mixed> */
    private function muatanKampanye(): array
    {
        return [
            'Kode' => 'promo-q1',
            'Nama' => 'Promo Kuartal 1',
            'Objective' => 'Lead',
            'Status' => 'Draf',
            'Channel' => ['GoogleAds', 'LinkedIn'],
        ];
    }
}
