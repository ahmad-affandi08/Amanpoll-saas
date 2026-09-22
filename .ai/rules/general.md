---
paths:
  - '**'
---

# General

## Gerbang verifikasi sebelum menyelesaikan perubahan
Jalankan seluruhnya sebelum menyatakan pekerjaan selesai: `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --memory-limit=2G`, `npx tsc --noEmit`, `npm run build`, `php artisan test --compact`.

Jumlah galat PHPStan harus tetap **165**. Naik berarti perubahanmu menambah galat baru — perbaiki akses atau tipenya, jangan ditutup. Dilarang: `@phpstan-ignore`, `@var` inline untuk menimpa inferensi, entri baseline baru, dan cast yang hanya untuk membungkam.

MariaDB di lingkungan dev kerap mati. Bila test gagal dengan "Connection refused", hidupkan ulang lalu ulangi — itu lingkungan, bukan perubahanmu:
`mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld && (mariadbd-safe --user=mysql >/tmp/mysql.log 2>&1 &)`

## Batasan shared hosting: tanpa daemon, tanpa layanan tambahan
Amanpoll berjalan di shared hosting (Niagahoster Business). Jangan memperkenalkan hal yang menuntut infrastruktur baru: Redis, Elasticsearch/Meilisearch, worker permanen, supervisor, websocket server.

Konsekuensi yang mengikat desain:
- Antrian dijalankan cron `queue:work --stop-when-empty`, bukan daemon.
- Pencarian memakai SQL `LIKE` biasa. `LIKE '%kata%'` berawalan joker memang tidak dapat memakai indeks; itu harga yang sudah diterima, bukan bug untuk "diperbaiki" dengan memasang mesin pencari.
- Dependensi baru (composer maupun npm) butuh persetujuan pengguna lebih dulu.

## Bahasa Indonesia untuk seluruh penamaan
PRD 6.2 dan DESIGN 29. Berlaku untuk nama kelas, method, variabel, kolom basis data, tabel, rute, dan teks antarmuka.

- Tabel dan kolom PascalCase Indonesia: `SukuCadang`, `JumlahTersedia`, `DibuatPada`.
- Kelas dan method Indonesia: `KelolaPesananPembelian`, `jalankan()`, `berikutnya()`.
- Istilah teknis yang tidak punya padanan mapan boleh tetap Inggris (`Status`, `Kode`, `Slug`).

Jangan menerjemahkan nama yang sudah ada hanya karena terlihat campur; menggantinya memutus data dan rute yang sudah berjalan.

## Git: jangan dorong ke main atau buka PR tanpa diminta
- Kerjakan di branch yang ditunjuk pengguna, dorong ke sana dengan `git push -u origin <branch>`.
- **Jangan** mendorong ke `main` kecuali pengguna memintanya saat itu juga. Izin sebelumnya tidak berlaku untuk dorongan berikutnya.
- **Jangan** membuat pull request kecuali diminta eksplisit.
- Akhiri pesan commit dengan `Co-Authored-By:` dan `Claude-Session:` sesuai instruksi sesi.
- Jangan menulis identitas model (nama atau ID model) di pesan commit, judul/isi PR, komentar kode, atau artefak apa pun yang masuk repo.
