# Domain SiklusAset

Bounded context Amanpoll untuk SiklusAset.

## Aturan

- Controller hanya menangani HTTP boundary.
- Request menangani validasi input.
- Action menangani satu use-case.
- Service menangani orkestrasi lintas use-case dalam domain yang sama.
- Query dipisahkan dari write path untuk laporan/pencarian berat.
- Repository interface berada di Domain, implementasi Eloquent di Infrastructure.
- Event domain tidak boleh bergantung pada Inertia/HTTP.
- Semua query tabel yang memiliki OrganisasiId wajib menghormati scope organisasi.
