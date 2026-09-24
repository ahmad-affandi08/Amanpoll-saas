<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Dukungan\GambarUji;

/**
 * Lead magnet melewati mesin kompresi (PRD 11.1), dan pengunjung tetap
 * mengunduh berkas yang utuh.
 */
final class KompresiLeadMagnetTest extends KasusLeadMagnet
{
    public function test_pdf_yang_hemat_tersimpan_gzip_dan_terunduh_identik(): void
    {
        $isi = "%PDF-1.4\n".str_repeat("BT /F1 12 Tf 72 712 Td (Checklist preventive pompa infus) Tj ET\n", 400)."%%EOF\n";
        $formulir = $this->buatFormulir(denganBerkas: false);

        app(BerkasLeadMagnet::class)->simpan($formulir, UploadedFile::fake()->createWithContent('checklist.pdf', $isi));
        $formulir->refresh();

        $lokasi = (string) $formulir->BerkasLokasi;
        $mentah = (string) Storage::disk(BerkasLeadMagnet::disk())->get($lokasi);
        $this->assertStringEndsWith('.pdf.gz', $lokasi);
        $this->assertStringStartsWith("\x1f\x8b", $mentah);
        $this->assertLessThan(strlen($isi), strlen($mentah));
        $this->assertSame(strlen($isi), $formulir->BerkasUkuranByte);
        $this->assertSame('application/pdf', $formulir->BerkasMime);

        $unduhan = $this->get($this->kirimFormulir());

        $unduhan->assertOk()->assertDownload('checklist.pdf');
        $unduhan->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($isi, $unduhan->streamedContent());
    }

    public function test_gambar_disimpan_sebagai_webp_dan_diunduh_sebagai_webp(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        app(BerkasLeadMagnet::class)->simpan($formulir, UploadedFile::fake()->createWithContent('poster.png', GambarUji::pngTransparan()));
        $formulir->refresh();

        $this->assertStringEndsWith('.webp', (string) $formulir->BerkasLokasi);
        $this->assertSame('image/webp', $formulir->BerkasMime);
        $this->assertSame('poster.webp', $formulir->BerkasNamaAsli);

        $unduhan = $this->get($this->kirimFormulir());

        $unduhan->assertOk()->assertDownload('poster.webp');
        $unduhan->assertHeader('Content-Type', 'image/webp');
        $this->assertSame(
            Storage::disk(BerkasLeadMagnet::disk())->get((string) $formulir->BerkasLokasi),
            $unduhan->streamedContent(),
        );
    }

    /** Kegagalan kompresi tidak menggagalkan unggahan: berkasnya tersimpan apa adanya dan tercatat di log. */
    public function test_gambar_rusak_tetap_tersimpan_apa_adanya(): void
    {
        Log::spy();
        $utuh = GambarUji::jpeg(600, 400);
        $rusak = substr($utuh, 0, (int) (strlen($utuh) * 0.6));
        $formulir = $this->buatFormulir(denganBerkas: false);

        app(BerkasLeadMagnet::class)->simpan($formulir, UploadedFile::fake()->createWithContent('rusak.jpg', $rusak));
        $formulir->refresh();

        $this->assertSame('rusak.jpg', $formulir->BerkasNamaAsli);
        $this->assertSame($rusak, Storage::disk(BerkasLeadMagnet::disk())->get((string) $formulir->BerkasLokasi));
        $this->assertSame($rusak, $this->get($this->kirimFormulir())->streamedContent());
        Log::shouldHaveReceived('warning')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'Kompresi berkas gagal'))->once();
    }
}
