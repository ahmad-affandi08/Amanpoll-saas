<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Kegagalan permanen harus meninggalkan jejak yang dibaca orang (FASE 25.02).
 *
 * Tabel `PekerjaanGagal` dipangkas tiap minggu dan tidak dibaca halaman mana
 * pun, jadi tanpa jejak di log sebuah pekerjaan yang berhenti diam-diam
 * terlihat persis seperti pekerjaan yang memang tidak pernah diantrekan.
 */
final class PekerjaanGagalTercatatTest extends TestCase
{
    public function test_pekerjaan_gagal_dicatat_ke_log_beserta_konteksnya(): void
    {
        $konteks = $this->tangkapLog(new RuntimeException('Penyedia menolak kiriman.'));

        $this->assertSame('App\\Jobs\\ContohPekerjaan', $konteks['Pekerjaan']);
        $this->assertSame('database', $konteks['Koneksi']);
        $this->assertSame('default', $konteks['Antrian']);
        $this->assertSame(3, $konteks['Percobaan']);
        $this->assertSame(RuntimeException::class, $konteks['Pengecualian']);
        $this->assertSame('Penyedia menolak kiriman.', $konteks['Pesan']);
    }

    /** Pesan pengecualian dipotong supaya satu pesan panjang tidak membanjiri log. */
    public function test_pesan_pengecualian_dipotong(): void
    {
        $konteks = $this->tangkapLog(new RuntimeException(str_repeat('a', 900)));

        $this->assertSame(500, mb_strlen($konteks['Pesan']));
    }

    /** @return array<string, mixed> */
    private function tangkapLog(RuntimeException $galat): array
    {
        $tertangkap = [];
        $pesan = null;

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $judul, array $konteks) use (&$tertangkap, &$pesan): bool {
                $pesan = $judul;
                $tertangkap = $konteks;

                return true;
            });

        Event::dispatch(new JobFailed('database', $this->pekerjaanPalsu(), $galat));

        $this->assertSame('Pekerjaan antrian gagal permanen.', $pesan);

        return $tertangkap;
    }

    private function pekerjaanPalsu(): Job
    {
        $pekerjaan = Mockery::mock(Job::class);
        $pekerjaan->shouldReceive('resolveName')->andReturn('App\\Jobs\\ContohPekerjaan');
        $pekerjaan->shouldReceive('getQueue')->andReturn('default');
        $pekerjaan->shouldReceive('attempts')->andReturn(3);
        $pekerjaan->shouldIgnoreMissing();

        return $pekerjaan;
    }
}
