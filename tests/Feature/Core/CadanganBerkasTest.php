<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Cadangan\LayananCadangan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Arsip berkas cadangan (FASE 25.04, PRD 11.1): tergzip, dan tidak memuat
 * cadangan sebelumnya yang tinggal di dalam folder yang dicadangkan.
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
        $this->assertSame("\x1f\x8b", (string) file_get_contents($arsip, length: 2), 'Arsip berkas tergzip.');

        $daftar = new Process(['tar', '-tzf', $arsip]);
        $daftar->mustRun();
        $isi = $daftar->getOutput();

        $this->assertStringContainsString(basename($this->akarUji).'/berkas/lampiran.txt', $isi);
        $this->assertStringNotContainsString('/cadangan', $isi);
    }
}
