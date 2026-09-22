<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\MulaiTrial;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PendaftaranSequence;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;

/** Sequence onboarding trial ditunjuk lewat setelan, bukan ditulis di kode (MARKETING.md 15). */
final class SequenceTrialOnboardingTest extends KasusEmailPemasaran
{
    private ?Organisasi $organisasi = null;

    public function test_trial_mendaftarkan_prospek_ke_sequence_yang_disetel(): void
    {
        $sequence = $this->sequenceTerpasang();
        $prospek = $this->buatProspek();

        app(MulaiTrial::class)->jalankan($this->organisasi()->Id, $prospek);

        $this->assertSame(1, PendaftaranSequence::query()
            ->where('SequenceEmailPemasaranId', $sequence->Id)
            ->where('ProspekId', $prospek->Id)
            ->count());
        $this->assertSame(2, PengirimanEmailPemasaran::query()->count());
    }

    public function test_tanpa_setelan_trial_tidak_mengirim_apa_pun(): void
    {
        $this->buatSequence([0, 2]);
        $prospek = $this->buatProspek();

        app(MulaiTrial::class)->jalankan($this->organisasi()->Id, $prospek);

        $this->assertSame(0, PendaftaranSequence::query()->count());
    }

    public function test_sequence_yang_ditunjuk_tetapi_nonaktif_diabaikan(): void
    {
        $sequence = $this->buatSequence([0], aktif: false);
        $this->pasangSetelan($sequence);
        $prospek = $this->buatProspek();

        app(MulaiTrial::class)->jalankan($this->organisasi()->Id, $prospek);

        $this->assertSame(0, PendaftaranSequence::query()->count());
    }

    /** Trial tetap jalan walau orangnya tidak boleh dikirimi surat; email bukan syarat mencoba produk. */
    public function test_prospek_tanpa_consent_tetap_mendapat_trialnya(): void
    {
        $this->sequenceTerpasang();
        $prospek = $this->buatProspek();
        app(LayananKonsen::class)->cabut((string) $prospek->Email, AlasanSupresi::Unsubscribe, $prospek);

        $trial = app(MulaiTrial::class)->jalankan($this->organisasi()->Id, $prospek);

        $this->assertNotNull($trial->Id);
        $this->assertSame(0, PendaftaranSequence::query()->count());
    }

    public function test_trial_tanpa_prospek_tidak_mendaftarkan_siapa_pun(): void
    {
        $this->sequenceTerpasang();

        app(MulaiTrial::class)->jalankan($this->organisasi()->Id);

        $this->assertSame(0, PendaftaranSequence::query()->count());
    }

    private function sequenceTerpasang(): SequenceEmailPemasaran
    {
        $sequence = $this->buatSequence([0, 2]);
        $this->pasangSetelan($sequence);

        return $sequence;
    }

    private function pasangSetelan(SequenceEmailPemasaran $sequence): void
    {
        app(LayananKonfigurasiPemasaran::class)->simpan(
            KatalogKonfigurasiPemasaran::EMAIL_SEQUENCE_TRIAL,
            $sequence->Kode,
        );
    }

    private function organisasi(): Organisasi
    {
        return $this->organisasi ??= Organisasi::create([
            'Kode' => 'ORG-SEQ-'.uniqid(),
            'Nama' => 'Organisasi Sequence',
            'Status' => 'Aktif',
        ]);
    }
}
