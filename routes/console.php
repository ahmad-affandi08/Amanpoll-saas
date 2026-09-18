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
