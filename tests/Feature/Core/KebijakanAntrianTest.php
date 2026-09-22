<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Penjaga kebijakan antrian (FASE 25.02).
 *
 * Tanpa `$tries` sendiri, sebuah job diam-diam memakai `--tries=3` dari baris
 * perintah `queue:work` di `routes/console.php`. Artinya kebijakan percobaan
 * ulang tersimpan di satu string cron, bukan di job yang mengetahui apakah
 * mengulang dirinya aman. Job yang mengirim pesan atau memegang tangga
 * percobaannya sendiri justru berbahaya bila diulang queue.
 */
final class KebijakanAntrianTest extends TestCase
{
    public function test_setiap_job_menyatakan_kebijakan_percobaan_ulangnya(): void
    {
        $tanpaKebijakan = [];

        foreach ($this->kelasJob() as $kelas) {
            $cerminan = new ReflectionClass($kelas);

            if (! $cerminan->hasProperty('tries')) {
                $tanpaKebijakan[] = $cerminan->getShortName();

                continue;
            }

            if ($cerminan->getProperty('tries')->getDeclaringClass()->getName() !== $kelas) {
                $tanpaKebijakan[] = $cerminan->getShortName();
            }
        }

        $this->assertSame([], $tanpaKebijakan, 'Job berikut belum menyatakan $tries-nya sendiri.');
    }

    /** Percobaan ulang tanpa jeda menghantam sumber yang sedang bermasalah tepat saat ia bermasalah. */
    public function test_job_yang_boleh_diulang_memberi_jeda_antar_percobaan(): void
    {
        $tanpaJeda = [];

        foreach ($this->kelasJob() as $kelas) {
            $cerminan = new ReflectionClass($kelas);
            $contoh = $cerminan->newInstanceWithoutConstructor();

            $tries = $cerminan->hasProperty('tries')
                ? $cerminan->getProperty('tries')->getValue($contoh)
                : 1;

            if ((int) $tries <= 1) {
                continue;
            }

            $punyaJeda = $cerminan->hasProperty('backoff') || $cerminan->hasMethod('backoff');

            if (! $punyaJeda) {
                $tanpaJeda[] = $cerminan->getShortName();
            }
        }

        $this->assertSame([], $tanpaJeda, 'Job berikut boleh diulang tetapi tidak memberi jeda.');
    }

    /**
     * Seluruh kelas job konkret di bawah `app/`.
     *
     * @return list<class-string>
     */
    private function kelasJob(): array
    {
        $kelas = [];

        foreach (Finder::create()->files()->in(app_path())->name('*.php') as $berkas) {
            $nama = $this->kelasDari($berkas);

            if ($nama === null || ! is_subclass_of($nama, ShouldQueue::class)) {
                continue;
            }

            $cerminan = new ReflectionClass($nama);

            if ($cerminan->isAbstract()) {
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
