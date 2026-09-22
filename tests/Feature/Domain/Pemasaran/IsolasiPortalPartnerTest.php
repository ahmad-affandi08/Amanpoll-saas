<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\MulaiTrial;
use App\Domain\Pemasaran\Application\Services\PelacakLeadPartner;
use App\Domain\Pemasaran\Application\Services\PenghitungKomisiPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KomisiPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Support\Str;

/** Isolasi portal partner: satu partner tidak pernah melihat milik partner lain (Gate 38.09). */
final class IsolasiPortalPartnerTest extends KasusPartner
{
    private Partner $partnerLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerLain = $this->buatPartner($this->programPartner);
    }

    public function test_portal_menolak_partner_yang_belum_masuk(): void
    {
        $this->get($this->urlPortal('/'))->assertRedirect($this->urlPortal('/masuk'));
    }

    public function test_partner_hanya_melihat_lead_miliknya_sendiri(): void
    {
        $milikSendiri = app(PelacakLeadPartner::class)
            ->kirim($this->partner, $this->isiLead(['NamaPerusahaan' => 'PT Milik Sendiri']));
        $milikOrangLain = app(PelacakLeadPartner::class)
            ->kirim($this->partnerLain, $this->isiLead(['NamaPerusahaan' => 'PT Milik Orang Lain']));

        $respons = $this->aktingSebagaiPartner($this->partner)->get($this->urlPortal('/'));
        $respons->assertOk();

        $lead = $this->propInertia($respons->viewData('page'), 'lead');
        $id = array_column($lead, 'Id');

        $this->assertContains($milikSendiri->Id, $id);
        $this->assertNotContains($milikOrangLain->Id, $id);
    }

    public function test_partner_hanya_melihat_komisi_miliknya_sendiri(): void
    {
        $komisiSendiri = $this->komisiUntuk($this->partner, $this->organisasi);
        $komisiLain = $this->komisiUntuk($this->partnerLain, $this->buatOrganisasiLain());

        $respons = $this->aktingSebagaiPartner($this->partner)->get($this->urlPortal('/'));
        $id = array_column($this->propInertia($respons->viewData('page'), 'komisi'), 'Id');

        $this->assertContains($komisiSendiri->Id, $id);
        $this->assertNotContains($komisiLain->Id, $id);
    }

    /** Ringkasan diturunkan dari kueri yang sama, jadi angkanya tidak boleh membocorkan milik orang lain. */
    public function test_ringkasan_portal_hanya_menghitung_milik_partner_yang_masuk(): void
    {
        app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        app(PelacakLeadPartner::class)->kirim($this->partnerLain, $this->isiLead());
        app(PelacakLeadPartner::class)->kirim($this->partnerLain, $this->isiLead());

        $respons = $this->aktingSebagaiPartner($this->partner)->get($this->urlPortal('/'));
        $ringkasan = $this->propInertia($respons->viewData('page'), 'ringkasan');

        $this->assertSame(1, $ringkasan['Lead']);
    }

    /** Lead disimpan dengan PartnerId dari sesi, bukan dari kiriman; memaksanya tidak mengubah pemiliknya. */
    public function test_partner_tidak_dapat_mengirim_lead_atas_nama_partner_lain(): void
    {
        $isi = $this->isiLead(['PartnerId' => $this->partnerLain->Id]);

        $this->aktingSebagaiPartner($this->partner)
            ->post($this->urlPortal('/lead'), $isi)
            ->assertRedirect();

        $lead = LeadPartner::query()->where('Email', $isi['Email'])->firstOrFail();

        $this->assertSame($this->partner->Id, $lead->PartnerId);
    }

    public function test_partner_yang_ditangguhkan_kehilangan_sesinya(): void
    {
        $this->aktingSebagaiPartner($this->partner)->get($this->urlPortal('/'))->assertOk();

        $this->partner->forceFill(['Status' => StatusPartner::Ditangguhkan->value])->save();

        $this->get($this->urlPortal('/'))->assertRedirect($this->urlPortal('/masuk'));
    }

    /** Guard partner berdiri sendiri: admin platform bukan partner dan sebaliknya. */
    public function test_admin_platform_tidak_dapat_membuka_portal_partner(): void
    {
        $this->actingAs($this->buatAdmin([], superAdmin: true), 'platform')
            ->get($this->urlPortal('/'))
            ->assertRedirect($this->urlPortal('/masuk'));
    }

    public function test_partner_tidak_dapat_membuka_konsol_platform(): void
    {
        $this->aktingSebagaiPartner($this->partner)
            ->get('http://localhost/admin-platform/pemasaran/partner')
            ->assertRedirect();
    }

    /** Konsol platform memang melihat seluruh partner; itulah bedanya dengan portal. */
    public function test_konsol_platform_melihat_lead_seluruh_partner(): void
    {
        app(PelacakLeadPartner::class)->kirim($this->partner, $this->isiLead());
        app(PelacakLeadPartner::class)->kirim($this->partnerLain, $this->isiLead());

        $respons = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PARTNER_LIHAT]), 'platform')
            ->get('http://localhost/admin-platform/pemasaran/partner');

        $respons->assertOk();
        $this->assertCount(2, $this->propInertia($respons->viewData('page'), 'lead'));
    }

    public function test_konsol_partner_menuntut_izin(): void
    {
        $this->actingAs($this->buatAdmin([]), 'platform')
            ->get('http://localhost/admin-platform/pemasaran/partner')
            ->assertForbidden();
    }

    /** Host partner tidak boleh diindeks, sama seperti host dashboard. */
    public function test_host_partner_menandai_dirinya_tidak_terindeks(): void
    {
        $this->get($this->urlPortal('/masuk'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_host_partner_melarang_seluruh_perayapan(): void
    {
        $this->get($this->urlPortal('/robots.txt'))
            ->assertOk()
            ->assertSee('Disallow: /');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function propInertia(mixed $halaman, string $kunci): array
    {
        $this->assertIsArray($halaman);
        $props = $halaman['props'] ?? [];
        $this->assertIsArray($props);
        $nilai = $props[$kunci] ?? [];
        $this->assertIsArray($nilai);

        return $nilai;
    }

    private function buatOrganisasiLain(): Organisasi
    {
        return Organisasi::create([
            'Kode' => 'ORG-LAIN-'.uniqid(),
            'Nama' => 'Organisasi Lain',
            'Status' => 'Aktif',
        ]);
    }

    private function komisiUntuk(Partner $partner, Organisasi $organisasi): KomisiPartner
    {
        $lead = app(PelacakLeadPartner::class)->kirim($partner, $this->isiLead());
        $prospek = Prospek::query()->whereKey($lead->ProspekId)->firstOrFail();

        app(MulaiTrial::class)
            ->jalankan($organisasi->Id, $prospek);

        $pembayaranId = $this->bayarUntukOrganisasi($organisasi, 200_000);
        $komisi = app(PenghitungKomisiPartner::class)->dariPembayaran($pembayaranId);

        $this->assertNotNull($komisi, 'Komisi seharusnya lahir dari pembayaran '.Str::limit($pembayaranId, 8));

        return $komisi;
    }
}
