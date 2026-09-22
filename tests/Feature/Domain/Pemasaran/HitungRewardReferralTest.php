<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Langganan\Domain\Contracts\PemberiImbalanLangganan;
use App\Domain\Pemasaran\Application\Services\PelacakReferral;
use App\Domain\Pemasaran\Application\Services\PenerbitKodeReferral;
use App\Domain\Pemasaran\Application\Services\PenghitungRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusReferral;
use App\Domain\Pemasaran\Domain\Enums\StatusRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Referral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RewardReferral;
use App\Domain\Pemasaran\Jobs\ProsesRewardReferral;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Penerbitan dan pemberian imbalan referral (MARKETING.md 20). */
final class HitungRewardReferralTest extends KasusReferral
{
    public function test_imbalan_terbit_dengan_jenis_dan_nilai_dari_programnya(): void
    {
        $referral = $this->sampaiDibayar();

        $reward = RewardReferral::query()->where('ReferralId', $referral->Id)->firstOrFail();

        $this->assertSame(JenisRewardReferral::Perpanjangan, $reward->Jenis);
        $this->assertSame(30.0, (float) $reward->Nilai);
        $this->assertSame($this->perujuk->Id, $reward->OrganisasiPenerimaId);
    }

    /** Satu referral hanya boleh berbuah satu imbalan; indeks unik yang jadi wasitnya. */
    public function test_menerbitkan_dua_kali_tidak_menggandakan_imbalannya(): void
    {
        $referral = $this->sampaiDibayar();
        $penghitung = app(PenghitungRewardReferral::class);

        $pertama = RewardReferral::query()->where('ReferralId', $referral->Id)->firstOrFail();

        $referral->Status = StatusReferral::Paid;
        $referral->save();
        $kedua = $penghitung->terbitkan($referral->refresh());

        $this->assertSame($pertama->Id, $kedua?->Id);
        $this->assertSame(1, RewardReferral::query()->count());
    }

    public function test_imbalan_perpanjangan_memperpanjang_langganan_perujuknya(): void
    {
        $langganan = $this->buatLanggananPerujuk();
        $sebelum = $langganan->UjiCobaSampai;

        $reward = $this->imbalanDari($this->sampaiDibayar());
        app(PenghitungRewardReferral::class)->berikan($reward);

        $sesudah = $langganan->fresh()?->UjiCobaSampai;
        $this->assertNotNull($sebelum);
        $this->assertNotNull($sesudah);
        $this->assertSame(30, (int) $sebelum->diffInDays($sesudah));
        $this->assertSame(StatusRewardReferral::Diberikan, $reward->fresh()?->Status);
    }

    public function test_pemberian_menutup_perjalanan_referralnya(): void
    {
        $this->buatLanggananPerujuk();
        $referral = $this->sampaiDibayar();

        app(PenghitungRewardReferral::class)->berikan($this->imbalanDari($referral));

        $this->assertSame(StatusReferral::Rewarded, $referral->fresh()?->Status);
    }

    /** Inti idempotensinya: pekerjaan yang dijalankan dua kali tidak memberi imbalan dua kali. */
    public function test_menjalankan_pekerjaan_dua_kali_tidak_memberi_imbalan_ganda(): void
    {
        $langganan = $this->buatLanggananPerujuk();
        $reward = $this->imbalanDari($this->sampaiDibayar());

        (new ProsesRewardReferral($reward->Id))->handle(app(PenghitungRewardReferral::class));
        $setelahSekali = $langganan->fresh()?->UjiCobaSampai;

        (new ProsesRewardReferral($reward->Id))->handle(app(PenghitungRewardReferral::class));

        // Layanan dipanggil langsung juga, sebab konsol punya tombol coba lagi yang melewati job-nya.
        app(PenghitungRewardReferral::class)->berikan($reward->refresh());

        $this->assertNotNull($setelahSekali);
        $this->assertTrue($setelahSekali->equalTo($langganan->fresh()?->UjiCobaSampai));
        $this->assertSame(1, $reward->fresh()?->Percobaan);
    }

    /** Billing belum punya buku besar kredit; imbalan itu ditolak terang-terangan, bukan diam-diam. */
    public function test_imbalan_yang_belum_didukung_billing_gagal_dengan_alasannya(): void
    {
        $this->buatLanggananPerujuk();
        $program = $this->buatProgram(JenisRewardReferral::Kredit, 100_000);
        $reward = $this->imbalanDari($this->sampaiDibayar($program));

        $status = app(PenghitungRewardReferral::class)->berikan($reward);

        $this->assertSame(StatusRewardReferral::Gagal, $status);
        $this->assertStringContainsString('Kredit', (string) $reward->fresh()?->Galat);
        $this->assertFalse(app(PemberiImbalanLangganan::class)->mendukung(JenisRewardReferral::Kredit->value));
    }

    /** Imbalan kustom memang diselesaikan manusia, jadi tidak menuntut dukungan Billing. */
    public function test_imbalan_kustom_tercatat_tanpa_menyentuh_billing(): void
    {
        $program = $this->buatProgram(JenisRewardReferral::Kustom, 0);
        $reward = $this->imbalanDari($this->sampaiDibayar($program));

        $status = app(PenghitungRewardReferral::class)->berikan($reward);

        $this->assertSame(StatusRewardReferral::Diberikan, $status);
        $this->assertStringContainsString('manual', (string) $reward->fresh()?->Ringkasan);
    }

    public function test_perujuk_tanpa_langganan_menggagalkan_imbalannya_tanpa_menghilangkannya(): void
    {
        $reward = $this->imbalanDari($this->sampaiDibayar());

        $status = app(PenghitungRewardReferral::class)->berikan($reward);

        $this->assertSame(StatusRewardReferral::Gagal, $status);
        $this->assertSame(1, RewardReferral::query()->count());
    }

    /** Yang gagal tetap ikut antrean berikutnya, dan berhasil begitu penghalangnya hilang. */
    public function test_imbalan_gagal_dapat_dicoba_lagi_setelah_penyebabnya_diperbaiki(): void
    {
        $reward = $this->imbalanDari($this->sampaiDibayar());
        app(PenghitungRewardReferral::class)->berikan($reward);
        $this->assertSame(StatusRewardReferral::Gagal, $reward->fresh()?->Status);

        $this->buatLanggananPerujuk();
        $this->artisan('pemasaran:proses-reward-referral')->assertSuccessful();
        app(PenghitungRewardReferral::class)->berikan($reward->refresh());

        $this->assertSame(StatusRewardReferral::Diberikan, $reward->fresh()?->Status);
        $this->assertSame(2, $reward->fresh()?->Percobaan);
    }

    public function test_imbalan_dapat_dibatalkan_selama_belum_diberikan(): void
    {
        $reward = $this->imbalanDari($this->sampaiDibayar());

        app(PenghitungRewardReferral::class)->batalkan($reward, 'Ditolak tim growth.');

        $this->assertSame(StatusRewardReferral::Dibatalkan, $reward->fresh()?->Status);
    }

    public function test_imbalan_yang_sudah_diberikan_tidak_dapat_dibatalkan(): void
    {
        $this->buatLanggananPerujuk();
        $reward = $this->imbalanDari($this->sampaiDibayar());
        app(PenghitungRewardReferral::class)->berikan($reward);

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenghitungRewardReferral::class)->batalkan($reward->refresh(), 'Terlambat.');
    }

    public function test_referral_yang_belum_dibayar_tidak_menerbitkan_imbalan(): void
    {
        $pengunjung = (string) Str::ulid();
        $referral = app(PelacakReferral::class)->catatKlik($this->kodePerujuk->Kode, $pengunjung);
        $this->assertNotNull($referral);

        $this->assertNull(app(PenghitungRewardReferral::class)->terbitkan($referral));
        $this->assertSame(0, RewardReferral::query()->count());
    }

    private function sampaiDibayar(?ProgramReferral $program = null): Referral
    {
        $program ??= $this->program;
        $kode = app(PenerbitKodeReferral::class)->untuk($program, $this->perujuk->Id);

        $pengunjung = (string) Str::ulid();
        app(PelacakReferral::class)->catatKlik($kode->Kode, $pengunjung);

        $prospek = $this->buatProspek($pengunjung);
        $this->mulaiTrial($prospek);
        $this->bayarUntukOrganisasiUji();

        return Referral::query()->where('PengenalPengunjung', $pengunjung)->firstOrFail();
    }

    private function imbalanDari(Referral $referral): RewardReferral
    {
        return RewardReferral::query()->where('ReferralId', $referral->Id)->firstOrFail();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
