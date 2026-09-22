<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\PengirimEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonsenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Jobs\KirimEmailPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Unsubscribe, daftar supresi, dan penegakannya (Gate 34, MARKETING.md 27). */
final class SuppressionListTest extends KasusEmailPemasaran
{
    public function test_tautan_berhenti_langganan_mencabut_konsen_dan_menyupresi(): void
    {
        $pengiriman = $this->kirimSatu();

        $this->get($this->tautanBerhentiLangganan($pengiriman))->assertOk();

        $this->assertFalse(app(LayananKonsen::class)->disetujui('budi@pabrik.test'));
        $this->assertTrue(app(LayananKonsen::class)->disupresi('budi@pabrik.test'));
        $this->assertSame(StatusPengirimanEmail::Unsubscribe, $pengiriman->fresh()?->Status);
    }

    /** Inti Gate 34: sisa sequence tidak boleh berangkat setelah orangnya berhenti. */
    public function test_pesan_berikutnya_tidak_pernah_berangkat_setelah_unsubscribe(): void
    {
        $sequence = $this->buatSequence([0, 1, 2]);
        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());

        $pertama = PengirimanEmailPemasaran::query()->orderBy('JadwalPada')->firstOrFail();
        (new KirimEmailPemasaran($pertama->Id))->handle(app(PengirimEmailPemasaran::class));
        $this->assertCount(1, $this->penyedia->terkirim);

        $this->get($this->tautanBerhentiLangganan($pertama))->assertOk();

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(3));

        foreach (PengirimanEmailPemasaran::query()->where('Status', StatusPengirimanEmail::Terjadwal->value)->get() as $sisa) {
            (new KirimEmailPemasaran($sisa->Id))->handle(app(PengirimEmailPemasaran::class));
        }

        $this->assertCount(1, $this->penyedia->terkirim);
        $this->assertSame(2, PengirimanEmailPemasaran::query()
            ->where('Status', StatusPengirimanEmail::Unsubscribe->value)
            ->where('Id', '!=', $pertama->Id)
            ->count());
    }

    public function test_tautan_tanpa_tanda_tangan_ditolak(): void
    {
        $pengiriman = $this->kirimSatu();

        $this->get(route('publik.berhenti-langganan', ['pengiriman' => $pengiriman->Id]))
            ->assertForbidden();

        $this->assertFalse(app(LayananKonsen::class)->disupresi('budi@pabrik.test'));
    }

    public function test_konsen_baru_tidak_menghidupkan_alamat_yang_sudah_disupresi(): void
    {
        $konsen = app(LayananKonsen::class);
        $konsen->cabut('budi@pabrik.test');

        $konsen->catat('budi@pabrik.test', true, SumberKonsen::Formulir);

        $this->assertTrue($konsen->disetujui('budi@pabrik.test'));
        $this->assertFalse($konsen->bolehDikirimi('budi@pabrik.test'));
    }

    public function test_supresi_mengabaikan_beda_huruf_dan_spasi(): void
    {
        $konsen = app(LayananKonsen::class);
        $konsen->cabut('  BUDI@Pabrik.Test ');

        $this->assertTrue($konsen->disupresi('budi@pabrik.test'));
        $this->assertFalse($konsen->bolehDikirimi('Budi@PABRIK.test'));
    }

    public function test_pendaftaran_sequence_ditolak_setelah_unsubscribe(): void
    {
        $prospek = $this->buatProspek();
        app(LayananKonsen::class)->cabut((string) $prospek->Email, AlasanSupresi::Unsubscribe, $prospek);

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $prospek);
    }

    public function test_prospek_tanpa_catatan_konsen_tidak_boleh_dikirimi(): void
    {
        $prospek = $this->buatProspek('tanpa@konsen.test', denganKonsen: false);

        $this->assertFalse(app(LayananKonsen::class)->bolehDikirimi((string) $prospek->Email));

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $prospek);
    }

    public function test_bounce_keras_memasukkan_alamat_ke_daftar_supresi(): void
    {
        $pengiriman = $this->kirimSatu();

        app(PengirimEmailPemasaran::class)->perbaruiStatus(
            $pengiriman,
            StatusPengirimanEmail::Bounce,
            'Mailbox tidak ada.',
        );

        $this->assertTrue(app(LayananKonsen::class)->disupresi('budi@pabrik.test'));
        $this->assertSame(
            AlasanSupresi::Bounce,
            DaftarSupresi::query()->where('Email', 'budi@pabrik.test')->firstOrFail()->Alasan,
        );
    }

    /** Supresi berdiri sendiri: setelah bounce consent-nya masih positif, kirimannya tetap harus berhenti. */
    public function test_supresi_saja_sudah_menghentikan_kiriman_walau_consent_masih_positif(): void
    {
        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $this->buatProspek());
        $pengiriman = PengirimanEmailPemasaran::query()->firstOrFail();

        app(LayananKonsen::class)->supresi('budi@pabrik.test', AlasanSupresi::Bounce);
        $this->assertTrue(app(LayananKonsen::class)->disetujui('budi@pabrik.test'));

        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $this->assertSame([], $this->penyedia->terkirim);
        $this->assertSame(StatusPengirimanEmail::Unsubscribe, $pengiriman->fresh()?->Status);
    }

    public function test_menyupresi_dua_kali_tidak_menggandakan_baris(): void
    {
        $konsen = app(LayananKonsen::class);

        $konsen->cabut('budi@pabrik.test');
        $konsen->cabut('budi@pabrik.test');

        $this->assertSame(1, DaftarSupresi::query()->where('Email', 'budi@pabrik.test')->count());
    }

    /** Riwayat consent adalah bukti: pencabutan menambah baris, tidak menimpa yang lama. */
    public function test_riwayat_konsen_tetap_terbaca_sebagai_bukti(): void
    {
        $prospek = $this->buatProspek();
        $awal = KonsenPemasaran::query()->where('Email', 'budi@pabrik.test')->firstOrFail();

        app(LayananKonsen::class)->cabut('budi@pabrik.test', AlasanSupresi::Unsubscribe, $prospek);

        $riwayat = KonsenPemasaran::query()
            ->where('Email', 'budi@pabrik.test')
            ->orderBy('DicatatPada')
            ->get();

        $this->assertCount(2, $riwayat);
        $this->assertTrue($riwayat[0]->Diberikan);
        $this->assertFalse($riwayat[1]->Diberikan);
        $this->assertSame(SumberKonsen::Unsubscribe, $riwayat[1]->Sumber);
        $this->assertNotSame('', (string) $riwayat[0]->VersiKebijakan);

        $awal->Diberikan = false;
        $this->expectException(AturanBisnisDilanggar::class);
        $awal->save();
    }

    private function kirimSatu(): PengirimanEmailPemasaran
    {
        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $this->buatProspek());

        $pengiriman = PengirimanEmailPemasaran::query()->firstOrFail();
        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        return $pengiriman->refresh();
    }

    private function tautanBerhentiLangganan(PengirimanEmailPemasaran $pengiriman): string
    {
        foreach ($this->penyedia->terkirim as $pesan) {
            if ($pesan->kepada === $pengiriman->Email && $pesan->urlBerhentiLangganan !== null) {
                return $pesan->urlBerhentiLangganan;
            }
        }

        $this->fail('Email yang terkirim tidak memuat tautan berhenti langganan.');
    }
}
