<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PengirimEmailPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusPendaftaranSequence;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanEmail;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Jobs\KirimEmailPemasaran;
use App\Domain\Pemasaran\Jobs\SinkronkanStatusProvider;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

/** Sequence email dan idempotensinya (Gate 34, MARKETING.md 15, 36). */
final class SequenceEmailTest extends KasusEmailPemasaran
{
    public function test_pendaftaran_menjadwalkan_seluruh_langkah(): void
    {
        $sequence = $this->buatSequence([0, 1, 3]);

        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());

        $this->assertSame(3, PengirimanEmailPemasaran::query()->count());
        $this->assertSame(
            [StatusPengirimanEmail::Terjadwal],
            PengirimanEmailPemasaran::query()->pluck('Status')->unique()->values()->all(),
        );
    }

    public function test_jadwal_tiap_langkah_mengikuti_hari_relatifnya(): void
    {
        $sequence = $this->buatSequence([0, 2, 5]);

        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());

        $jadwal = PengirimanEmailPemasaran::query()->orderBy('JadwalPada')->pluck('JadwalPada');
        $mulai = CarbonImmutable::now();

        $this->assertTrue($jadwal[0]->equalTo($mulai));
        $this->assertTrue($jadwal[1]->equalTo($mulai->addDays(2)));
        $this->assertTrue($jadwal[2]->equalTo($mulai->addDays(5)));
    }

    public function test_mendaftar_dua_kali_tidak_menggandakan_kiriman(): void
    {
        $sequence = $this->buatSequence([0, 1]);
        $prospek = $this->buatProspek();
        $aksi = app(DaftarkanKeSequence::class);

        $pertama = $aksi->jalankan($sequence, $prospek);
        $kedua = $aksi->jalankan($sequence, $prospek);

        $this->assertSame($pertama->Id, $kedua->Id);
        $this->assertSame(2, PengirimanEmailPemasaran::query()->count());
    }

    public function test_menjalankan_ulang_pekerjaan_tidak_mengirim_dua_kali(): void
    {
        $pengiriman = $this->jadwalkanSatu();

        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));
        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $this->assertCount(1, $this->penyedia->terkirim);
        $this->assertSame(StatusPengirimanEmail::Dikirim, $pengiriman->fresh()?->Status);
    }

    public function test_kegagalan_penyedia_menyisakan_kiriman_untuk_dicoba_lagi(): void
    {
        $pengiriman = $this->jadwalkanSatu();
        $this->penyedia->gagalkan = true;

        try {
            (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));
            $this->fail('Kegagalan penyedia seharusnya dilemparkan agar antrean mencoba ulang.');
        } catch (\RuntimeException) {
            $this->assertSame(StatusPengirimanEmail::Terjadwal, $pengiriman->fresh()?->Status);
        }

        $this->penyedia->gagalkan = false;
        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $this->assertCount(1, $this->penyedia->terkirim);
        $this->assertSame(StatusPengirimanEmail::Dikirim, $pengiriman->fresh()?->Status);
    }

    public function test_variabel_template_terisi(): void
    {
        $this->jadwalkanSatu();
        $pengiriman = PengirimanEmailPemasaran::query()->firstOrFail();

        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $pesan = $this->penyedia->terkirim[0];
        $this->assertSame('Halo Budi', $pesan->subjek);
        $this->assertStringContainsString('Halo Budi', $pesan->isiHtml);
        $this->assertStringNotContainsString('{{', $pesan->isiHtml);
    }

    public function test_tautan_berhenti_langganan_ikut_terkirim(): void
    {
        $pengiriman = $this->jadwalkanSatu();

        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $this->assertStringContainsString(
            'berhenti-langganan',
            (string) $this->penyedia->terkirim[0]->urlBerhentiLangganan,
        );
    }

    public function test_sequence_nonaktif_menolak_pendaftaran(): void
    {
        $sequence = $this->buatSequence([0], aktif: false);

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());
    }

    public function test_menghentikan_sequence_membatalkan_kiriman_yang_belum_berangkat(): void
    {
        $sequence = $this->buatSequence([0, 5]);
        $pendaftaran = app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());

        app(DaftarkanKeSequence::class)->hentikan($pendaftaran);

        $this->assertSame(StatusPendaftaranSequence::Dihentikan, $pendaftaran->fresh()?->Status);
        $this->assertSame(0, PengirimanEmailPemasaran::query()->count());
    }

    public function test_status_dari_penyedia_hanya_boleh_maju(): void
    {
        $pengiriman = $this->jadwalkanSatu();
        (new KirimEmailPemasaran($pengiriman->Id))->handle(app(PengirimEmailPemasaran::class));

        $idPesan = (string) $pengiriman->fresh()?->IdPesanPenyedia;

        $this->penyedia->laporkan($idPesan, StatusPengirimanEmail::Diklik);
        app(SinkronkanStatusProvider::class)->handle($this->penyedia, app(PengirimEmailPemasaran::class));
        $this->assertSame(StatusPengirimanEmail::Diklik, $pengiriman->fresh()?->Status);

        // Laporan "terkirim" yang tiba terlambat tidak boleh memundurkan yang sudah diklik.
        $this->penyedia->laporkan($idPesan, StatusPengirimanEmail::Terkirim);
        app(SinkronkanStatusProvider::class)->handle($this->penyedia, app(PengirimEmailPemasaran::class));

        $this->assertSame(StatusPengirimanEmail::Diklik, $pengiriman->fresh()?->Status);
    }

    public function test_penjadwal_hanya_mengantrekan_yang_sudah_jatuh_tempo(): void
    {
        $sequence = $this->buatSequence([0, 7]);
        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());

        $this->artisan('pemasaran:kirim-antrian-email')->assertSuccessful();

        $this->assertSame(1, PengirimanEmailPemasaran::query()
            ->where('Status', StatusPengirimanEmail::Terjadwal->value)
            ->where('JadwalPada', '>', now())
            ->count());
    }

    public function test_cap_harian_membatasi_jumlah_yang_diantrekan(): void
    {
        app(LayananKonfigurasiPemasaran::class)->simpan(KatalogKonfigurasiPemasaran::EMAIL_CAP_HARIAN, 2);
        Queue::fake();

        foreach (['a@pabrik.test', 'b@pabrik.test', 'c@pabrik.test'] as $email) {
            app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $this->buatProspek($email));
        }

        $this->artisan('pemasaran:kirim-antrian-email')->assertSuccessful();

        Queue::assertPushed(KirimEmailPemasaran::class, 2);
    }

    /** Cap dihitung dari yang benar-benar berangkat, bukan dari yang diantrekan. */
    public function test_cap_harian_menghitung_yang_sudah_terkirim_hari_ini(): void
    {
        app(LayananKonfigurasiPemasaran::class)->simpan(KatalogKonfigurasiPemasaran::EMAIL_CAP_HARIAN, 2);

        foreach (['a@pabrik.test', 'b@pabrik.test', 'c@pabrik.test'] as $email) {
            app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $this->buatProspek($email));
        }

        $pertama = PengirimanEmailPemasaran::query()->orderBy('Id')->firstOrFail();
        (new KirimEmailPemasaran($pertama->Id))->handle(app(PengirimEmailPemasaran::class));

        Queue::fake();
        $this->artisan('pemasaran:kirim-antrian-email')->assertSuccessful();

        Queue::assertPushed(KirimEmailPemasaran::class, 1);
    }

    private function jadwalkanSatu(): PengirimanEmailPemasaran
    {
        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $this->buatProspek());

        return PengirimanEmailPemasaran::query()->firstOrFail();
    }
}
