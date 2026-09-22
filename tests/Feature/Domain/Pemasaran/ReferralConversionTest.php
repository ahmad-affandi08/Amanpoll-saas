<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use App\Domain\Pemasaran\Application\Services\PenerbitKodeReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Referral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RewardReferral;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Perjalanan referral dari klik sampai pembayaran, dan anti self-referral (Gate 36, MARKETING.md 20). */
final class ReferralConversionTest extends KasusReferral
{
    /** Inti Gate 36: satu referral tertelusur utuh dari klik sampai imbalannya terbit. */
    public function test_referral_tertelusur_dari_klik_sampai_pembayaran(): void
    {
        $pengunjung = (string) Str::ulid();

        $referral = app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);
        $this->assertSame(StatusReferral::Diklik, $referral?->Status);

        $prospek = $this->buatProspek($pengunjung);
        $this->assertSame(StatusReferral::Lead, $referral->fresh()?->Status);

        $this->mulaiTrial($prospek);
        $this->assertSame(StatusReferral::Trial, $referral->fresh()?->Status);

        $this->bayarUntukOrganisasiUji();

        $akhir = $referral->fresh();
        $this->assertSame(StatusReferral::RewardPending, $akhir?->Status);
        $this->assertNotNull($akhir?->DiklikPada);
        $this->assertNotNull($akhir?->MenjadiLeadPada);
        $this->assertNotNull($akhir?->MenjadiTrialPada);
        $this->assertNotNull($akhir?->MenjadiPaidPada);
        $this->assertSame($this->organisasi->Id, $akhir?->OrganisasiBaruId);

        $reward = RewardReferral::query()->where('ReferralId', $referral->Id)->firstOrFail();
        $this->assertSame(StatusRewardReferral::Tertunda, $reward->Status);
        $this->assertSame($this->perujuk->Id, $reward->OrganisasiPenerimaId);
    }

    public function test_tiap_tahap_meninggalkan_peristiwanya_sendiri(): void
    {
        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $prospek = $this->buatProspek($pengunjung);
        $this->mulaiTrial($prospek);
        $this->bayarUntukOrganisasiUji();

        foreach ([
            KatalogPeristiwaPemasaran::REFERRAL_DIKLIK,
            KatalogPeristiwaPemasaran::REFERRAL_MENJADI_LEAD,
            KatalogPeristiwaPemasaran::REFERRAL_MENJADI_TRIAL,
            KatalogPeristiwaPemasaran::REFERRAL_MENJADI_PAID,
        ] as $jenis) {
            $this->assertSame(1, EventPemasaran::query()->where('Jenis', $jenis)->count(), $jenis);
        }
    }

    public function test_klik_kedua_dari_pengunjung_sama_tidak_melahirkan_referral_kedua(): void
    {
        $pengunjung = (string) Str::ulid();
        $pelacak = app(PelacakReferral::class);

        $pertama = $pelacak->catatKlik($this->kodePerujuk->Kode, $pengunjung);
        $kedua = $pelacak->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $this->assertSame($pertama?->Id, $kedua?->Id);
        $this->assertSame(1, Referral::query()->count());
    }

    public function test_kode_tidak_dikenal_atau_program_nonaktif_tidak_dilacak(): void
    {
        $pelacak = app(PelacakReferral::class);

        $this->assertNull($pelacak->catatKlik('TIDAKADA123', (string) Str::ulid()));

        $this->program->Aktif = false;
        $this->program->save();

        $this->assertNull($pelacak->catatKlik($this->kodePerujuk->Kode, (string) Str::ulid()));
        $this->assertSame(0, Referral::query()->count());
    }

    /** Paruh kedua Gate 36: organisasi tidak boleh mereferensikan dirinya sendiri. */
    public function test_organisasi_tidak_dapat_mereferensikan_dirinya_sendiri(): void
    {
        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $prospek = $this->buatProspek($pengunjung);
        app(PelacakReferral::class)->tandaiTrial($prospek, $this->perujuk->Id);

        $referral = Referral::query()->firstOrFail();
        $this->assertSame(StatusReferral::Ditolak, $referral->Status);
        $this->assertStringContainsString('perujuk', (string) $referral->AlasanDitolak);
    }

    /** Pengguna organisasi perujuk yang mengklik tautannya sendiri lalu mengisi formulir. */
    public function test_email_pengguna_perujuk_ditolak_saat_menjadi_lead(): void
    {
        Pengguna::query()->withoutGlobalScopes()->create([
            'OrganisasiId' => $this->perujuk->Id,
            'Nama' => 'Orang Dalam',
            'Email' => 'orang.dalam@perujuk.test',
            'KataSandi' => 'rahasia-sekali',
            'Status' => 'Aktif',
        ]);

        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $this->buatProspekBeremail('orang.dalam@perujuk.test', $pengunjung);

        $referral = Referral::query()->firstOrFail();
        $this->assertSame(StatusReferral::Ditolak, $referral->Status);
        $this->assertStringContainsString('email', (string) $referral->AlasanDitolak);
    }

    public function test_referral_yang_ditolak_tidak_pernah_menghasilkan_imbalan(): void
    {
        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $prospek = $this->buatProspek($pengunjung);
        app(PelacakReferral::class)->tandaiTrial($prospek, $this->perujuk->Id);

        $this->bayarUntukOrganisasiUji();

        $this->assertSame(StatusReferral::Ditolak, Referral::query()->firstOrFail()->Status);
        $this->assertSame(0, RewardReferral::query()->count());
    }

    public function test_pengunjung_tanpa_referral_tidak_menghasilkan_apa_pun(): void
    {
        $prospek = $this->buatProspek();
        $this->mulaiTrial($prospek);
        $this->bayarUntukOrganisasiUji();

        $this->assertSame(0, Referral::query()->count());
        $this->assertSame(0, RewardReferral::query()->count());
    }

    /** Status hanya boleh maju; kabar yang tiba terlambat tidak memundurkan perjalanan. */
    public function test_status_referral_hanya_boleh_maju(): void
    {
        $pengunjung = (string) Str::ulid();
        $referral = app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);
        $this->assertNotNull($referral);

        app(PelacakReferral::class)->majukan($referral, StatusReferral::Trial);
        app(PelacakReferral::class)->majukan($referral, StatusReferral::Lead);

        $this->assertSame(StatusReferral::Trial, $referral->fresh()?->Status);
    }

    public function test_referral_yang_lewat_jendelanya_ditutup(): void
    {
        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(91));
        $ditutup = app(PelacakReferral::class)->kedaluwarsakan();

        $this->assertSame(1, $ditutup);
        $this->assertSame(StatusReferral::Kedaluwarsa, Referral::query()->firstOrFail()->Status);
    }

    /** Yang sudah dibayar tidak ikut ditutup walau jendelanya lewat. */
    public function test_referral_yang_sudah_berbuah_tidak_ikut_kedaluwarsa(): void
    {
        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);

        $prospek = $this->buatProspek($pengunjung);
        $this->mulaiTrial($prospek);
        $this->bayarUntukOrganisasiUji();

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(365));
        $this->assertSame(0, app(PelacakReferral::class)->kedaluwarsakan());
        $this->assertSame(StatusReferral::RewardPending, Referral::query()->firstOrFail()->Status);
    }

    public function test_satu_pelanggan_satu_kode_per_program(): void
    {
        $penerbit = app(PenerbitKodeReferral::class);

        $pertama = $penerbit->untuk($this->program, $this->perujuk->Id);
        $kedua = $penerbit->untuk($this->program, $this->perujuk->Id);

        $this->assertSame($pertama->Id, $kedua->Id);
        $this->assertSame($this->kodePerujuk->Kode, $kedua->Kode);
    }

    public function test_pelanggan_berbeda_mendapat_kode_berbeda(): void
    {
        $lain = Organisasi::create([
            'Kode' => 'ORG-LAIN-'.uniqid(),
            'Nama' => 'Pelanggan Lain',
            'Status' => 'Aktif',
        ]);

        $kodeLain = app(PenerbitKodeReferral::class)->untuk($this->program, $lain->Id);

        $this->assertNotSame($this->kodePerujuk->Kode, $kodeLain->Kode);
    }

    private function buatProspekBeremail(string $email, string $pengenal): void
    {
        app(CatatProspek::class)->jalankan(
            ['Nama' => 'Orang Dalam', 'Email' => $email],
            SumberProspek::Website,
            $pengenal,
        );
    }
}
