<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenerbitKontenSosial;
use App\Domain\Pemasaran\Application\Services\PenjadwalKontenSosial;
use App\Domain\Pemasaran\Application\Services\PenyusunTautanDistribusi;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\Enums\StatusKontenSosial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DistribusiKontenSosial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Satu konten utama disebarkan ke banyak channel, tiap channel punya isinya sendiri (Gate 38.02). */
final class DistribusiSosialTest extends KasusSosial
{
    /** Bagian pertama Gate 38.02: satu artikel dijadwalkan ke lebih dari satu channel. */
    public function test_satu_konten_dapat_disebarkan_ke_banyak_channel(): void
    {
        $konten = $this->buatKonten($this->buatKampanye());
        $penjadwal = app(PenjadwalKontenSosial::class);
        $penerbit = app(PenerbitKontenSosial::class);

        foreach ([ChannelSosial::LinkedIn, ChannelSosial::Instagram, ChannelSosial::TikTok] as $channel) {
            $distribusi = $this->buatDistribusi($konten, $channel);
            $jadwal = $penjadwal->jadwalkan($distribusi, CarbonImmutable::now());
            $penerbit->terbitkan($jadwal);
        }

        $this->assertEqualsCanonicalizing(
            ['LinkedIn', 'Instagram', 'TikTok'],
            $this->penyedia->channelTerbit(),
        );
        $this->assertSame(3, DistribusiKontenSosial::query()
            ->where('Status', StatusKontenSosial::Terbit->value)->count());
    }

    /** Tiap distribusi membawa caption dan medianya sendiri, bukan salinan yang sama. */
    public function test_tiap_distribusi_membawa_captionnya_sendiri(): void
    {
        $konten = $this->buatKonten();
        $penjadwal = app(PenjadwalKontenSosial::class);
        $penerbit = app(PenerbitKontenSosial::class);

        $linkedin = $this->buatDistribusi($konten, ChannelSosial::LinkedIn);
        $linkedin->Caption = 'Versi panjang untuk LinkedIn.';
        $linkedin->save();

        $tiktok = $this->buatDistribusi($konten, ChannelSosial::TikTok);
        $tiktok->Caption = 'Versi pendek.';
        $tiktok->save();

        foreach ([$linkedin, $tiktok] as $distribusi) {
            $penerbit->terbitkan($penjadwal->jadwalkan($distribusi, CarbonImmutable::now()));
        }

        $caption = array_map(fn ($satu): string => $satu->caption, $this->penyedia->diterbitkan);

        $this->assertContains('Versi panjang untuk LinkedIn.', $caption);
        $this->assertContains('Versi pendek.', $caption);
    }

    /** Satu channel hanya boleh punya satu distribusi; dua caption untuk channel yang sama adalah salah ketik. */
    public function test_channel_yang_sama_tidak_dapat_digandakan(): void
    {
        $konten = $this->buatKonten();
        $this->buatDistribusi($konten, ChannelSosial::LinkedIn);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->buatDistribusi($konten, ChannelSosial::LinkedIn);
    }

    /** Inti tautan UTM: trafik dari posting ini harus terbaca sebagai kampanyenya. */
    public function test_tautan_distribusi_membawa_utm_kampanyenya(): void
    {
        $konten = $this->buatKonten($this->buatKampanye('promo-q1'));
        $distribusi = $this->buatDistribusi($konten, ChannelSosial::LinkedIn);

        $tautan = app(PenyusunTautanDistribusi::class)->untuk($distribusi);

        $this->assertNotNull($tautan);
        $this->assertStringContainsString('utm_campaign=promo-q1', $tautan);
        $this->assertStringContainsString('utm_source=linkedin', $tautan);
        $this->assertStringContainsString('utm_medium=social', $tautan);
        $this->assertStringContainsString('utm_content=artikel-audit', $tautan);
    }

    /** UTM yang ditulis sendiri di distribusinya menang atas bawaan channelnya. */
    public function test_utm_distribusi_menimpa_bawaan_channelnya(): void
    {
        $konten = $this->buatKonten($this->buatKampanye());
        $distribusi = $this->buatDistribusi($konten, ChannelSosial::LinkedIn);
        $distribusi->UtmSource = 'newsletter';
        $distribusi->UtmMedium = 'partner';
        $distribusi->save();

        $tautan = app(PenyusunTautanDistribusi::class)->untuk($distribusi->fresh());

        $this->assertStringContainsString('utm_source=newsletter', (string) $tautan);
        $this->assertStringContainsString('utm_medium=partner', (string) $tautan);
    }

    /** Konten tanpa kampanye tidak boleh memalsukan utm_campaign kosong di tautannya. */
    public function test_konten_tanpa_kampanye_tidak_menulis_utm_campaign(): void
    {
        $konten = $this->buatKonten();
        $distribusi = $this->buatDistribusi($konten, ChannelSosial::LinkedIn);

        $tautan = (string) app(PenyusunTautanDistribusi::class)->untuk($distribusi);

        $this->assertStringNotContainsString('utm_campaign', $tautan);
        $this->assertStringContainsString('utm_source=linkedin', $tautan);
    }

    /** Parameter yang sudah ada di tautannya dipertahankan, bukan ditimpa. */
    public function test_parameter_yang_sudah_ada_di_tautan_dipertahankan(): void
    {
        $konten = $this->buatKonten($this->buatKampanye());
        $distribusi = $this->buatDistribusi(
            $konten,
            ChannelSosial::LinkedIn,
            tautan: 'https://amanpoll.test/artikel/audit?ref=nawala',
        );

        $tautan = (string) app(PenyusunTautanDistribusi::class)->untuk($distribusi);

        $this->assertStringContainsString('ref=nawala', $tautan);
        $this->assertStringContainsString('&utm_source=linkedin', $tautan);
    }

    /** Tautan yang diterbitkan ke penyedia adalah tautan yang sudah bertanda UTM, bukan yang mentah. */
    public function test_penyedia_menerima_tautan_yang_sudah_ber_utm(): void
    {
        $konten = $this->buatKonten($this->buatKampanye('promo-q1'));
        $distribusi = $this->buatDistribusi($konten);

        app(PenerbitKontenSosial::class)->terbitkan(
            app(PenjadwalKontenSosial::class)->jadwalkan($distribusi, CarbonImmutable::now()),
        );

        $this->assertStringContainsString(
            'utm_campaign=promo-q1',
            (string) $this->penyedia->diterbitkan[0]->tautan,
        );
    }

    /** Peta transisi ditegakkan: draf tidak dapat melompat langsung ke terbit. */
    public function test_transisi_status_di_luar_peta_ditolak(): void
    {
        $konten = $this->buatKonten();
        $distribusi = $this->buatDistribusi($konten);
        $distribusi->Status = StatusKontenSosial::Draf;
        $distribusi->save();

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenjadwalKontenSosial::class)->pindahkan($distribusi, StatusKontenSosial::Terbit);
    }

    public function test_yang_sudah_terbit_tidak_dapat_berpindah_lagi(): void
    {
        $konten = $this->buatKonten();
        $distribusi = $this->buatDistribusi($konten);
        app(PenerbitKontenSosial::class)->terbitkan(
            app(PenjadwalKontenSosial::class)->jadwalkan($distribusi, CarbonImmutable::now()),
        );

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenjadwalKontenSosial::class)->pindahkan($distribusi->fresh(), StatusKontenSosial::Draf);
    }

    /** Channel bergambar tanpa media akan ditolak penyedianya, jadi ditolak sejak dijadwalkan. */
    public function test_channel_wajib_media_tanpa_media_ditolak(): void
    {
        $konten = $this->buatKonten();
        $distribusi = $this->buatDistribusi($konten, ChannelSosial::Instagram, mediaUrl: '');

        $this->expectException(AturanBisnisDilanggar::class);

        app(PenjadwalKontenSosial::class)->jadwalkan($distribusi, CarbonImmutable::now());
    }

    /** Kegagalan penyedia menandai distribusinya Gagal beserta alasannya, bukan diam-diam terbit. */
    public function test_kegagalan_penyedia_menandai_distribusinya_gagal(): void
    {
        $konten = $this->buatKonten();
        $distribusi = $this->buatDistribusi($konten);
        $this->penyedia->gagalkan = true;

        $status = app(PenerbitKontenSosial::class)->terbitkan(
            app(PenjadwalKontenSosial::class)->jadwalkan($distribusi, CarbonImmutable::now()),
        );

        $segar = $distribusi->fresh();

        $this->assertSame(StatusKontenSosial::Gagal, $status);
        $this->assertSame(StatusKontenSosial::Gagal, $segar?->Status);
        $this->assertNotNull($segar?->Galat);
        $this->assertNull($segar?->TerbitPada);
    }
}
