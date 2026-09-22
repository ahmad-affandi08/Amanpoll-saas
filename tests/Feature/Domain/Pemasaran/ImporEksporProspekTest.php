<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemasaran\Application\Actions\CatatProspek;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

/** Impor CSV dan ekspor prospek (MARKETING.md 5.1, 26, 27). */
final class ImporEksporProspekTest extends KasusProspek
{
    public function test_impor_csv_membuat_prospek(): void
    {
        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.prospek.impor'), ['Berkas' => $this->csv()])
            ->assertRedirect();

        $this->assertSame(2, Prospek::query()->count());
        $this->assertSame(
            SumberProspek::ImporCsv->value,
            Prospek::query()->where('Email', 'budi@contoh.test')->value('Sumber'),
        );
    }

    public function test_impor_melewati_baris_tanpa_nama(): void
    {
        $isi = "Nama,Email\n,kosong@contoh.test\nBudi,budi@contoh.test\n";

        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.prospek.impor'), [
                'Berkas' => UploadedFile::fake()->createWithContent('prospek.csv', $isi),
            ])
            ->assertRedirect();

        $this->assertSame(1, Prospek::query()->count());
    }

    public function test_impor_tercatat_di_audit(): void
    {
        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->post(route('pemasaran.prospek.impor'), ['Berkas' => $this->csv()]);

        $this->assertTrue(
            CatatanAudit::query()->withoutGlobalScopes()->where('Aksi', 'Prospek.Diimpor')->exists(),
        );
    }

    public function test_ekspor_menuntut_izin_tersendiri(): void
    {
        $this->actingAs($this->buatAdmin([
            KatalogIzinPemasaran::PROSPEK_LIHAT,
            KatalogIzinPemasaran::PROSPEK_KELOLA,
        ]), 'platform')
            ->get(route('pemasaran.prospek.ekspor'))
            ->assertForbidden();
    }

    public function test_pemegang_izin_ekspor_menerima_berkasnya(): void
    {
        $this->buatProspek('Budi');

        $respons = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_EKSPOR]), 'platform')
            ->get(route('pemasaran.prospek.ekspor'));

        $respons->assertOk();
        $this->assertStringContainsString('Budi', $this->isi($respons));
    }

    public function test_ekspor_tercatat_di_audit(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_EKSPOR]), 'platform')
            ->get(route('pemasaran.prospek.ekspor'))
            ->assertOk();

        $this->assertTrue(
            CatatanAudit::query()->withoutGlobalScopes()->where('Aksi', 'Prospek.Diekspor')->exists(),
        );
    }

    public function test_nama_prospek_berawalan_rumus_dinetralkan_di_ekspor(): void
    {
        // Nama diketik orang luar lewat formulir publik.
        $this->buatProspek('=cmd|\' /c calc\'!A0');

        $respons = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::PROSPEK_EKSPOR]), 'platform')
            ->get(route('pemasaran.prospek.ekspor'));

        $this->assertStringContainsString("'=cmd", $this->isi($respons));
    }

    private function buatProspek(string $nama): void
    {
        app(CatatProspek::class)->jalankan(
            ['Nama' => $nama, 'Email' => 'uji+'.uniqid().'@contoh.test'],
            SumberProspek::Manual,
        );
    }

    private function csv(): UploadedFile
    {
        $isi = "Nama,Email,Perusahaan\nBudi,budi@contoh.test,PT Pabrik\nSiti,siti@contoh.test,PT Hotel\n";

        return UploadedFile::fake()->createWithContent('prospek.csv', $isi);
    }

    private function isi(TestResponse $respons): string
    {
        ob_start();
        $respons->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
