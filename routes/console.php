<?php

use App\Domain\Pemasaran\Jobs\SinkronkanStatusProvider;
use Illuminate\Support\Facades\Schedule;

// | Shared hosting tidak menjalankan daemon queue secara permanen.
Schedule::command('queue:work database --queue=high,default,low --stop-when-empty --sleep=1 --tries=3 --timeout=45 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(1);

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('02:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('auth:clear-resets')
    ->dailyAt('02:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));

Schedule::command('catatan-akses:bersihkan')
    ->dailyAt('03:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));

Schedule::command('reservasi-suku-cadang:kedaluwarsakan')
    ->hourly()
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));

Schedule::command('suku-cadang:peringatan-stok-minimum')
    ->dailyAt('07:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));

Schedule::command('keluhan:proses-eskalasi-sla')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

Schedule::command('pemeliharaan:jadwalkan-preventif')
    ->dailyAt('01:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('kalibrasi:kirim-peringatan-jatuh-tempo')
    ->dailyAt('06:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('kontrak:kirim-peringatan-berakhir')
    ->dailyAt('06:45')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('kepatuhan:kirim-peringatan-kedaluwarsa')
    ->dailyAt('07:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping();

Schedule::command('outbox:proses')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('panggilan-balik:kirim-ulang')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

Schedule::command('sinkronisasi:proses-antrian')
    ->everyMinute()
    ->withoutOverlapping(2);

Schedule::command('idempotensi:bersihkan')
    ->dailyAt('03:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'));

// Terbit dan tarik terjadwal halaman pemasaran.
Schedule::command('pemasaran:jalankan-jadwal-halaman')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Trial yang masa berlakunya lewat ditutup tiap jam (MARKETING.md 12).
Schedule::command('pemasaran:kedaluwarsakan-trial')
    ->hourly()
    ->withoutOverlapping();

// Email pemasaran yang jatuh tempo diantrekan tiap lima menit (MARKETING.md 15).
Schedule::command('pemasaran:kirim-antrian-email')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Status kiriman yang datang belakangan ditarik tiap jam (MARKETING.md 15).
Schedule::job(new SinkronkanStatusProvider)
    ->hourly()
    ->withoutOverlapping();
