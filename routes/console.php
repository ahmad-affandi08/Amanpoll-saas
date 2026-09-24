<?php

use App\Domain\Pemasaran\Jobs\SinkronkanStatusProvider;
use Illuminate\Support\Facades\Schedule;

/*
| Tiap jadwal menyebut sendiri masa berlaku kuncinya, tidak memakai bawaan
| `withoutOverlapping()` yang 1440 menit. Shared hosting rutin membunuh proses
| yang kelamaan, dan kunci yang ditinggalkan proses mati itu menahan jalannya
| jadwal berikutnya selama kunci belum kedaluwarsa — untuk jadwal per jam
| berarti dua puluh empat kali jalan yang hilang diam-diam. Angkanya dipilih
| longgar terhadap durasi wajar jadwalnya, tetapi selalu lebih pendek dari
| jarak ke jalan berikutnya (FASE 25.02, 25.03).
*/

/*
| Shared hosting tidak menjalankan daemon queue secara permanen; antrean
| dikerjakan dua pekerja cron yang berumur pendek (FASE 45).
|
| Pekerja cepat mengambil job pendek. Umur terpanjangnya --max-time ditambah
| satu --timeout (50 + 45 detik), jadi kuncinya dua menit: kunci satu menit
| kedaluwarsa selagi ia masih bekerja dan melahirkan pekerja kedua.
|
| Pekerja panjang khusus queue low (ekspor laporan): satu job per proses,
| --timeout sedikit di atas timeout job terpanjang di sana (300 detik), dan
| kunci enam menit yang melampaui --timeout itu. Ia memakai koneksi
| `database-panjang` yang `retry_after`-nya 420 detik, supaya ekspor yang
| masih berjalan tidak diambil ulang dan dikerjakan dobel.
|
| Keduanya berjalan di latar supaya jadwal lain pada menit yang sama tidak
| menunggu antrean selesai. PekerjaAntreanCronTest menjaga angka-angka ini
| terhadap job yang sungguh ada.
*/
Schedule::command('queue:work database --queue=high,default --stop-when-empty --sleep=1 --tries=3 --timeout=45 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground();

Schedule::command('queue:work database-panjang --queue=low --max-jobs=1 --stop-when-empty --sleep=1 --tries=1 --timeout=310')
    ->everyThreeMinutes()
    ->withoutOverlapping(6)
    ->runInBackground();

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('02:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Cadangan harian berjalan sebelum pembersihan apa pun, supaya yang tersimpan adalah keadaan utuh.
// Di latar: dump dan unggahan ke disk luar dapat memakan belasan menit.
Schedule::command('cadangan:jalankan')
    ->dailyAt('01:45')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120)
    ->runInBackground();

Schedule::command('auth:clear-resets')
    ->dailyAt('02:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Penghapusan menyapu tabel yang tumbuh tiap permintaan, jadi ia dapat berjalan lama.
Schedule::command('catatan-akses:bersihkan')
    ->dailyAt('03:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Dua jalan bersamaan dapat melepas hold yang sama dua kali.
Schedule::command('reservasi-suku-cadang:kedaluwarsakan')
    ->hourly()
    ->withoutOverlapping(55);

// Menyusuri seluruh organisasi dan mengirim notifikasi; tumpang tindih berarti pesan ganda.
Schedule::command('suku-cadang:peringatan-stok-minimum')
    ->dailyAt('07:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

Schedule::command('keluhan:proses-eskalasi-sla')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

Schedule::command('pemeliharaan:jadwalkan-preventif')
    ->dailyAt('01:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

Schedule::command('kalibrasi:kirim-peringatan-jatuh-tempo')
    ->dailyAt('06:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

Schedule::command('kontrak:kirim-peringatan-berakhir')
    ->dailyAt('06:45')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

Schedule::command('kepatuhan:kirim-peringatan-kedaluwarsa')
    ->dailyAt('07:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

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
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Terbit dan tarik terjadwal halaman pemasaran.
Schedule::command('pemasaran:jalankan-jadwal-halaman')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Trial yang masa berlakunya lewat ditutup tiap jam (MARKETING.md 12).
Schedule::command('pemasaran:kedaluwarsakan-trial')
    ->hourly()
    ->withoutOverlapping(55);

// Email pemasaran yang jatuh tempo diantrekan tiap lima menit (MARKETING.md 15).
Schedule::command('pemasaran:kirim-antrian-email')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Pesan WhatsApp yang jatuh tempo diantrekan tiap lima menit (MARKETING.md 16).
Schedule::command('pemasaran:kirim-antrian-whatsapp')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Penerbitan konten sosial yang jatuh tempo diantrekan tiap lima menit (MARKETING.md 18).
Schedule::command('pemasaran:terbitkan-sosial')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Status kiriman yang datang belakangan ditarik tiap jam (MARKETING.md 15).
Schedule::job(new SinkronkanStatusProvider)
    ->hourly()
    ->withoutOverlapping(55);

// Eksekusi otomasi yang jedanya lewat atau menunggu dicoba lagi (MARKETING.md 17).
Schedule::command('pemasaran:proses-antrian-otomasi')
    ->everyFiveMinutes()
    ->withoutOverlapping(5);

// Peringatan trial mendekati akhir, sehari sekali (MARKETING.md 17).
Schedule::command('pemasaran:peringatkan-trial-akan-berakhir')
    ->dailyAt('07:05')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Metrik kampanye dan alert growth dihitung sekali sehari (MARKETING.md 5).
Schedule::command('pemasaran:hitung-metrik')
    ->dailyAt('01:30')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Referral yang lewat jendelanya ditutup tiap hari (MARKETING.md 20).
Schedule::command('pemasaran:kedaluwarsakan-referral')
    ->dailyAt('02:00')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Lead partner yang lewat jendela atribusinya ditutup tiap hari (MARKETING.md 21).
Schedule::command('pemasaran:kedaluwarsakan-lead-partner')
    ->dailyAt('02:20')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Imbalan referral yang masih terutang diantrekan tiap jam (MARKETING.md 20).
Schedule::command('pemasaran:proses-reward-referral')
    ->hourly()
    ->withoutOverlapping(55);

// Dataset demo dibangun ulang tiap lima belas menit; intervalnya sendiri diatur per demo (MARKETING.md 11).
Schedule::command('pemasaran:reset-demo')
    ->everyFifteenMinutes()
    ->withoutOverlapping(15);

// FASE 45: retensi tabel operasional dan pemantau ukuran basis data (batas hosting 3 GB).
// Pemangkasan per potongan dengan batas waktu total (amanpoll.retensi.batas_detik).
Schedule::command('retensi:pangkas')
    ->dailyAt('03:15')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(120);

// Dicatat setelah seluruh pembersihan malam selesai, supaya angkanya keadaan sesudah pemangkasan.
Schedule::command('basisdata:pantau-ukuran')
    ->dailyAt('05:10')
    ->timezone(config('amanpoll.zona_waktu_default', 'Asia/Jakarta'))
    ->withoutOverlapping(60);
