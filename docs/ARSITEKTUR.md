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

## Dependency

`Http -> Application -> Domain <- Infrastructure`

Tidak semua tabel boleh memperoleh generic CRUD route. Tabel ledger, audit, approval, stok, status history, outbox, idempotensi, dan sinkronisasi hanya dimutasi lewat use-case yang menjaga invariant.
