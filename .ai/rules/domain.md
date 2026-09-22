---
paths:
  - 'app/Domain/**'
  - 'app/Domain/**/routes.php'
---

# Domain

## Struktur DDD per domain dan rutenya
Tiap domain memakai susunan yang sama:
`app/Domain/<Domain>/{Application/{Actions,Services},Domain/{Enums,ValueObjects,Contracts},Http/{Controllers,Requests,Resources,Policies},Infrastructure/Persistence/Models}` ditambah `routes.php` per domain.

- `routes.php` tiap domain dimuat `DomainServiceProvider` di dalam host dashboard. Jangan mendaftarkan rute tenant di `routes/web.php`.
- Logika tulis tinggal di Action, bukan di Controller.
- Kode lintas domain masuk `app/Shared/` atau `app/Core/`, jangan diduplikasi antar domain.
- Jangan membuat folder dasar baru tanpa persetujuan pengguna.

## Pemisahan host: dashboard, publik, partner
PRD 5.4. Tiga host, satu instalasi:
- Host dashboard (`AMANPOLL_DOMAIN_DASHBOARD`) — seluruh rute sistem, termasuk login dan formulir trial. Inilah tempat `app/Domain/*/routes.php` dimuat.
- Host publik (`AMANPOLL_DOMAIN_PUBLIK`) — `routes/publik.php`.
- Host partner (`AMANPOLL_DOMAIN_PARTNER`) — `routes/partner.php`.

Host yang variabelnya kosong **tidak mendaftarkan rutenya sama sekali**. Di dev, publik dan partner biasanya mati; di `phpunit.xml` ketiganya hidup. Kalau sebuah halaman tampak hilang setelah deploy, periksa dulu dengan `php artisan route:list --path=<jalur>` sebelum menduga kodenya rusak.
