<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Cadangan\LayananCadangan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Arsip berkas cadangan (FASE 25.04, PRD 11.1, FASE 45): tar polos tanpa gzip
 * (unggahan tenant sudah terkompresi), dan tidak memuat cadangan sebelumnya
 * yang tinggal di dalam folder yang dicadangkan.
 */
final class CadanganBerkasTest extends TestCase
{
    private string $akarUji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->akarUji = storage_path('app/uji-cadangan-'.Str::lower(Str::random(6)));
        File::ensureDirectoryExists($this->akarUji.'/berkas');

        // Sama dengan susunan produksi: folder cadangan berada di dalam folder yang dicadangkan.
        config([
            'filesystems.disks.local.root' => $this->akarUji,
            'amanpoll.cadangan.disk' => 'local',
            'amanpoll.cadangan.folder' => 'cadangan',
            'amanpoll.cadangan.folder_berkas' => [basename($this->akarUji)],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->akarUji);

        parent::tearDown();
    }

    public function test_arsip_berkas_tidak_memuat_cadangan_sebelumnya(): void
    {
        File::put($this->akarUji.'/berkas/lampiran.txt', 'isi lampiran');
        File::ensureDirectoryExists($this->akarUji.'/cadangan');
        File::put($this->akarUji.'/cadangan/basisdata-20260101-000000.sql.gz', str_repeat('cadangan lama ', 1000));

        $arsip = app(LayananCadangan::class)->cadangkanBerkas();

        $this->assertNotNull($arsip);
        $this->assertSame('ustar', (string) file_get_contents($arsip, offset: 257, length: 5), 'Arsip berkas tar polos.');

        $daftar = new Process(['tar', '-tf', $arsip]);
        $daftar->mustRun();
        $isi = $daftar->getOutput();

        $this->assertStringContainsString(basename($this->akarUji).'/berkas/lampiran.txt', $isi);
        $this->assertStringNotContainsString('/cadangan', $isi);
    }

    /**
     * Di antara arsip penuh mingguan hanya berkas yang berubah sejak arsip penuh
     * terakhir yang diarsipkan; pemulihan cukup arsip penuh lalu selisih terbaru.
     */
    public function test_arsip_selisih_hanya_memuat_berkas_yang_berubah_sejak_arsip_penuh(): void
    {
        config(['amanpoll.cadangan.berkas_penuh_tiap_hari' => 7]);
        $lama = $this->akarUji.'/berkas/lama.jpg';
        $baru = $this->akarUji.'/berkas/baru.jpg';
        File::put($lama, 'foto lama');
        touch($lama, CarbonImmutable::now()->subDays(3)->getTimestamp());

        $penuh = app(LayananCadangan::class)->cadangkanBerkas(CarbonImmutable::now()->subDays(2));
        File::put($baru, 'foto baru');
        $selisih = app(LayananCadangan::class)->cadangkanBerkas(CarbonImmutable::now());

        $this->assertNotNull($penuh);
        $this->assertNotNull($selisih);
        $this->assertStringStartsWith('berkas-2', basename($penuh));
        $this->assertStringStartsWith('berkas-selisih-', basename($selisih));

        $isiPenuh = $this->isiArsip($penuh);
        $isiSelisih = $this->isiArsip($selisih);
        $this->assertStringContainsString('berkas/lama.jpg', $isiPenuh);
        $this->assertStringContainsString('berkas/baru.jpg', $isiSelisih);
        $this->assertStringNotContainsString('berkas/lama.jpg', $isiSelisih);
    }

    public function test_arsip_penuh_dibuat_lagi_setelah_arsip_penuh_terakhir_berumur_seminggu(): void
    {
        config(['amanpoll.cadangan.berkas_penuh_tiap_hari' => 7]);
        File::put($this->akarUji.'/berkas/lampiran.txt', 'isi lampiran');

        app(LayananCadangan::class)->cadangkanBerkas(CarbonImmutable::now()->subDays(7));
        $berikutnya = app(LayananCadangan::class)->cadangkanBerkas(CarbonImmutable::now());

        $this->assertNotNull($berikutnya);
        $this->assertStringStartsWith('berkas-2', basename($berikutnya));
        $this->assertStringContainsString('berkas/lampiran.txt', $this->isiArsip($berikutnya));
    }

    private function isiArsip(string $arsip): string
    {
        $daftar = new Process(['tar', '-tf', $arsip]);
        $daftar->mustRun();

        return $daftar->getOutput();
    }
}
