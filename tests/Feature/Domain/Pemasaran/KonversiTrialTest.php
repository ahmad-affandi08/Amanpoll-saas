<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Langganan\Application\Actions\CatatPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;
use App\Domain\Langganan\Domain\Enums\StatusTagihanLangganan;
use App\Domain\Langganan\Domain\ValueObjects\PeristiwaPembayaran;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use App\Domain\Pemasaran\Application\Actions\CatatAktivasiTrial;
use App\Domain\Pemasaran\Application\Services\PenyusunTimelineProspek;
use App\Domain\Pemasaran\Domain\Enums\ButirAktivasi;
use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Konversi trial dan peristiwa revenue (Gate 33, MARKETING.md 23).
 *
 * Yang dijaga di sini adalah Gate-nya: satu perjalanan dari kunjungan pertama
 * sampai berlangganan harus terbaca utuh dalam satu timeline.
 */
final class KonversiTrialTest extends KasusTrial
{
    public function test_pembayaran_berhasil_mengonversi_trialnya(): void
    {
        $trial = $this->mulaiTrial($this->buatProspek());

        $this->bayar();

        $segar = $trial->fresh();
        $this->assertSame(StatusTrial::Konversi, $segar?->Status);
        $this->assertNotNull($segar?->KonversiPada);
    }

    public function test_konversi_menautkan_langganan_yang_dibayar(): void
    {
        $trial = $this->mulaiTrial();
        $tagihan = $this->buatTagihan();

        $this->bayar($tagihan);

        $this->assertSame($tagihan->LanggananId, $trial->fresh()?->LanggananId);
    }

    public function test_pembayaran_kedua_tidak_mengonversi_ulang(): void
    {
        $trial = $this->mulaiTrial();

        $this->bayar();
        $waktuKonversi = $trial->fresh()?->KonversiPada;

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addHour());
        $this->bayar();

        $this->assertTrue($trial->fresh()?->KonversiPada?->equalTo($waktuKonversi));
    }

    public function test_konversi_memindahkan_prospeknya_ke_tahap_menang(): void
    {
        $prospek = $this->buatProspek();
        $this->mulaiTrial($prospek);

        $this->bayar();

        $this->assertSame(
            KatalogTahapPipeline::MENANG,
            Prospek::query()->with('tahap')->find($prospek->Id)?->tahap?->Kode,
        );
    }

    public function test_pembayaran_gagal_tidak_mengonversi(): void
    {
        $trial = $this->mulaiTrial();

        $this->bayar(status: StatusPembayaranLangganan::Gagal);

        $this->assertNotSame(StatusTrial::Konversi, $trial->fresh()?->Status);
    }

    public function test_peristiwa_revenue_tercatat_pada_organisasinya(): void
    {
        $this->mulaiTrial();

        $this->bayar();

        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::PEMBAYARAN_BERHASIL)
            ->where('OrganisasiId', $this->organisasi->Id)
            ->exists());
    }

    public function test_pembayaran_gagal_tercatat_sebagai_peristiwanya_sendiri(): void
    {
        $this->mulaiTrial();

        $this->bayar(status: StatusPembayaranLangganan::Gagal);

        $this->assertTrue(EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::PEMBAYARAN_GAGAL)
            ->exists());
    }

    public function test_perjalanan_terbaca_utuh_dalam_satu_timeline(): void
    {
        $pengenal = (string) Str::ulid();
        $prospek = $this->buatProspek($pengenal);
        $this->mulaiTrial($prospek);

        foreach (ButirAktivasi::wajibUntukAktivasi() as $butir) {
            app(CatatAktivasiTrial::class)->jalankan($this->organisasi->Id, $butir);
        }

        $this->bayar();

        $jenis = array_column(
            app(PenyusunTimelineProspek::class)->untuk($prospek->fresh() ?? $prospek),
            'Jenis',
        );

        // Dari formulir anonim, lewat trial dan aktivasinya, sampai pembayaran.
        $this->assertContains(KatalogPeristiwaPemasaran::FORMULIR_DIKIRIM, $jenis);
        $this->assertContains(KatalogPeristiwaPemasaran::TRIAL_DIMULAI, $jenis);
        $this->assertContains(KatalogPeristiwaPemasaran::ASET_PERTAMA_DIBUAT, $jenis);
        $this->assertContains(KatalogPeristiwaPemasaran::TRIAL_TERAKTIVASI, $jenis);
        $this->assertContains(KatalogPeristiwaPemasaran::PEMBAYARAN_BERHASIL, $jenis);
    }

    private function bayar(
        ?TagihanLangganan $tagihan = null,
        StatusPembayaranLangganan $status = StatusPembayaranLangganan::Berhasil,
    ): void {
        $tagihan ??= $this->buatTagihan();

        app(CatatPembayaranLangganan::class)->dariPeristiwa('TransferManual', new PeristiwaPembayaran(
            idPeristiwa: (string) Str::ulid(),
            nomorTagihan: (string) $tagihan->Nomor,
            jumlah: (float) $tagihan->Total,
            status: $status,
        ));
    }

    private function buatTagihan(): TagihanLangganan
    {
        $langganan = $this->buatLangganan();

        return TagihanLangganan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'LanggananId' => $langganan->Id,
            'Nomor' => 'INV-'.Str::upper(Str::random(8)),
            'PeriodeMulai' => CarbonImmutable::now()->toDateString(),
            'PeriodeSelesai' => CarbonImmutable::now()->addMonth()->toDateString(),
            'JatuhTempo' => CarbonImmutable::now()->addDays(14)->toDateString(),
            'Subtotal' => 250_000,
            'Pajak' => 0,
            'Total' => 250_000,
            'MataUang' => 'IDR',
            'Status' => StatusTagihanLangganan::BelumDibayar->value,
        ]);
    }
}
