<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemasaran\Application\Actions\CatatAktivasiTrial;
use App\Domain\Pemasaran\Application\Actions\PerpanjangTrial;
use App\Domain\Pemasaran\Application\Actions\PindahkanStatusTrial;
use App\Domain\Pemasaran\Application\Services\PembacaKonfigurasiTrial;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ButirAktivasiTrial;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Aktivasi trial (MARKETING.md 12, 23, 36). */
final class TrialActivationTest extends KasusTrial
{
    public function test_trial_dimulai_dengan_organisasi_tercentang(): void
    {
        $trial = $this->mulaiTrial();

        $this->assertSame(StatusTrial::Setup, $trial->Status);
        $this->assertSame([ButirAktivasi::OrganisasiDibuat->value], $trial->fresh(['butir'])?->butirSelesai());
    }

    public function test_durasi_trial_dibaca_dari_domain_langganan(): void
    {
        config(['amanpoll.langganan.hari_uji_coba' => 21]);

        $trial = $this->mulaiTrial();

        $this->assertSame(21, (int) $trial->MulaiPada->diffInDays($trial->BerakhirPada));
        $this->assertSame(21, app(PembacaKonfigurasiTrial::class)->berlaku()->durasiHari);
    }

    public function test_trial_dimulai_mencatat_peristiwanya(): void
    {
        $trial = $this->mulaiTrial($this->buatProspek());

        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TRIAL_DIMULAI)
            ->where('OrganisasiId', $trial->OrganisasiId)
            ->exists());
    }

    public function test_membuat_lokasi_mencentang_butirnya(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatLokasi();

        $this->assertContains(ButirAktivasi::LokasiDibuat->value, $trial->fresh(['butir'])?->butirSelesai() ?? []);
    }

    public function test_lokasi_kedua_tidak_mencentang_ulang(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatLokasi();
        $this->buatLokasi();

        $this->assertSame(1, ButirAktivasiTrial::query()
            ->where('TrialId', $trial->Id)
            ->where('Butir', ButirAktivasi::LokasiDibuat->value)
            ->count());
    }

    public function test_butir_pertama_memajukan_setup_menjadi_aktif(): void
    {
        $trial = $this->mulaiTrial();

        $this->buatLokasi();

        $this->assertSame(StatusTrial::Aktif, $trial->fresh()?->Status);
    }

    public function test_seluruh_butir_wajib_menjadikan_trial_teraktivasi(): void
    {
        $trial = $this->mulaiTrial($this->buatProspek());

        $this->lengkapiButirWajib();

        $segar = $trial->fresh();
        $this->assertSame(StatusTrial::Teraktivasi, $segar?->Status);
        $this->assertNotNull($segar?->TeraktivasiPada);
    }

    public function test_aktivasi_mencatat_peristiwa_teraktivasi(): void
    {
        $trial = $this->mulaiTrial();

        $this->lengkapiButirWajib();

        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TRIAL_TERAKTIVASI)
            ->where('OrganisasiId', $trial->OrganisasiId)
            ->exists());
    }

    public function test_butir_tidak_dicatat_untuk_organisasi_tanpa_trial(): void
    {
        $this->buatLokasi();

        $this->assertSame(0, ButirAktivasiTrial::query()->count());
    }

    public function test_trial_yang_sudah_selesai_tidak_menerima_butir_baru(): void
    {
        $trial = $this->mulaiTrial();
        app(PindahkanStatusTrial::class)->jalankan($trial, StatusTrial::Dibatalkan);

        app(CatatAktivasiTrial::class)->jalankan($this->organisasi->Id, ButirAktivasi::AsetPertama);

        $this->assertNotContains(
            ButirAktivasi::AsetPertama->value,
            $trial->fresh(['butir'])?->butirSelesai() ?? [],
        );
    }

    public function test_transisi_status_yang_tidak_diizinkan_ditolak(): void
    {
        $trial = $this->mulaiTrial();

        $this->expectException(AturanBisnisDilanggar::class);

        app(PindahkanStatusTrial::class)->jalankan($trial, StatusTrial::Teraktivasi);
    }

    public function test_perubahan_status_tercatat_di_audit(): void
    {
        $trial = $this->mulaiTrial();

        app(PindahkanStatusTrial::class)->jalankan($trial, StatusTrial::Dibatalkan, 'Diminta pelanggan.');

        $this->assertTrue(CatatanAudit::query()
            ->withoutGlobalScopes()
            ->where('Aksi', 'Trial.StatusBerubah')
            ->where('EntitasId', $trial->Id)
            ->exists());
    }

    public function test_perpanjangan_menggeser_tanggal_dan_masa_uji_coba_langganan(): void
    {
        $trial = $this->mulaiTrial();
        $langganan = $this->buatLangganan();
        $akhirSemula = $trial->BerakhirPada;
        $ujiCobaSemula = $langganan->UjiCobaSampai;

        app(PerpanjangTrial::class)->jalankan($trial, 7);

        $segar = $trial->fresh();
        $this->assertSame(7, $segar?->HariPerpanjangan);
        $this->assertTrue($segar?->BerakhirPada->equalTo($akhirSemula->addDays(7)));
        $this->assertTrue($langganan->fresh()?->UjiCobaSampai?->greaterThan($ujiCobaSemula));
    }

    public function test_perpanjangan_melebihi_kebijakan_ditolak(): void
    {
        $trial = $this->mulaiTrial();
        $maks = app(PembacaKonfigurasiTrial::class)->berlaku()->perpanjanganMaksHari;

        $this->expectException(AturanBisnisDilanggar::class);

        app(PerpanjangTrial::class)->jalankan($trial, $maks + 1);
    }

    public function test_perpanjangan_bertahap_tetap_dibatasi_totalnya(): void
    {
        $trial = $this->mulaiTrial();
        $maks = app(PembacaKonfigurasiTrial::class)->berlaku()->perpanjanganMaksHari;

        app(PerpanjangTrial::class)->jalankan($trial, $maks);

        $this->expectException(AturanBisnisDilanggar::class);

        app(PerpanjangTrial::class)->jalankan($trial->fresh() ?? $trial, 1);
    }

    public function test_trial_yang_lewat_masanya_ditutup_penjadwal(): void
    {
        $trial = $this->mulaiTrial();

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addDays(30));
        $this->artisan('pemasaran:kedaluwarsakan-trial')->assertSuccessful();

        $this->assertSame(StatusTrial::Kadaluarsa, $trial->fresh()?->Status);
    }

    public function test_trial_yang_masih_berjalan_tidak_ikut_ditutup(): void
    {
        $trial = $this->mulaiTrial();

        $this->artisan('pemasaran:kedaluwarsakan-trial')->assertSuccessful();

        $this->assertSame(StatusTrial::Setup, $trial->fresh()?->Status);
    }

    public function test_memulai_trial_dua_kali_tidak_menggandakannya(): void
    {
        $pertama = $this->mulaiTrial();
        $kedua = $this->mulaiTrial();

        $this->assertSame($pertama->Id, $kedua->Id);
        $this->assertSame(1, Trial::query()->count());
    }

    private function lengkapiButirWajib(): void
    {
        foreach (ButirAktivasi::wajibUntukAktivasi() as $butir) {
            app(CatatAktivasiTrial::class)->jalankan($this->organisasi->Id, $butir);
        }
    }

    private function buatLokasi(): Lokasi
    {
        return Lokasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'LOK-'.Str::random(6),
            'Nama' => 'Lokasi Uji',
            'Tipe' => 'Gedung',
        ]);
    }
}
