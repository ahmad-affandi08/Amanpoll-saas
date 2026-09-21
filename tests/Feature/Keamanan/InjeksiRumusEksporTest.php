<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Domain\Pelaporan\Infrastructure\Services\PenulisEksporCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Isi ekspor berasal dari data tenant — nama aset, gudang, penyedia — dan
 * Excel memperlakukan sel yang diawali karakter tertentu sebagai rumus. Satu
 * pengguna yang menamai asetnya `=cmd|...` karena itu dapat mengeksekusi
 * perintah di komputer rekan kerjanya yang membuka hasil ekspor (24).
 */
final class InjeksiRumusEksporTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function nilaiBerbahaya(): array
    {
        return [
            'sama dengan' => ['=cmd|\' /c calc\'!A0'],
            'tambah' => ['+1+1'],
            'kurang' => ['-1+1'],
            'at' => ['@SUM(A1:A9)'],
            'tab' => ["\t=1+1"],
            'carriage return' => ["\r=1+1"],
        ];
    }

    #[DataProvider('nilaiBerbahaya')]
    public function test_nilai_berawalan_rumus_dinetralkan(string $berbahaya): void
    {
        $isi = $this->tulisCsv(['Label', 'Nilai'], [[$berbahaya, 1]]);

        $this->assertStringContainsString("'".$berbahaya, $isi);
    }

    public function test_nilai_biasa_tidak_diubah(): void
    {
        $isi = $this->tulisCsv(['Label', 'Nilai'], [['Pompa Air Utama', 12]]);

        $this->assertStringContainsString('Pompa Air Utama', $isi);
        $this->assertStringNotContainsString("'Pompa", $isi);
    }

    public function test_kepala_kolom_dan_meta_ikut_dinetralkan(): void
    {
        $isi = $this->tulisCsv(['=Label'], [['aman', 1]], ['=Judul' => '=Nilai']);

        $this->assertStringContainsString("'=Label", $isi);
        $this->assertStringContainsString("'=Judul", $isi);
        $this->assertStringContainsString("'=Nilai", $isi);
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<mixed>>  $baris
     * @param  array<string, mixed>  $meta
     */
    private function tulisCsv(array $kepala, array $baris, array $meta = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ekspor').'.csv';

        try {
            (new PenulisEksporCsv)->tulis($path, $kepala, $baris, $meta);

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }
}
