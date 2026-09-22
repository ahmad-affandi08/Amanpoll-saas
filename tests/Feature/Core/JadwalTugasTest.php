<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * Penjaga konfigurasi scheduler (FASE 25.02, 25.03).
 *
 * Jadwal yang lupa dijaga baru ketahuan saat produksi menumpuk dua proses di
 * shared hosting, dan kunci yang terlalu panjang baru ketahuan saat satu jadwal
 * diam berhari-hari. Keduanya diperiksa di sini, bukan diserahkan pada ingatan
 * orang yang menambah jadwal berikutnya.
 */
final class JadwalTugasTest extends TestCase
{
    /**
     * Batas atas kunci terhadap jarak antar jalan.
     *
     * Kunci tidak boleh lebih pendek dari durasi wajar jadwalnya: ia lapse di
     * tengah jalan dan justru melahirkan proses kedua, persis yang hendak
     * dicegah. Yang pantas dibatasi adalah sisi atasnya — satu proses mati
     * boleh menelan paling banyak sekitar satu kali jalan, bukan sehari penuh.
     */
    private const BATAS_TERHADAP_JEDA = 2.0;

    public function test_setiap_jadwal_dijaga_dari_tumpang_tindih(): void
    {
        $tanpaPenjaga = [];

        foreach ($this->jadwal() as $event) {
            if (! $event->withoutOverlapping) {
                $tanpaPenjaga[] = $this->nama($event);
            }
        }

        $this->assertSame([], $tanpaPenjaga, 'Jadwal berikut belum memakai withoutOverlapping().');
    }

    /** Bawaan `withoutOverlapping()` adalah 1440 menit; itu terlalu panjang untuk jadwal mana pun di sini. */
    public function test_setiap_kunci_menyebut_masa_berlakunya_sendiri(): void
    {
        $memakaiBawaan = [];

        foreach ($this->jadwal() as $event) {
            if ($event->withoutOverlapping && $event->expiresAt === 1440) {
                $memakaiBawaan[] = $this->nama($event);
            }
        }

        $this->assertSame([], $memakaiBawaan, 'Jadwal berikut masih memakai masa berlaku kunci bawaan.');
    }

    /** Kunci yang hidup jauh lebih lama dari jarak antar jalan menelan jalan-jalan berikutnya. */
    public function test_masa_berlaku_kunci_tidak_jauh_melampaui_jarak_antar_jalan(): void
    {
        $terlaluPanjang = [];

        foreach ($this->jadwal() as $event) {
            if (! $event->withoutOverlapping) {
                continue;
            }

            $jeda = $this->jedaMenit($event);

            if ((float) $event->expiresAt > $jeda * self::BATAS_TERHADAP_JEDA) {
                $terlaluPanjang[] = $this->nama($event)." (kunci {$event->expiresAt} menit, jeda {$jeda} menit)";
            }
        }

        $this->assertSame([], $terlaluPanjang, 'Kunci jadwal berikut lebih panjang dari jeda antar jalannya.');
    }

    /** Shared hosting hanya punya satu slot; dua jadwal harian pada menit yang sama saling menunggu. */
    public function test_tidak_ada_dua_jadwal_harian_pada_menit_yang_sama(): void
    {
        $perMenit = [];

        foreach ($this->jadwal() as $event) {
            if ($this->jedaMenit($event) < 1440) {
                continue;
            }

            $perMenit[$event->expression][] = $this->nama($event);
        }

        $bentrok = array_filter($perMenit, fn (array $nama): bool => count($nama) > 1);

        $this->assertSame([], $bentrok, 'Jadwal harian berikut berbagi menit yang sama.');
    }

    /** @return list<Event> */
    private function jadwal(): array
    {
        return array_values(app(Schedule::class)->events());
    }

    /** Jarak menit antara dua kali jalan berturut-turut menurut ekspresi cron-nya. */
    private function jedaMenit(Event $event): int
    {
        $cron = new CronExpression($event->expression);
        $pertama = $cron->getNextRunDate('now', 0, allowCurrentDate: false);
        $kedua = $cron->getNextRunDate($pertama, 0, allowCurrentDate: false);

        return (int) round(($kedua->getTimestamp() - $pertama->getTimestamp()) / 60);
    }

    private function nama(Event $event): string
    {
        $perintah = (string) $event->command;

        if ($perintah !== '') {
            // Baris perintah lengkap memuat jalur PHP dan artisan; yang menjelaskan hanyalah nama perintahnya.
            return preg_replace('/^.*artisan[\'"]?\s+/', '', $perintah) ?? $perintah;
        }

        return (string) ($event->description ?? 'jadwal tanpa nama');
    }
}
