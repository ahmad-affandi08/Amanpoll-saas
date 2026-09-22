<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use App\Domain\Pemasaran\Application\Services\PerangkapSpam;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/** Berkas lead magnet tidak dapat diunduh tanpa mengisi formulirnya (Gate 38.05). */
final class UnduhanLeadMagnetTest extends KasusLeadMagnet
{
    /** Inti Gate 38.05. */
    public function test_tautan_unduhan_lahir_dari_pengiriman_formulir(): void
    {
        $this->buatFormulir();

        $unduhan = $this->kirimFormulir();

        $this->assertNotSame('', $unduhan);
        $this->get($unduhan)->assertOk()->assertDownload('template-preventive.pdf');
    }

    /** Sisi lain Gate 38.05: tanpa tanda tangan tidak ada yang keluar. */
    public function test_tautan_tanpa_tanda_tangan_ditolak(): void
    {
        $this->buatFormulir();
        $this->kirimFormulir();

        $pengiriman = PengirimanFormulir::query()->firstOrFail();

        $this->get($this->urlPublik('/unduh/'.$pengiriman->Id))->assertForbidden();
    }

    public function test_tanda_tangan_yang_dirusak_ditolak(): void
    {
        $this->buatFormulir();
        $unduhan = $this->kirimFormulir();

        $this->get($unduhan.'x')->assertForbidden();
    }

    /** Menebak id pengiriman orang lain tetap tidak menghasilkan tanda tangan yang sah. */
    public function test_pengiriman_yang_tidak_ada_tidak_dapat_ditandatangani_sendiri(): void
    {
        $this->buatFormulir();

        $palsu = URL::temporarySignedRoute(
            'publik.unduhan',
            now()->addHour(),
            ['pengiriman' => '01JQXXXXXXXXXXXXXXXXXXXXXX'],
        );

        $this->get($palsu)->assertNotFound();
    }

    public function test_tautan_kedaluwarsa_ditolak(): void
    {
        $this->buatFormulir();
        $unduhan = $this->kirimFormulir();

        $this->travel(61)->minutes();

        $this->get($unduhan)->assertForbidden();
    }

    public function test_formulir_tanpa_berkas_tidak_menerbitkan_tautan(): void
    {
        $this->buatFormulir(denganBerkas: false);

        $this->assertSame('', $this->kirimFormulir());
    }

    /** Baris formulir boleh saja masih menunjuk berkas yang sudah lenyap dari disk. */
    public function test_berkas_yang_hilang_dari_disk_menjawab_404(): void
    {
        $formulir = $this->buatFormulir();
        $unduhan = $this->kirimFormulir();

        Storage::disk(BerkasLeadMagnet::disk())->delete((string) $formulir->BerkasLokasi);

        $this->get($unduhan)->assertNotFound();
    }

    /** Berkas yang dicabut setelah tautan terbit tidak boleh tetap dapat diunduh. */
    public function test_berkas_yang_dicabut_menutup_tautan_yang_sudah_terbit(): void
    {
        $formulir = $this->buatFormulir();
        $unduhan = $this->kirimFormulir();

        app(BerkasLeadMagnet::class)->hapus($formulir);

        $this->get($unduhan)->assertNotFound();
    }

    public function test_kiriman_yang_terperangkap_honeypot_tidak_mendapat_tautan(): void
    {
        $this->buatFormulir();

        $unduhan = $this->kirimFormulir([PerangkapSpam::FIELD => 'http://spam.test']);

        $this->assertSame('', $unduhan);
        $this->assertSame(0, PengirimanFormulir::query()->count());
    }

    public function test_template_diunduh_tercatat_saat_berkas_diambil(): void
    {
        $this->buatFormulir();
        $this->get($this->kirimFormulir())->assertOk();

        $event = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TEMPLATE_DIUNDUH)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('unduh-template', $event->DataTambahan['FormulirKode'] ?? null);
        $this->assertSame('template-preventive.pdf', $event->DataTambahan['NamaBerkas'] ?? null);
    }

    public function test_template_diunduh_tidak_tercatat_saat_pengiriman_saja(): void
    {
        $this->buatFormulir();
        $this->kirimFormulir();

        $this->assertSame(0, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TEMPLATE_DIUNDUH)->count());
    }

    /** Peristiwanya menempel pada pengunjung yang mengisi formulir, bukan pada siapa pun. */
    public function test_peristiwa_unduhan_membawa_pengenal_pengunjung_pengirimnya(): void
    {
        $this->buatFormulir();
        $this->get($this->kirimFormulir())->assertOk();

        $pengiriman = PengirimanFormulir::query()->firstOrFail();
        $event = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::TEMPLATE_DIUNDUH)
            ->firstOrFail();

        $this->assertNotNull($pengiriman->PengenalPengunjung);
        $this->assertSame($pengiriman->PengenalPengunjung, $event->PengenalPengunjung);
    }

    /** Berkas lead magnet hidup di disk privat; tidak pernah ada URL yang dapat ditebak. */
    public function test_berkas_disimpan_dengan_nama_acak_bukan_nama_asli(): void
    {
        $formulir = $this->buatFormulir();

        $lokasi = (string) $formulir->BerkasLokasi;

        $this->assertStringStartsWith('lead-magnet/', $lokasi);
        $this->assertStringNotContainsString('template-preventive', $lokasi);
        $this->assertSame('template-preventive.pdf', $formulir->BerkasNamaAsli);
        Storage::disk(BerkasLeadMagnet::disk())->assertExists($lokasi);
    }

    public function test_mengganti_berkas_menghapus_yang_lama_dari_disk(): void
    {
        $formulir = $this->buatFormulir();
        $lama = (string) $formulir->BerkasLokasi;

        app(BerkasLeadMagnet::class)->simpan($formulir, $this->berkasContoh('checklist-inspeksi.pdf'));

        $disk = Storage::disk(BerkasLeadMagnet::disk());
        $disk->assertMissing($lama);
        $disk->assertExists((string) $formulir->fresh()?->BerkasLokasi);
        $this->assertSame('checklist-inspeksi.pdf', $formulir->fresh()?->BerkasNamaAsli);
    }

    public function test_menghapus_berkas_mengosongkan_seluruh_kolomnya(): void
    {
        $formulir = $this->buatFormulir();
        $lokasi = (string) $formulir->BerkasLokasi;

        app(BerkasLeadMagnet::class)->hapus($formulir);

        $segar = FormulirPemasaran::query()->whereKey($formulir->Id)->firstOrFail();

        $this->assertFalse($segar->punyaBerkas());
        $this->assertNull($segar->BerkasNamaAsli);
        $this->assertNull($segar->BerkasMime);
        $this->assertNull($segar->BerkasUkuranByte);
        Storage::disk(BerkasLeadMagnet::disk())->assertMissing($lokasi);
    }
}
