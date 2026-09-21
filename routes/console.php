<?php

use Illuminate\Support\Facades\Schedule;

/*
| Shared hosting tidak menjalankan daemon queue secara permanen.
| hPanel hanya perlu memanggil "php artisan schedule:run" setiap menit.
| Scheduler di bawah menjalankan worker pendek lalu keluar dengan aman.
*/
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
