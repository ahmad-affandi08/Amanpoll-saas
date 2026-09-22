<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogTahapPipeline;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FieldFormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TahapPipeline;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Landasan test lead magnet dan tools publik. */
abstract class KasusLeadMagnet extends KasusPemasaran
{
    protected PetaHost $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = app(PetaHost::class);
        $this->semaiTahap();
        Storage::fake(BerkasLeadMagnet::disk());
    }

    protected function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) $this->host->publik().'/'.ltrim($path, '/');
    }

    protected function buatFormulir(bool $denganBerkas = true): FormulirPemasaran
    {
        $formulir = FormulirPemasaran::create([
            'Kode' => 'unduh-template',
            'Nama' => 'Unduh Template Preventive',
            'PesanSukses' => 'Templatnya siap diunduh.',
            'Sumber' => SumberProspek::LeadMagnet->value,
            'Tag' => ['lead-magnet'],
            'WajibPersetujuan' => true,
            'Aktif' => true,
        ]);

        $field = [
            ['Kode' => 'Nama', 'Label' => 'Nama', 'Jenis' => JenisFieldFormulir::Teks, 'Wajib' => true],
            ['Kode' => 'Email', 'Label' => 'Email', 'Jenis' => JenisFieldFormulir::Email, 'Wajib' => true],
            [
                'Kode' => 'Setuju',
                'Label' => 'Saya setuju dihubungi',
                'Jenis' => JenisFieldFormulir::Persetujuan,
                'Wajib' => true,
            ],
        ];

        foreach ($field as $urutan => $satu) {
            FieldFormulirPemasaran::create([
                ...$satu,
                'FormulirPemasaranId' => $formulir->Id,
                'Urutan' => $urutan,
            ]);
        }

        if ($denganBerkas) {
            app(BerkasLeadMagnet::class)->simpan($formulir, $this->berkasContoh());
        }

        return $formulir->fresh() ?? $formulir;
    }

    protected function berkasContoh(string $nama = 'template-preventive.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($nama, 12, 'application/pdf');
    }

    /** @param array<string, mixed> $tambahan */
    protected function kirimFormulir(array $tambahan = []): string
    {
        $respons = $this->post($this->urlPublik('/formulir/unduh-template'), [
            'Nama' => 'Budi',
            'Email' => 'budi@pabrik.test',
            'Setuju' => true,
            ...$tambahan,
        ]);

        $respons->assertRedirect();

        return (string) ($respons->getSession()->get('unduhan') ?? '');
    }

    private function semaiTahap(): void
    {
        foreach (KatalogTahapPipeline::bawaan() as $tahap) {
            TahapPipeline::query()->firstOrCreate(['Kode' => $tahap['Kode']], [
                'Nama' => $tahap['Nama'],
                'Urutan' => $tahap['Urutan'],
                'TahapAkhir' => $tahap['TahapAkhir'],
                'DianggapMenang' => $tahap['DianggapMenang'],
            ]);
        }
    }
}
