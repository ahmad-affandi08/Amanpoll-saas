# Checklist Rilis Amanpoll

- [ ] `php artisan test` lulus.
- [ ] `npm run build` lulus.
- [ ] `APP_DEBUG=false` di production.
- [ ] Database backup tersedia.
- [ ] Schema dibangun lewat `php artisan migrate --force`; `Amanpoll_Schema_Hosting.sql` hanya dokumentasi/jalur cadangan.
- [ ] Folder `storage` dan `bootstrap/cache` writable.
- [ ] `public_html` tidak berisi `.env`, `vendor`, source app, atau dump database.
- [ ] Cron `schedule:run` berjalan setiap menit.
- [ ] Queue tidak menumpuk.
- [ ] HTTPS aktif.
- [ ] Smoke test login per organisasi, dashboard, upload, dan health endpoint selesai.
