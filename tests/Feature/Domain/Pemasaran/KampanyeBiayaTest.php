<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\SimpanKampanye;
use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenKampanye;
use App\Domain\Pemasaran\Domain\Enums\MetrikTargetKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeBiaya;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeChannel;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeKonten;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeTarget;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;

/** Konsol biaya, target, dan konten kampanye beserta penjaganya (FASE 38.08). */
final class KampanyeBiayaTest extends KasusPemasaran
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::ANALITIK);
    }

    public function test_biaya_tersimpan_per_channel_per_hari(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.kampanye.biaya.simpan', $kampanye), [
                'Channel' => ChannelKampanye::GoogleAds->value,
                'Tanggal' => '2026-09-01',
                'Jumlah' => 150000,
            ])
            ->assertRedirect();

        $biaya = KampanyeBiaya::query()->firstOrFail();

        $this->assertSame(ChannelKampanye::GoogleAds, $biaya->Channel);
        $this->assertSame('150000.00', $biaya->Jumlah);
    }

    /** Satu channel satu hari hanya punya satu angka; kiriman kedua mengoreksi, bukan menambah. */
    public function test_biaya_hari_yang_sama_dikoreksi_bukan_digandakan(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);
        $admin = $this->pengelola();

        foreach ([150000, 175000] as $jumlah) {
            $this->actingAs($admin, 'platform')
                ->post(route('pemasaran.kampanye.biaya.simpan', $kampanye), [
                    'Channel' => ChannelKampanye::GoogleAds->value,
                    'Tanggal' => '2026-09-01',
                    'Jumlah' => $jumlah,
                ])
                ->assertRedirect();
        }

        $this->assertSame(1, KampanyeBiaya::query()->count());
        $this->assertSame('175000.00', KampanyeBiaya::query()->value('Jumlah'));
    }

    /** Biaya di channel yang tidak dijalankan kampanye ini akan jadi CAC tanpa penyebut selamanya. */
    public function test_biaya_di_channel_yang_tidak_dijalankan_ditolak(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.kampanye.biaya.simpan', $kampanye), [
                'Channel' => ChannelKampanye::TikTok->value,
                'Tanggal' => '2026-09-01',
                'Jumlah' => 150000,
            ])
            ->assertSessionHasErrors('Channel');

        $this->assertSame(0, KampanyeBiaya::query()->count());
    }

    public function test_channel_di_luar_daftar_tertutup_ditolak(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.kampanye.biaya.simpan', $kampanye), [
                'Channel' => 'Billboard',
                'Tanggal' => '2026-09-01',
                'Jumlah' => 150000,
            ])
            ->assertSessionHasErrors('Channel');
    }

    /** Mencabut channel yang sudah dibelanjai akan membuat biayanya menggantung tanpa induk. */
    public function test_channel_yang_sudah_dibelanjai_tidak_dapat_dilepas(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds, ChannelKampanye::LinkedIn]);
        KampanyeBiaya::create([
            'KampanyeId' => $kampanye->Id,
            'Channel' => ChannelKampanye::LinkedIn->value,
            'Tanggal' => '2026-09-01',
            'Jumlah' => 150000,
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(SimpanKampanye::class)->jalankan($kampanye, [
            'Kode' => $kampanye->Kode,
            'Nama' => $kampanye->Nama,
            'Objective' => 'Lead',
            'Status' => StatusKampanye::Draf->value,
            'Channel' => [ChannelKampanye::GoogleAds->value],
        ]);
    }

    public function test_biaya_kampanye_lain_tidak_dapat_dihapus_lewat_rute_bersarang(): void
    {
        $milikOrang = $this->buatKampanye([ChannelKampanye::GoogleAds], 'punya-orang');
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds], 'punya-saya');

        $biaya = KampanyeBiaya::create([
            'KampanyeId' => $milikOrang->Id,
            'Channel' => ChannelKampanye::GoogleAds->value,
            'Tanggal' => '2026-09-01',
            'Jumlah' => 150000,
        ]);

        $this->expectException(DataTidakDitemukan::class);

        $this->withoutExceptionHandling()
            ->actingAs($this->pengelola(), 'platform')
            ->delete(route('pemasaran.kampanye.biaya.hapus', [$kampanye, $biaya]));
    }

    public function test_transisi_status_di_luar_peta_ditolak(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.kampanye.update', $kampanye), $this->muatan($kampanye, StatusKampanye::Aktif))
            ->assertSessionHasErrors('Status');

        $this->assertSame(StatusKampanye::Draf, $kampanye->fresh()?->Status);
    }

    public function test_transisi_status_yang_sah_diterima(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.kampanye.update', $kampanye), $this->muatan($kampanye, StatusKampanye::Siap))
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusKampanye::Siap, $kampanye->fresh()?->Status);
    }

    /** Peta transisi tidak ada gunanya bila kampanye dapat lahir langsung berstatus apa pun. */
    public function test_kampanye_baru_selalu_lahir_sebagai_draf(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.kampanye.store'), [
                'Kode' => 'lahir-aktif',
                'Nama' => 'Lahir Aktif',
                'Objective' => 'Lead',
                'Status' => StatusKampanye::Aktif->value,
                'Channel' => [ChannelKampanye::GoogleAds->value],
            ])
            ->assertSessionHasErrors('Status');

        $this->assertSame(0, Kampanye::query()->count());
    }

    /** Penjaga transisi harus hidup di domain juga, bukan hanya di formulirnya. */
    public function test_penjaga_transisi_tetap_menolak_walau_formulirnya_dilewati(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(SimpanKampanye::class)->jalankan($kampanye, [
            'Kode' => $kampanye->Kode,
            'Nama' => $kampanye->Nama,
            'Objective' => 'Lead',
            'Status' => StatusKampanye::Selesai->value,
            'Channel' => [ChannelKampanye::GoogleAds->value],
        ]);
    }

    public function test_field_lanjutan_tersimpan_beserta_utmnya(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.kampanye.update', $kampanye), [
                ...$this->muatan($kampanye, StatusKampanye::Draf),
                'Budget' => 5000000,
                'Audience' => 'Manajer mutu pabrik menengah',
                'Offer' => 'Uji coba 14 hari',
                'UtmSource' => 'google',
                'UtmMedium' => 'cpc',
                'UtmTerm' => 'audit mutu',
                'UtmContent' => 'varian-a',
            ])
            ->assertSessionHasNoErrors();

        $segar = $kampanye->fresh();

        $this->assertSame('5000000.00', $segar?->Budget);
        $this->assertSame('google', $segar?->UtmSource);
        $this->assertSame('varian-a', $segar?->UtmContent);
    }

    /** Target dikirim utuh: metrik yang hilang dari kiriman berarti dicabut. */
    public function test_target_yang_tidak_dikirim_ulang_tercabut(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);
        $admin = $this->pengelola();

        $this->actingAs($admin, 'platform')
            ->put(route('pemasaran.kampanye.target.simpan', $kampanye), [
                'Target' => [
                    ['Metrik' => MetrikTargetKampanye::Lead->value, 'Nilai' => 100],
                    ['Metrik' => MetrikTargetKampanye::Bayar->value, 'Nilai' => 10],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, KampanyeTarget::query()->count());

        $this->actingAs($admin, 'platform')
            ->put(route('pemasaran.kampanye.target.simpan', $kampanye), [
                'Target' => [['Metrik' => MetrikTargetKampanye::Lead->value, 'Nilai' => 120]],
            ])
            ->assertRedirect();

        $this->assertSame(1, KampanyeTarget::query()->count());
        $this->assertSame('120.00', KampanyeTarget::query()->value('Nilai'));
    }

    public function test_konten_kampanye_dapat_ditambah_dan_dihapus(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);
        $admin = $this->pengelola();

        $this->actingAs($admin, 'platform')
            ->post(route('pemasaran.kampanye.konten.simpan', $kampanye), [
                'Jenis' => JenisKontenKampanye::Iklan->value,
                'Judul' => 'Iklan pencarian varian A',
                'Tautan' => 'https://contoh.test/iklan',
            ])
            ->assertRedirect();

        $konten = KampanyeKonten::query()->firstOrFail();
        $this->assertSame(JenisKontenKampanye::Iklan, $konten->Jenis);

        $this->actingAs($admin, 'platform')
            ->delete(route('pemasaran.kampanye.konten.hapus', [$kampanye, $konten]))
            ->assertRedirect();

        $this->assertSame(0, KampanyeKonten::query()->count());
    }

    public function test_izin_lihat_tidak_cukup_untuk_mencatat_biaya(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::KAMPANYE_LIHAT]), 'platform')
            ->post(route('pemasaran.kampanye.biaya.simpan', $kampanye), [
                'Channel' => ChannelKampanye::GoogleAds->value,
                'Tanggal' => '2026-09-01',
                'Jumlah' => 150000,
            ])
            ->assertForbidden();
    }

    public function test_halaman_detail_menampilkan_biaya_target_dan_realisasinya(): void
    {
        $kampanye = $this->buatKampanye([ChannelKampanye::GoogleAds]);
        KampanyeBiaya::create([
            'KampanyeId' => $kampanye->Id,
            'Channel' => ChannelKampanye::GoogleAds->value,
            'Tanggal' => '2026-09-01',
            'Jumlah' => 150000,
        ]);
        KampanyeTarget::create([
            'KampanyeId' => $kampanye->Id,
            'Metrik' => MetrikTargetKampanye::Lead->value,
            'Nilai' => 100,
        ]);

        $props = $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.kampanye.show', $kampanye))
            ->viewData('page')['props'];

        $this->assertSame(150000.0, $props['biaya'][0]['Jumlah']);
        $this->assertSame(100.0, $props['target'][0]['Nilai']);
        $this->assertSame(0.0, $props['target'][0]['Realisasi']);
        $this->assertSame(['Siap', 'Diarsipkan'], $props['kampanye']['TujuanStatus']);
    }

    /** @param list<ChannelKampanye> $channel */
    private function buatKampanye(array $channel, string $kode = 'promo'): Kampanye
    {
        $kampanye = Kampanye::create([
            'Kode' => $kode,
            'Nama' => 'Kampanye '.$kode,
            'Objective' => 'Lead',
            'Status' => StatusKampanye::Draf->value,
        ]);

        foreach ($channel as $satu) {
            KampanyeChannel::create(['KampanyeId' => $kampanye->Id, 'Channel' => $satu->value]);
        }

        return $kampanye;
    }

    /** @return array<string, mixed> */
    private function muatan(Kampanye $kampanye, StatusKampanye $status): array
    {
        return [
            'Kode' => $kampanye->Kode,
            'Nama' => $kampanye->Nama,
            'Objective' => 'Lead',
            'Status' => $status->value,
            'Channel' => [ChannelKampanye::GoogleAds->value],
        ];
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::KAMPANYE_LIHAT,
            KatalogIzinPemasaran::KAMPANYE_KELOLA,
        ]);
    }
}
