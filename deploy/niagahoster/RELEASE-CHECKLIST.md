# Checklist Rilis Amanpoll

- [ ] `php artisan test` lulus.
- [ ] `npm run build` lulus.
- [ ] `APP_DEBUG=false` di production.
- [ ] Database backup tersedia: `php artisan cadangan:daftar --luar` menampilkan cadangan semalam di disk luar
      (`AMANPOLL_CADANGAN_DISK_LUAR`). Tanpa disk luar, cadangan hilang bersama server.
- [ ] `SESSION_DOMAIN=.amanpoll.id`, `SESSION_SECURE_COOKIE=true`, `MAIL_MAILER=amanpoll`.
- [ ] Schema dibangun lewat `php artisan migrate --force`; `Amanpoll_Schema_Hosting.sql` hanya dokumentasi/jalur cadangan.
- [ ] Folder `storage` dan `bootstrap/cache` writable.
- [ ] `public_html` tidak berisi `.env`, `vendor`, source app, atau dump database.
- [ ] Cron `schedule:run` berjalan setiap menit.
- [ ] Queue tidak menumpuk.
- [ ] HTTPS aktif.
- [ ] Smoke test login per organisasi, dashboard, dan upload selesai.
- [ ] `curl -fsS -H 'Accept: application/json' https://<host>/up` menjawab `{"status":"up"}`.
      Endpoint itu memeriksa koneksi basis data, direktori tulis, dan perjalanan
      bolak-balik cache; jawaban 500 berarti salah satunya mati dan alasannya ada
      di `storage/logs`.
