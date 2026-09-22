<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Actions\PindahkanTahapProspek;
use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AktivitasProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FiturPlatform;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RiwayatTahapProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/** Pipeline prospek dan jejaknya (MARKETING.md 5.3, 36). */
final class PipelineProspekTest extends KasusProspek
{
    public function test_perpindahan_tahap_menulis_riwayat_dan_timeline(): void
    {
        $prospek = $this->buatProspek();
        $tujuan = $this->tahap(KatalogTahapPipeline::DIHUBUNGI);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $tujuan, 'Ditelepon sales.');

        $riwayat = RiwayatTahapProspek::query()
            ->where('ProspekId', $prospek->Id)
            ->orderByDesc('BerpindahPada')
            ->first();

        $this->assertNotNull($riwayat);
        $this->assertSame($tujuan->Id, $riwayat->TahapSesudahId);
        $this->assertSame('Ditelepon sales.', $riwayat->Alasan);

        $this->assertTrue(
            AktivitasProspek::query()
                ->where('ProspekId', $prospek->Id)
                ->where('Jenis', 'PerubahanTahap')
                ->exists(),
        );
    }

    public function test_memindahkan_ke_tahap_yang_sama_ditolak(): void
    {
        $prospek = $this->buatProspek();

        $this->expectException(AturanBisnisDilanggar::class);

        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::BARU));
    }

    public function test_riwayat_tahap_tidak_dapat_ditulis_ulang(): void
    {
        $prospek = $this->buatProspek();
        $riwayat = RiwayatTahapProspek::query()->where('ProspekId', $prospek->Id)->firstOrFail();

        $this->expectException(AturanBisnisDilanggar::class);
        $riwayat->update(['Alasan' => 'diubah']);
    }

    public function test_konsol_memindahkan_tahap_lewat_kodenya(): void
    {
        $prospek = $this->buatProspek();

        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.prospek.tahap', $prospek), ['Kode' => KatalogTahapPipeline::DEMO])
            ->assertRedirect();

        $this->assertSame(
            $this->tahap(KatalogTahapPipeline::DEMO)->Id,
            Prospek::query()->whereKey($prospek->Id)->value('TahapPipelineId'),
        );
    }

    public function test_izin_lihat_saja_tidak_dapat_memindahkan_tahap(): void
    {
        $prospek = $this->buatProspek();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->post(route('pemasaran.prospek.tahap', $prospek), ['Kode' => KatalogTahapPipeline::DEMO])
            ->assertForbidden();
    }

    public function test_tahap_di_luar_daftar_ditolak_validasi(): void
    {
        $prospek = $this->buatProspek();

        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.prospek.tahap', $prospek), ['Kode' => 'TAHAP_KARANGAN'])
            ->assertSessionHasErrors('Kode');
    }

    public function test_halaman_prospek_tertutup_saat_modul_crm_mati(): void
    {
        $prospek = $this->buatProspek();
        $this->matikanCrm();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.prospek.show', $prospek))
            ->assertNotFound();
    }

    public function test_timeline_menggabungkan_peristiwa_dan_aktivitas(): void
    {
        $prospek = $this->buatProspek();
        app(PindahkanTahapProspek::class)->jalankan($prospek, $this->tahap(KatalogTahapPipeline::DEMO));

        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.prospek.show', $prospek))
            ->viewData('page')['props'];

        $sumber = array_column($props['timeline'], 'Sumber');

        $this->assertContains('Aktivitas', $sumber);
        $this->assertContains('Peristiwa', $sumber);
    }

    /**
     * RiwayatTahapProspek sudah lama diisi tetapi tidak pernah dibaca, sehingga
     * "sudah mengendap berapa lama di tahap ini" tidak terjawab di mana pun.
     * Timeline hanya menyebut perpindahannya, bukan durasinya.
     */
    public function test_halaman_prospek_menampilkan_lama_menetap_tiap_tahap(): void
    {
        $prospek = $this->buatProspek();

        // Tahap awal ditulis saat prospek dibuat; mundurkan supaya durasinya terukur.
        RiwayatTahapProspek::query()
            ->where('ProspekId', $prospek->Id)
            ->update(['BerpindahPada' => now()->subDays(10)]);

        $this->travelTo(now()->subDays(4));
        app(PindahkanTahapProspek::class)->jalankan(
            $prospek,
            $this->tahap(KatalogTahapPipeline::DIHUBUNGI),
            'Ditelepon sales.',
        );
        $this->travelBack();

        $props = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_LIHAT]), 'platform')
            ->get(route('pemasaran.prospek.show', $prospek))
            ->viewData('page')['props'];

        $riwayat = $props['riwayatTahap'];

        $this->assertCount(2, $riwayat);
        // Urut naik: tahap awal dulu, lalu perpindahannya.
        $this->assertNull($riwayat[0]['TahapSebelum']);
        $this->assertSame(6, $riwayat[0]['LamaHari']);
        $this->assertFalse($riwayat[0]['Berjalan']);

        $this->assertSame('Dihubungi', $riwayat[1]['TahapSesudah']);
        $this->assertSame('Ditelepon sales.', $riwayat[1]['Alasan']);
        $this->assertSame(4, $riwayat[1]['LamaHari']);
        // Tahap terakhir masih berjalan, jadi durasinya diukur sampai sekarang.
        $this->assertTrue($riwayat[1]['Berjalan']);
    }

    private function buatProspek(): Prospek
    {
        return app(CatatProspek::class)->jalankan(
            ['Nama' => 'Budi', 'Email' => 'budi@contoh.test'],
            SumberProspek::Website,
            (string) Str::ulid(),
        );
    }

    private function tahap(string $kode): TahapPipeline
    {
        return TahapPipeline::query()->where('Kode', $kode)->firstOrFail();
    }

    private function matikanCrm(): void
    {
        FiturPlatform::query()
            ->where('Kode', KatalogFiturPlatform::CRM)
            ->update(['Aktif' => false]);

        app(PemeriksaFiturPlatform::class)->bersihkanCache();
    }
}
