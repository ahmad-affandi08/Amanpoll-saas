<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\PengirimWhatsAppPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjadwalWhatsAppPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjawabWhatsAppMasuk;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\KanalPesan;
use App\Domain\Pemasaran\Domain\Enums\StatusPengirimanWhatsApp;
use App\Domain\Pemasaran\Domain\Enums\StatusPersetujuanTemplateWa;
use App\Domain\Pemasaran\Domain\Enums\SumberKonsen;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Opt-in, STOP, supresi, cap, dan persetujuan template (Gate 38.01). */
final class WhatsAppConsentTest extends KasusWhatsApp
{
    /** Inti Gate 38.01: nomor yang mengirim STOP tidak pernah menerima pesan berikutnya. */
    public function test_nomor_yang_mengirim_stop_tidak_menerima_pesan_lagi(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        app(PenjawabWhatsAppMasuk::class)->jawab('081234567890', 'STOP');

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:stop');

        $this->assertNotNull($pengiriman);
        $this->assertSame(
            StatusPengirimanWhatsApp::Unsubscribe,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman),
        );
        $this->assertSame([], $this->penyedia->nomorTerkirim());
    }

    /** STOP menulis konsen negatif sekaligus memasukkan nomornya ke daftar supresi. */
    public function test_stop_mencatat_konsen_negatif_dan_supresi(): void
    {
        $this->buatProspek();

        $hasil = app(PenjawabWhatsAppMasuk::class)->jawab('081234567890', 'stop');

        $this->assertTrue($hasil['Berhenti']);
        $this->assertFalse(app(LayananKonsen::class)->disetujui('081234567890', KanalPesan::WhatsApp));
        $this->assertTrue(app(LayananKonsen::class)->disupresi('081234567890', KanalPesan::WhatsApp));
    }

    /** Berhenti dari WhatsApp bukan berhenti dari email; keduanya ledger yang sama tetapi kanal berbeda. */
    public function test_berhenti_whatsapp_tidak_mencabut_konsen_email(): void
    {
        $prospek = $this->buatProspek();
        app(LayananKonsen::class)->catat('budi@pabrik.test', true, SumberKonsen::Formulir, $prospek);

        app(PenjawabWhatsAppMasuk::class)->jawab('081234567890', 'STOP');

        $this->assertFalse(app(LayananKonsen::class)->disetujui('081234567890', KanalPesan::WhatsApp));
        $this->assertTrue(app(LayananKonsen::class)->bolehDikirimi('budi@pabrik.test'));
    }

    /** Nomor ditulis bermacam bentuk; semuanya harus mengenai satu orang yang sama. */
    public function test_bentuk_nomor_yang_berbeda_dianggap_satu_nomor(): void
    {
        $this->buatProspek();
        app(PenjawabWhatsAppMasuk::class)->jawab('+62 812-3456-7890', 'STOP');

        foreach (['081234567890', '6281234567890', '+6281234567890', '81234567890'] as $bentuk) {
            $this->assertTrue(
                app(LayananKonsen::class)->disupresi($bentuk, KanalPesan::WhatsApp),
                "Bentuk {$bentuk} seharusnya sudah disupresi.",
            );
        }
    }

    /** Tanpa opt-in sama sekali, jawabannya tidak; bukan diam-diam dikirim. */
    public function test_nomor_tanpa_opt_in_tidak_dikirimi(): void
    {
        $prospek = $this->buatProspek(setuju: false);
        $template = $this->buatTemplate();

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:tanpa-optin');

        $this->assertNotNull($pengiriman);
        $this->assertSame(
            StatusPengirimanWhatsApp::Unsubscribe,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman),
        );
    }

    /** Daftar supresi dihormati walau konsennya positif; keduanya syarat yang berbeda. */
    public function test_nomor_tersupresi_tidak_dikirimi_walau_konsennya_positif(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();
        app(LayananKonsen::class)->supresi('081234567890', AlasanSupresi::Manual, null, KanalPesan::WhatsApp);

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:supresi');

        $this->assertNotNull($pengiriman);
        $this->assertSame(
            StatusPengirimanWhatsApp::Unsubscribe,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman),
        );
    }

    /** Bagian kedua Gate 38.01: template tanpa persetujuan penyedia tidak dapat berangkat. */
    public function test_template_belum_disetujui_tidak_dapat_dijadwalkan(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate(StatusPersetujuanTemplateWa::Diajukan);

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:belum-disetujui');
    }

    /** Persetujuan dapat dicabut setelah pesan dijadwalkan, jadi diperiksa lagi saat berangkat. */
    public function test_persetujuan_yang_dicabut_menghentikan_kiriman_yang_sudah_terjadwal(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:dicabut');
        $this->assertNotNull($pengiriman);

        $template->StatusPersetujuan = StatusPersetujuanTemplateWa::Ditangguhkan;
        $template->save();

        $this->assertSame(
            StatusPengirimanWhatsApp::Ditolak,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman->fresh()),
        );
        $this->assertSame([], $this->penyedia->nomorTerkirim());
    }

    /** Dua syarat yang berbeda: penyedia menyetujuinya, dan kami belum menonaktifkannya. */
    public function test_template_nonaktif_tidak_dapat_dikirim_walau_sudah_disetujui(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate(aktif: false);

        $this->assertFalse($template->siapKirim());

        try {
            app(PenjadwalWhatsAppPemasaran::class)
                ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:nonaktif');
            $this->fail('Template nonaktif seharusnya ditolak sejak dijadwalkan.');
        } catch (AturanBisnisDilanggar) {
            $this->assertSame([], $this->penyedia->nomorTerkirim());
        }
    }

    /** Menonaktifkan template setelah dijadwalkan pun harus menghentikan kirimannya. */
    public function test_template_yang_dinonaktifkan_menghentikan_kiriman_terjadwal(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:dinonaktifkan');
        $this->assertNotNull($pengiriman);

        $template->Aktif = false;
        $template->save();

        $this->assertSame(
            StatusPengirimanWhatsApp::Ditolak,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman->fresh()),
        );
        $this->assertSame([], $this->penyedia->nomorTerkirim());
    }

    public function test_template_yang_disetujui_benar_benar_berangkat(): void
    {
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:berangkat');
        $this->assertNotNull($pengiriman);

        $this->assertSame(
            StatusPengirimanWhatsApp::Dikirim,
            app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman),
        );
        $this->assertSame(['6281234567890'], $this->penyedia->nomorTerkirim());
        $this->assertStringContainsString('Budi Pabrik', $this->penyedia->terkirim[0]->isiTeks);
    }

    /** Frequency cap dibaca dari setelan dan benar-benar menahan pesan berikutnya. */
    public function test_frequency_cap_menahan_pesan_melebihi_batas(): void
    {
        $this->setelCap(2);
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        foreach (range(1, 3) as $ke) {
            $pengiriman = app(PenjadwalWhatsAppPemasaran::class)
                ->jadwalkan($prospek, $template, CarbonImmutable::now(), "uji:cap:{$ke}");
            $this->assertNotNull($pengiriman);
            $status = app(PengirimWhatsAppPemasaran::class)->kirim($pengiriman);

            $harapan = $ke <= 2 ? StatusPengirimanWhatsApp::Dikirim : StatusPengirimanWhatsApp::Ditolak;
            $this->assertSame($harapan, $status, "Pesan ke-{$ke} salah status.");
        }

        $this->assertCount(2, $this->penyedia->terkirim);
    }

    /** Cap dihitung dalam jendela; setelah jendelanya lewat, nomor yang sama boleh dikirimi lagi. */
    public function test_cap_terbuka_lagi_setelah_jendelanya_lewat(): void
    {
        $this->setelCap(1, jendelaJam: 6);
        $prospek = $this->buatProspek();
        $template = $this->buatTemplate();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
        $pertama = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:jendela:1');
        $this->assertNotNull($pertama);
        app(PengirimWhatsAppPemasaran::class)->kirim($pertama);

        CarbonImmutable::setTestNow('2026-06-15 16:00:00');
        $kedua = app(PenjadwalWhatsAppPemasaran::class)
            ->jadwalkan($prospek, $template, CarbonImmutable::now(), 'uji:jendela:2');
        $this->assertNotNull($kedua);

        $this->assertSame(
            StatusPengirimanWhatsApp::Dikirim,
            app(PengirimWhatsAppPemasaran::class)->kirim($kedua),
        );

        CarbonImmutable::setTestNow();
    }

    /** Menu dan balasannya dibaca dari tabel, sehingga dapat diubah tanpa rilis. */
    public function test_menu_dan_balasannya_dibaca_dari_data(): void
    {
        $this->buatMenu();

        $penjawab = app(PenjawabWhatsAppMasuk::class);

        $this->assertStringContainsString('1. Lihat Demo', $penjawab->menu());
        $this->assertSame('Trial 14 hari tanpa kartu.', $penjawab->jawab('628123', '2')['Balasan']);
        $this->assertStringContainsString('3. Harga', $penjawab->jawab('628123', '99')['Balasan']);
    }

    /** Kata berhenti pun dari setelan, bukan ditulis di kode. */
    public function test_kata_berhenti_dapat_diubah_lewat_setelan(): void
    {
        $this->buatProspek();
        $this->setel(KatalogKonfigurasiPemasaran::WHATSAPP_KATA_BERHENTI, 'CUKUP');

        $this->assertFalse(app(PenjawabWhatsAppMasuk::class)->jawab('081234567890', 'STOP')['Berhenti']);
        $this->assertTrue(app(PenjawabWhatsAppMasuk::class)->jawab('081234567890', 'cukup')['Berhenti']);
    }

    /** STOP dari nomor yang belum jadi prospek tetap harus mengunci nomornya. */
    public function test_stop_dari_nomor_asing_tetap_disupresi(): void
    {
        app(PenjawabWhatsAppMasuk::class)->jawab('08999000111', 'BERHENTI');

        $this->assertTrue(app(LayananKonsen::class)->disupresi('08999000111', KanalPesan::WhatsApp));
        $this->assertSame(1, DaftarSupresi::query()->where('Kanal', KanalPesan::WhatsApp->value)->count());
    }

    public function test_prospek_tanpa_nomor_tidak_dijadwalkan(): void
    {
        $prospek = Prospek::create([
            'Nama' => 'Tanpa Nomor',
            'Email' => 'tanpa@pabrik.test',
            'Sumber' => SumberProspek::Website->value,
        ]);

        $this->assertNull(app(PenjadwalWhatsAppPemasaran::class)->jadwalkan(
            $prospek,
            $this->buatTemplate(),
            CarbonImmutable::now(),
            'uji:tanpa-nomor',
        ));
    }

    private function setelCap(int $cap, int $jendelaJam = 24): void
    {
        $this->setel(KatalogKonfigurasiPemasaran::WHATSAPP_CAP_PER_NOMOR, (string) $cap);
        $this->setel(KatalogKonfigurasiPemasaran::WHATSAPP_CAP_JENDELA_JAM, (string) $jendelaJam);
    }

    private function setel(string $kunci, string $nilai): void
    {
        app(LayananKonfigurasiPemasaran::class)->simpan($kunci, $nilai);
    }
}
