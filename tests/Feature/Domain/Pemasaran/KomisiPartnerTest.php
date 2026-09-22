<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Events\PeristiwaLangganan;
use App\Domain\Pemasaran\Application\Services\LayananPayoutPartner;
use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Application\Services\PemeriksaAlertPemasaran;
use App\Domain\Pemasaran\Application\Services\PenghitungKomisiPartner;
use App\Domain\Pemasaran\Application\Services\PenghitungKpiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPayoutPartner;
use App\Domain\Pemasaran\Domain\KatalogAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKpiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AlertPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanKomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/** Komisi partner: hanya lahir dari pembayaran yang benar-benar terjadi (Gate 38.09). */
final class KomisiPartnerTest extends KasusPartner
{
    public function test_pembayaran_nyata_melahirkan_komisi_sesuai_aturannya(): void
    {
        $this->leadYangJadiPelanggan();

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $komisi = KomisiPartner::query()->where('PartnerId', $this->partner->Id)->first();

        $this->assertNotNull($komisi);
        $this->assertEqualsWithDelta(25_000.0, (float) $komisi->Jumlah, 0.01);
        $this->assertEqualsWithDelta(250_000.0, (float) $komisi->JumlahPembayaran, 0.01);
        $this->assertSame(StatusKomisiPartner::Tertunda, $komisi->Status);
    }

    /**
     * Inti Gate 38.09: peristiwa dapat disiarkan siapa saja, dan muatannya dapat
     * mengarang jumlah. Yang menentukan tetap baris pembayaran di domain Langganan.
     */
    public function test_peristiwa_dengan_pembayaran_palsu_tidak_melahirkan_komisi(): void
    {
        $this->leadYangJadiPelanggan();

        Event::dispatch(new PeristiwaLangganan(
            PeristiwaLangganan::PEMBAYARAN_BERHASIL,
            $this->organisasi->Id,
            null,
            ['PembayaranId' => (string) Str::ulid(), 'Jumlah' => 900_000_000],
        ));

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    /** Jumlah komisi dibaca dari pembayarannya, bukan dari angka yang menumpang di peristiwanya. */
    public function test_jumlah_di_muatan_peristiwa_tidak_mengubah_besar_komisi(): void
    {
        $this->leadYangJadiPelanggan();
        $pembayaranId = $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        KomisiPartner::query()->delete();

        Event::dispatch(new PeristiwaLangganan(
            PeristiwaLangganan::PEMBAYARAN_BERHASIL,
            $this->organisasi->Id,
            null,
            ['PembayaranId' => $pembayaranId, 'Jumlah' => 900_000_000],
        ));

        $this->assertEqualsWithDelta(
            25_000.0,
            (float) (KomisiPartner::query()->value('Jumlah') ?? 0),
            0.01,
        );
    }

    public function test_satu_pembayaran_hanya_melahirkan_satu_komisi(): void
    {
        $this->leadYangJadiPelanggan();
        $pembayaranId = $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        app(PenghitungKomisiPartner::class)->dariPembayaran($pembayaranId);
        app(PenghitungKomisiPartner::class)->dariPembayaran($pembayaranId);

        $this->assertSame(1, KomisiPartner::query()->where('PembayaranId', $pembayaranId)->count());
    }

    /** Pembayaran yang gagal bukan uang yang masuk, jadi ia tidak pernah melahirkan komisi. */
    public function test_pembayaran_gagal_tidak_melahirkan_komisi(): void
    {
        $this->leadYangJadiPelanggan();

        $pembayaranId = $this->bayarUntukOrganisasi(
            $this->organisasi,
            250_000,
            StatusPembayaranLangganan::Gagal,
        );

        app(PenghitungKomisiPartner::class)->dariPembayaran($pembayaranId);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    /** Pembayaran tertunda belum punya tanggal bayar; menunggu bukan sama dengan terjadi. */
    public function test_pembayaran_menunggu_tidak_melahirkan_komisi(): void
    {
        $this->leadYangJadiPelanggan();

        $pembayaranId = $this->bayarUntukOrganisasi(
            $this->organisasi,
            250_000,
            StatusPembayaranLangganan::Menunggu,
        );

        app(PenghitungKomisiPartner::class)->dariPembayaran($pembayaranId);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    public function test_pembayaran_tanpa_lead_partner_tidak_melahirkan_komisi(): void
    {
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    public function test_lead_yang_ditolak_tidak_pernah_berbuah_komisi(): void
    {
        $lead = $this->leadYangJadiPelanggan();
        app(PelacakLeadPartner::class)->tolak($lead, 'Sudah pelanggan langsung.');

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    public function test_partner_yang_berhenti_tidak_lagi_menerima_komisi(): void
    {
        $this->leadYangJadiPelanggan();
        $this->partner->forceFill(['Status' => StatusPartner::Berhenti->value])->save();

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    /** Partner yang ditangguhkan tetap berhak: leadnya dikirim jauh sebelum penangguhan. */
    public function test_partner_yang_ditangguhkan_tetap_menerima_komisi(): void
    {
        $this->leadYangJadiPelanggan();
        $this->partner->forceFill(['Status' => StatusPartner::Ditangguhkan->value])->save();

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(1, KomisiPartner::query()->count());
    }

    public function test_tanpa_aturan_komisi_tidak_ada_komisi_yang_lahir(): void
    {
        AturanKomisiPartner::query()->delete();
        $this->leadYangJadiPelanggan();

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(0, KomisiPartner::query()->count());
    }

    /** Aturan khusus partner mengalahkan aturan bawaan programnya. */
    public function test_aturan_khusus_partner_mengalahkan_bawaan_program(): void
    {
        $this->buatAturan($this->programPartner, persentase: 25, partner: $this->partner);
        $this->leadYangJadiPelanggan();

        $this->bayarUntukOrganisasi($this->organisasi, 200_000);

        $this->assertEqualsWithDelta(
            50_000.0,
            (float) (KomisiPartner::query()->value('Jumlah') ?? 0),
            0.01,
        );
    }

    public function test_komisi_nominal_tetap_tidak_melampaui_pembayarannya(): void
    {
        AturanKomisiPartner::query()->delete();
        AturanKomisiPartner::create([
            'ProgramPartnerId' => $this->programPartner->Id,
            'Nama' => 'Tetap besar',
            'Jenis' => JenisKomisiPartner::Tetap->value,
            'Nilai' => 500_000,
            'Aktif' => true,
        ]);

        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 100_000);

        $this->assertEqualsWithDelta(
            100_000.0,
            (float) (KomisiPartner::query()->value('Jumlah') ?? 0),
            0.01,
        );
    }

    /** Batas jumlah pembayaran menutup komisi berulang tanpa akhir. */
    public function test_batas_jumlah_pembayaran_dihormati(): void
    {
        AturanKomisiPartner::query()->delete();
        $this->buatAturan($this->programPartner, persentase: 10, maksPembayaran: 1);

        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 100_000);
        $this->bayarUntukOrganisasi($this->organisasi, 100_000);

        $this->assertSame(1, KomisiPartner::query()->count());
    }

    public function test_kelahiran_komisi_mencatat_peristiwa_komisi_partner_dibuat(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $peristiwa = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::KOMISI_PARTNER_DIBUAT)
            ->first();

        $this->assertNotNull($peristiwa);
        $this->assertSame($this->partner->Id, $peristiwa->DataTambahan['PartnerId'] ?? null);
    }

    public function test_pembayaran_menandai_lead_menjadi_pelanggan_berbayar(): void
    {
        $lead = $this->leadYangJadiPelanggan();

        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->assertSame(StatusLeadPartner::Paid, $lead->fresh()?->Status);
    }

    public function test_payout_hanya_mengumpulkan_komisi_yang_sudah_disetujui(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $this->expectException(AturanBisnisDilanggar::class);
        app(LayananPayoutPartner::class)->susun($this->partner);
    }

    public function test_payout_yang_dibayar_menutup_komisinya(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $komisi = KomisiPartner::query()->firstOrFail();
        app(PenghitungKomisiPartner::class)->setujui($komisi);

        $payout = app(LayananPayoutPartner::class)->susun($this->partner);
        app(LayananPayoutPartner::class)->tandaiDibayar($payout, 'TRF-001');

        $this->assertSame(StatusPayoutPartner::Dibayar, $payout->fresh()?->Status);
        $this->assertSame(StatusKomisiPartner::Dibayar, $komisi->fresh()?->Status);
    }

    /** Payout yang batal tidak boleh ikut membunuh komisinya; keduanya kembali ke antrean. */
    public function test_payout_yang_dibatalkan_melepas_komisinya_kembali(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $komisi = KomisiPartner::query()->firstOrFail();
        app(PenghitungKomisiPartner::class)->setujui($komisi);
        $payout = app(LayananPayoutPartner::class)->susun($this->partner);

        app(LayananPayoutPartner::class)->batalkan($payout, 'Rekening salah.');

        $this->assertNull($komisi->fresh()?->PayoutPartnerId);
        $this->assertSame(StatusKomisiPartner::Disetujui, $komisi->fresh()?->Status);
    }

    public function test_komisi_yang_sudah_dibayar_tidak_dapat_dibatalkan(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $komisi = KomisiPartner::query()->firstOrFail();
        app(PenghitungKomisiPartner::class)->setujui($komisi);
        $payout = app(LayananPayoutPartner::class)->susun($this->partner);
        app(LayananPayoutPartner::class)->tandaiDibayar($payout, 'TRF-002');

        $this->expectException(AturanBisnisDilanggar::class);
        app(PenghitungKomisiPartner::class)->batalkan($komisi->fresh() ?? $komisi, 'Salah hitung.');
    }

    /** KPI revenue partner membaca pembayaran, bukan komisi: komisi batal tidak menghapus uang yang masuk. */
    public function test_kpi_revenue_partner_membaca_pembayaran_bukan_komisi(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $komisi = KomisiPartner::query()->firstOrFail();
        app(PenghitungKomisiPartner::class)->batalkan($komisi, 'Salah aturan.');

        $kpi = app(PenghitungKpiPemasaran::class)
            ->hitung(FilterGrowth::dariKueri([]));

        $this->assertEqualsWithDelta(250_000.0, $kpi[KatalogKpiPemasaran::REVENUE_PARTNER], 0.01);
    }

    /** Lead yang ditolak bukan pelanggan kiriman partner, jadi uangnya tidak masuk hitungan. */
    public function test_revenue_partner_tidak_menghitung_lead_yang_ditolak(): void
    {
        $lead = $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);
        app(PelacakLeadPartner::class)->tolak($lead, 'Ternyata pelanggan langsung.');

        $kpi = app(PenghitungKpiPemasaran::class)
            ->hitung(FilterGrowth::dariKueri([]));

        $this->assertEqualsWithDelta(0.0, $kpi[KatalogKpiPemasaran::REVENUE_PARTNER], 0.01);
    }

    public function test_revenue_partner_dihitung_per_partner_bila_difilter(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        $partnerLain = $this->buatPartner($this->programPartner);

        $kpi = app(PenghitungKpiPemasaran::class)
            ->hitung(FilterGrowth::dariKueri(['partner' => $partnerLain->Kode]));

        $this->assertEqualsWithDelta(0.0, $kpi[KatalogKpiPemasaran::REVENUE_PARTNER], 0.01);
    }

    public function test_revenue_partner_dinyatakan_tersedia_di_katalog(): void
    {
        $this->assertContains(KatalogKpiPemasaran::REVENUE_PARTNER, KatalogKpiPemasaran::kunciTersedia());
    }

    /** Komisi yang lama menggantung membangunkan tim growth; ambangnya dari setelan. */
    public function test_alert_menyala_untuk_komisi_yang_lama_tertunda(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        app(PemeriksaAlertPemasaran::class)->periksa(CarbonImmutable::now()->addDays(30));

        $this->assertSame(1, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::KOMISI_PARTNER_TERTUNDA)
            ->count());
    }

    public function test_alert_diam_selama_komisi_belum_melewati_ambangnya(): void
    {
        $this->leadYangJadiPelanggan();
        $this->bayarUntukOrganisasi($this->organisasi, 250_000);

        app(PemeriksaAlertPemasaran::class)->periksa(CarbonImmutable::now()->addDay());

        $this->assertSame(0, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::KOMISI_PARTNER_TERTUNDA)
            ->count());
    }

    /** Lead yang sudah berjalan sampai menjadi pelanggan organisasi uji. */
    private function leadYangJadiPelanggan(?Partner $partner = null): LeadPartner
    {
        $lead = app(PelacakLeadPartner::class)->kirim($partner ?? $this->partner, $this->isiLead());
        $prospek = Prospek::query()->whereKey($lead->ProspekId)->firstOrFail();

        $this->mulaiTrial($prospek);

        return $lead->fresh() ?? $lead;
    }
}
