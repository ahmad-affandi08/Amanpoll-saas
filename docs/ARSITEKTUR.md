# Arsitektur Amanpoll

Amanpoll menggunakan **Laravel 13 + Modular Monolith + DDD pragmatis**.

## Prinsip

- `app/Core`: capability lintas-domain (tenancy, IAM, audit, approval, integrasi, berkas, notifikasi, keamanan).
- `app/Domain/<Domain>`: bounded context bisnis.
- `app/Shared`: abstraksi teknis yang stabil dan benar-benar reusable.
- Controller tipis: validasi -> Action/Service -> Resource/Inertia.
- Business rule tidak ditaruh di Controller, Request, React component, atau Repository.
- Semua tabel dengan `OrganisasiId` otomatis memakai scope organisasi.
- ULID adalah identifier bisnis utama.
- Waktu disimpan UTC; presentasi mengikuti zona waktu organisasi.
- Query/reporting berat dipisah dari write path.
- Integrasi eksternal wajib idempotent dan tercatat di audit/outbox.

## Bangunan bersama yang wajib dipakai ulang

Jangan menulis ulang yang sudah ada. Empat ini dipakai lintas domain dan punya
jebakan yang sudah dibayar mahal sekali:

| Dipakai untuk | Kelas |
| --- | --- |
| Daftar tabel: cari, urut, faset, paginasi | `App\Shared\Infrastructure\Persistence\DaftarTersaring` |
| Kode data induk terbit sendiri (`GDG-0001`) | `App\Core\Penomoran\PunyaKodeOtomatis` |
| Nomor dokumen transaksi berurutan | `App\Domain\Platform\Application\Services\LayananNomorDokumen` |
| Penyaringan per organisasi | `App\Core\Organisasi\ScopeOrganisasi` |

Di sisi React, `DataTable` sudah memuat mode server; halaman daftar memakai prop
`server`, bukan menyusun paginasinya sendiri.

Aturan rinci beserta jebakannya ada di `.ai/rules/`, dipetakan per glob berkas.

## Host

Satu instalasi melayani tiga host: dashboard (seluruh rute sistem), publik, dan
partner. Host yang variabel domainnya kosong tidak mendaftarkan rutenya sama
sekali — itu disengaja, bukan kerusakan.

## Dependency

`Http -> Application -> Domain <- Infrastructure`

Tidak semua tabel boleh memperoleh generic CRUD route. Tabel ledger, audit, approval, stok, status history, outbox, idempotensi, dan sinkronisasi hanya dimutasi lewat use-case yang menjaga invariant.
