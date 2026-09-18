# Checklist Rilis Amanpoll

- [ ] `php artisan test` lulus.
- [ ] `npm run build` lulus.
- [ ] `APP_DEBUG=false` di production.
- [ ] Database backup tersedia.
- [ ] `Amanpoll_Schema_Hosting.sql` hanya untuk instalasi awal; update berikutnya melalui migration.
- [ ] Folder `storage` dan `bootstrap/cache` writable.
- [ ] `public_html` tidak berisi `.env`, `vendor`, source app, atau dump database.
- [ ] Cron `schedule:run` berjalan setiap menit.
- [ ] Queue tidak menumpuk.
- [ ] HTTPS aktif.
- [ ] Smoke test login per organisasi, dashboard, upload, dan health endpoint selesai.
