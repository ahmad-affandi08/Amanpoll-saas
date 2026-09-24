<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Pekerja antrean cron dan job yang dikerjakannya (FASE 45).
 *
 * Tiga angka yang tinggal di tiga tempat harus saling cocok: `$timeout` job,
 * `--timeout` pekerja di `routes/console.php`, dan `retry_after` koneksi di
 * `config/queue.php`. Bila `retry_after` tidak melampaui lama sebuah job
 * berjalan, pekerja lain mengambilnya ulang selagi ia masih dikerjakan; bila
 * kunci jadwal lebih pendek dari umur pekerjanya, dua proses PHP berat hidup
 * bersamaan di shared hosting. Keduanya tidak menimbulkan galat, hanya ekspor
 * dobel dan server yang tersendat, jadi dijaga di sini.
 */
final class PekerjaAntreanCronTest extends TestCase
{
    /** Jeda minimum antara kedaluwarsa --timeout pekerja dan lepasnya kunci atau `retry_after`. */
    private const JEDA_DETIK = 30;

    public function test_antrean_dikerjakan_dua_pekerja_dengan_queue_yang_tidak_bertumpuk(): void
    {
        $pekerja = $this->pekerja();

        $this->assertCount(2, $pekerja, 'Harus ada tepat dua pekerja antrean: cepat dan panjang.');
        $this->assertSame(
            [],
            array_values(array_intersect($pekerja[0]['antrean'], $pekerja[1]['antrean'])),
            'Queue yang sama tidak boleh diambil dua pekerja dengan timeout berbeda.',
        );
        $this->assertSame(['high', 'default'], $this->pekerjaUntuk('default')['antrean']);
        $this->assertSame(['low'], $this->pekerjaUntuk('low')['antrean']);
    }

    public function test_pekerja_cepat_berumur_pendek_dan_kuncinya_menutup_umur_terpanjangnya(): void
    {
        $cepat = $this->pekerjaUntuk('default');
        $opsi = $cepat['opsi'];

        $this->assertSame('database', $cepat['koneksi']);
        $this->assertSame('* * * * *', $cepat['event']->expression);
        $this->assertArrayHasKey('stop-when-empty', $opsi);
        $this->assertLessThan(60, (int) $opsi['max-time'], 'Pekerja per menit harus berhenti sebelum menit berikutnya.');

        // --max-time hanya diperiksa di antara job, jadi job terakhir boleh berjalan satu --timeout lagi.
        $umurTerpanjang = (int) $opsi['max-time'] + (int) $opsi['timeout'];

        $this->assertTrue($cepat['event']->withoutOverlapping);
        $this->assertGreaterThanOrEqual($umurTerpanjang, $cepat['event']->expiresAt * 60);
        $this->assertTrue($cepat['event']->runInBackground, 'Pekerja di latar supaya jadwal lain tidak menunggu.');
    }

    public function test_pekerja_panjang_satu_job_per_proses_dengan_kunci_melampaui_timeoutnya(): void
    {
        $panjang = $this->pekerjaUntuk('low');
        $opsi = $panjang['opsi'];

        $this->assertSame('database-panjang', $panjang['koneksi']);
        $this->assertSame('1', $opsi['max-jobs'] ?? null, 'Pekerja low hanya mengerjakan satu job berat per proses.');
        $this->assertArrayHasKey('stop-when-empty', $opsi);
        $this->assertTrue($panjang['event']->withoutOverlapping);
        $this->assertGreaterThanOrEqual(
            (int) $opsi['timeout'] + self::JEDA_DETIK,
            $panjang['event']->expiresAt * 60,
            'Kunci yang lepas sebelum --timeout melahirkan pekerja berat kedua.',
        );
        $this->assertTrue($panjang['event']->runInBackground, 'Pekerja lima menit tidak boleh menahan jadwal lain.');
    }

    public function test_retry_after_setiap_koneksi_melampaui_timeout_pekerjanya(): void
    {
        foreach ($this->pekerja() as $satu) {
            $retryAfter = (int) config("queue.connections.{$satu['koneksi']}.retry_after");

            $this->assertGreaterThanOrEqual(
                (int) $satu['opsi']['timeout'] + self::JEDA_DETIK,
                $retryAfter,
                "retry_after koneksi {$satu['koneksi']} tidak melampaui --timeout pekerjanya.",
            );
        }
    }

    /**
     * Setiap job yang ada harus mendarat di queue yang sungguh diambil pekerja,
     * dan lama maksimalnya harus muat di --timeout pekerja itu serta di bawah
     * `retry_after` koneksinya.
     */
    public function test_setiap_job_dikerjakan_pekerja_yang_sanggup_menyelesaikannya(): void
    {
        $this->pakaiKonfigurasiAntreanProduksi();

        $pelanggaran = [];
        $dilayaniPanjang = [];

        foreach ($this->kelasJob() as $kelas) {
            $job = $this->buatJob($kelas);
            $koneksi = $job->connection ?? config('queue.default');
            $antrean = $job->queue ?? config("queue.connections.{$koneksi}.queue");
            $nama = (new ReflectionClass($kelas))->getShortName();

            $pekerja = $this->pekerjaUntuk((string) $antrean);

            if ($pekerja['koneksi'] !== $koneksi) {
                $pelanggaran[] = "{$nama}: koneksi {$koneksi}, pekerja queue {$antrean} memakai {$pekerja['koneksi']}";

                continue;
            }

            $timeoutPekerja = (int) $pekerja['opsi']['timeout'];
            $timeoutJob = isset($job->timeout) ? (int) $job->timeout : $timeoutPekerja;
            $retryAfter = (int) config("queue.connections.{$koneksi}.retry_after");

            if ($timeoutJob > $timeoutPekerja) {
                $pelanggaran[] = "{$nama}: timeout {$timeoutJob} detik melampaui --timeout pekerja {$timeoutPekerja}";
            }

            if ($retryAfter <= $timeoutJob) {
                $pelanggaran[] = "{$nama}: retry_after {$koneksi} {$retryAfter} tidak melampaui timeout {$timeoutJob}";
            }

            if ($pekerja['antrean'] === ['low']) {
                $dilayaniPanjang[] = $kelas;
            }
        }

        $this->assertSame([], $pelanggaran, 'Job berikut dapat dikerjakan dobel atau dibunuh sebelum selesai.');
        $this->assertNotSame([], $dilayaniPanjang, 'Pekerja low tidak melayani satu job pun; iterasinya bolong.');
    }

    /** Job berat yang habis waktu tidak boleh diulang berkali-kali oleh antrean. */
    public function test_job_di_pekerja_panjang_gagal_saat_habis_waktu_dan_percobaannya_terbatas(): void
    {
        $this->pakaiKonfigurasiAntreanProduksi();

        $diperiksa = 0;

        foreach ($this->kelasJob() as $kelas) {
            $job = $this->buatJob($kelas);

            if ($job->queue !== 'low') {
                continue;
            }

            $diperiksa++;
            $nama = (new ReflectionClass($kelas))->getShortName();

            $this->assertTrue($job->failOnTimeout ?? false, "{$nama} harus failOnTimeout.");
            $this->assertLessThanOrEqual(2, $job->tries ?? PHP_INT_MAX, "{$nama} mengulang job berat terlalu sering.");
        }

        $this->assertGreaterThan(0, $diperiksa);
    }

    /**
     * Konfigurasi antrean seperti di produksi (QUEUE_CONNECTION=database),
     * dibaca dari `config/queue.php` sungguhan, bukan disalin ke sini.
     */
    private function pakaiKonfigurasiAntreanProduksi(): void
    {
        $sebelumnya = $_SERVER['QUEUE_CONNECTION'] ?? null;
        $_SERVER['QUEUE_CONNECTION'] = 'database';

        try {
            $konfigurasi = require config_path('queue.php');
        } finally {
            if ($sebelumnya === null) {
                unset($_SERVER['QUEUE_CONNECTION']);
            } else {
                $_SERVER['QUEUE_CONNECTION'] = $sebelumnya;
            }
        }

        $this->assertSame('database-panjang', $konfigurasi['koneksi_panjang']);
        config(['queue' => $konfigurasi]);
    }

    /**
     * @return list<array{koneksi: string, antrean: list<string>, opsi: array<string, string>, event: Event}>
     */
    private function pekerja(): array
    {
        $hasil = [];

        foreach (app(Schedule::class)->events() as $event) {
            if (preg_match('/queue:work\s+(\S+)(.*)$/', (string) $event->command, $cocok) !== 1) {
                continue;
            }

            preg_match_all('/--([a-z-]+)(?:=(\S+))?/', $cocok[2], $opsiCocok, PREG_SET_ORDER);

            $opsi = [];
            foreach ($opsiCocok as $satu) {
                $opsi[$satu[1]] = $satu[2] ?? '';
            }

            $hasil[] = [
                'koneksi' => $cocok[1],
                'antrean' => explode(',', $opsi['queue'] ?? 'default'),
                'opsi' => $opsi,
                'event' => $event,
            ];
        }

        return $hasil;
    }

    /** @return array{koneksi: string, antrean: list<string>, opsi: array<string, string>, event: Event} */
    private function pekerjaUntuk(string $antrean): array
    {
        foreach ($this->pekerja() as $satu) {
            if (in_array($antrean, $satu['antrean'], true)) {
                return $satu;
            }
        }

        $this->fail("Tidak ada pekerja cron yang mengambil queue {$antrean}; job di sana tidak pernah jalan.");
    }

    /**
     * Membuat job lewat konstruktornya — di sanalah queue dan koneksi ditetapkan —
     * dengan argumen tiruan menurut tipe parameternya.
     */
    private function buatJob(string $kelas): object
    {
        $cerminan = new ReflectionClass($kelas);
        $argumen = [];

        foreach ($cerminan->getConstructor()?->getParameters() ?? [] as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $argumen[] = $parameter->getDefaultValue();

                continue;
            }

            $tipe = $parameter->getType();
            $namaTipe = $tipe instanceof ReflectionNamedType ? $tipe->getName() : null;

            $argumen[] = match (true) {
                $tipe?->allowsNull() === true => null,
                $namaTipe === 'string' => 'tiruan',
                $namaTipe === 'array' => [],
                $namaTipe === 'int' => 0,
                $namaTipe === 'bool' => false,
                default => $this->fail("Parameter \${$parameter->getName()} {$kelas} belum dapat ditiru test ini."),
            };
        }

        return $cerminan->newInstanceArgs($argumen);
    }

    /** @return list<class-string> */
    private function kelasJob(): array
    {
        $kelas = [];

        foreach (Finder::create()->files()->in(app_path())->name('*.php') as $berkas) {
            $nama = $this->kelasDari($berkas);

            if ($nama === null || ! is_subclass_of($nama, ShouldQueue::class)) {
                continue;
            }

            if ((new ReflectionClass($nama))->isAbstract()) {
                continue;
            }

            $kelas[] = $nama;
        }

        sort($kelas);

        return $kelas;
    }

    /** @return class-string|null */
    private function kelasDari(SplFileInfo $berkas): ?string
    {
        $relatif = str_replace([app_path().DIRECTORY_SEPARATOR, '.php'], '', $berkas->getRealPath());
        $nama = 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relatif);

        return class_exists($nama) ? $nama : null;
    }
}
