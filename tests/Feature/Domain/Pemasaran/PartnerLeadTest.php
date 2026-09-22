<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusLeadPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Lead kiriman partner: pengiriman, klaim, perjalanan status, dan peristiwanya (MARKETING.md 21). */
final class PartnerLeadTest extends KasusPartner
{
    public function test_partner_mengirim_lead_lewat_portalnya(): void
    {
        $isi = $this->isiLead();

        $this->aktingSebagaiPartner($this->partner)
            ->post($this->urlPortal('/lead'), $isi)
            ->assertRedirect();

        $lead = LeadPartner::query()->where('Email', $isi['Email'])->first();

        $this->assertNotNull($lead);
        $this->assertSame($this->partner->Id, $lead->PartnerId);
        $this->assertSame(StatusLeadPartner::Dikirim, $lead->Status);
    }

    /** Lead yang masuk harus ikut menjadi prospek, agar tim penjualan mengerjakannya di CRM yang sama. */
    public function test_lead_melahirkan_prospek_dengan_sumber_partner(): void
    {
        $isi = $this->isiLead();

        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $isi);

        $prospek = Prospek::query()->whereKey($lead->ProspekId)->first();

        $this->assertNotNull($prospek);
        $this->assertSame($isi['Email'], $prospek->Email);
        $this->assertSame('Partner', $prospek->Sumber);
    }

    /** Lead partner bukan formulir; menghitungnya sebagai formulir akan menggelembungkan konversi halaman. */
    public function test_lead_partner_tidak_dicatat_sebagai_formulir_dikirim(): void
    {
        app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());

        $this->assertSame(
            0,
            EventPemasaran::query()->where('Jenis', KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM)->count(),
        );
    }

    public function test_pengiriman_lead_mencatat_peristiwa_partner_mengirim_lead(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());

        $peristiwa = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::PARTNER_MENGIRIM_LEAD)
            ->first();

        $this->assertNotNull($peristiwa);
        $this->assertSame($lead->Id, $peristiwa->DataTambahan['LeadPartnerId'] ?? null);
        $this->assertSame($this->partner->Id, $peristiwa->DataTambahan['PartnerId'] ?? null);
    }

    /** Satu alamat satu pemilik: partner kedua tidak boleh mengklaim lead yang sudah dikirim. */
    public function test_alamat_yang_sudah_diklaim_tidak_dapat_dikirim_partner_lain(): void
    {
        $isi = $this->isiLead();
        app(PelacakLeadPartner::class)->kirim($this->partner, $isi);

        $partnerLain = $this->buatPartner($this->programPartner);

        $this->expectException(AturanBisnisDilanggar::class);
        app(PelacakLeadPartner::class)->kirim($partnerLain, $isi);
    }

    /** Partner berhak tahu apakah alamatnya miliknya sendiri atau sudah diklaim orang lain. */
    public function test_penolakan_klaim_menyebut_siapa_yang_memilikinya(): void
    {
        $isi = $this->isiLead();
        app(PelacakLeadPartner::class)->kirim($this->partner, $isi);

        $this->expectExceptionMessage('sudah diklaim partner lain');
        app(PelacakLeadPartner::class)->kirim($this->buatPartner($this->programPartner), $isi);
    }

    public function test_partner_yang_mengirim_ulang_alamatnya_sendiri_diberi_tahu(): void
    {
        $isi = $this->isiLead();
        app(PelacakLeadPartner::class)->kirim($this->partner, $isi);

        $this->expectExceptionMessage('Anda sudah pernah mengirim lead');
        app(PelacakLeadPartner::class)->kirim($this->partner, $isi);
    }

    public function test_partner_yang_ditangguhkan_tidak_dapat_mengirim_lead(): void
    {
        $ditangguhkan = $this->buatPartner($this->programPartner, StatusPartner::Ditangguhkan);

        $this->expectException(AturanBisnisDilanggar::class);
        app(PelacakLeadPartner::class)->kirim($ditangguhkan, $this->isiLead());
    }

    public function test_program_nonaktif_tidak_menerima_lead(): void
    {
        $program = $this->buatProgramPartner(aktif: false);
        $partner = $this->buatPartner($program);

        $this->expectException(AturanBisnisDilanggar::class);
        app(PelacakLeadPartner::class)->kirim($partner, $this->isiLead());
    }

    /** Trial menaikkan status lead, jadi portal partner melihat perjalanannya tanpa ditulis tangan. */
    public function test_lead_menjadi_trial_ketika_prospeknya_memulai_trial(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        $prospek = Prospek::query()->whereKey($lead->ProspekId)->firstOrFail();

        $this->mulaiTrial($prospek);

        $this->assertSame(StatusLeadPartner::Trial, $lead->fresh()?->Status);
        $this->assertSame($this->organisasi->Id, $lead->fresh()?->OrganisasiId);
    }

    public function test_status_lead_tidak_pernah_mundur(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        $prospek = Prospek::query()->whereKey($lead->ProspekId)->firstOrFail();

        $this->mulaiTrial($prospek);
        app(PelacakLeadPartner::class)->terima($lead->fresh() ?? $lead);

        $this->assertSame(StatusLeadPartner::Trial, $lead->fresh()?->Status);
    }

    public function test_lead_yang_ditolak_tidak_dapat_diterima_kembali(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        app(PelacakLeadPartner::class)->tolak($lead, 'Sudah pelanggan kami.');

        $this->expectException(AturanBisnisDilanggar::class);
        app(PelacakLeadPartner::class)->terima($lead->fresh() ?? $lead);
    }

    /** Jendela atribusi menutup lead yang tidak pernah berbuah, supaya komisinya tidak lahir bertahun kemudian. */
    public function test_lead_yang_lewat_jendela_atribusi_ditolak_otomatis(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(181));
        $jumlah = app(PelacakLeadPartner::class)->kedaluwarsakan();

        $this->assertSame(1, $jumlah);
        $this->assertSame(StatusLeadPartner::Ditolak, $lead->fresh()?->Status);
    }

    public function test_lead_yang_sudah_menjadi_trial_tidak_ikut_kedaluwarsa(): void
    {
        $lead = app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        $prospek = Prospek::query()->whereKey($lead->ProspekId)->firstOrFail();
        $this->mulaiTrial($prospek);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(181));

        $this->assertSame(0, app(PelacakLeadPartner::class)->kedaluwarsakan());
        $this->assertSame(StatusLeadPartner::Trial, $lead->fresh()?->Status);
    }
}
