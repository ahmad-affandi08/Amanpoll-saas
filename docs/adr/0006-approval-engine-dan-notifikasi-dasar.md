# ADR 0006 — Approval Engine dan Notifikasi Dasar

Status: Diterima
Fase: TASK.md FASE 06

## Konteks

TASK.md menaruh FASE 06 sebelum modul bisnis manapun (Aset, Pengadaan,
dll.) dengan alasan eksplisit: "Approval dibangun sebelum transaksi yang
membutuhkannya." Sama seperti `RegistriEntitas` di FASE 05 melayani
lampiran/tag/komentar untuk modul manapun tanpa modul itu tahu detailnya,
`Persetujuan` di fase ini harus bisa menyetujui/menolak ENTITAS APAPUN
tanpa domain `Persetujuan` tahu apa isi entitas itu — dibuktikan konkret
di ADR ini lewat `Lokasi` sebagai entitas uji, persis pola pembuktian
Gate 05.

## 1. RegistriEntitas Dipakai Lagi, Bukan Dibuat Ulang

`AlurPersetujuan.JenisEntitas` divalidasi lewat
`Rule::in(app(RegistriEntitas::class)->jenisDikenal())` — domain
`Persetujuan` tidak mendaftarkan entitasnya sendiri, ia hanya konsumen
peta yang sudah ada dari FASE 05. `AjukanPermintaanPersetujuan` memanggil
`RegistriEntitas::cariEntitas()` untuk resolve entitas target (otomatis
404 lintas-tenant lewat `ScopeOrganisasi` milik model target, sama seperti
ADR 0005 bagian 1) tanpa domain `Persetujuan` tahu apakah itu `Lokasi`,
`Aset`, atau apapun nanti.

Satu method baru ditambahkan langsung ke `RegistriEntitas`, bukan ke kelas
baru: `cariBanyakEntitas(string $jenisEntitas, array $entitasId)` —
lookup batch per jenis entitas, dipakai endpoint inbox (bagian 5) untuk
menghindari N+1 saat memeriksa banyak `PermintaanPersetujuan` sekaligus.
Ditaruh di `RegistriEntitas` karena kapabilitasnya genuinely reusable
untuk modul manapun yang nanti perlu resolve banyak entitas polimorfik
sekaligus, bukan cuma kebutuhan sesaat `Persetujuan`.

## 2. Approver "Unit": Memakai Kolom yang Sudah Ada, Belum Pernah Dipakai

TASK.md 06.02 minta approver bisa "user/role/unit", tapi skema
`TahapPersetujuan` cuma punya `PenggunaId`/`PeranId` — tidak ada kolom
unit sendiri. Investigasi skema menemukan `PenggunaPeran.UnitOrganisasiId`
dan `Pengguna.UnitOrganisasiId` sudah ada sejak migration awal tapi belum
pernah dipakai logika manapun di codebase.

Resolusi "Unit" dirancang dinamis, bukan lewat kolom baru: baca
`UnitOrganisasiId` milik ENTITAS TARGET (mis. `Lokasi.UnitOrganisasiId`),
lalu cari semua `Pengguna` yang punya baris `PenggunaPeran` dengan
`UnitOrganisasiId` yang sama (opsional difilter lebih lanjut oleh
`PeranId` tahap itu kalau diisi). Konsekuensinya: `TahapPersetujuan`
dengan `JenisPenyetuju='Unit'` TIDAK mengisi `PeranId`/`PenggunaId` sama
sekali — penyetujunya ditentukan sepenuhnya oleh unit organisasi entitas
yang sedang diproses, bukan oleh baris tahap itu sendiri. Kalau entitas
target tidak punya `UnitOrganisasiId` (null), `LayananPenyetuju` melempar
`AturanBisnisDilanggar` — desain "unit" secara inheren mensyaratkan
entitasnya scoped ke unit, gagal eksplisit lebih baik daripada diam-diam
mengembalikan nol penyetuju.

Dibuktikan `test_penyetuju_berbasis_unit`: satu `Lokasi` di-scope ke unit
tertentu, satu approver ditempatkan di unit itu lewat
`PenggunaPeran.UnitOrganisasiId`, satu approver lain TIDAK — approver
dalam unit berhasil memutuskan, approver luar unit dapat 403.

## 3. LayananPenyetuju: Satu Titik untuk "Siapa Boleh Memutuskan"

`App\Domain\Persetujuan\Application\Services\LayananPenyetuju` adalah
satu-satunya tempat yang tahu cara resolve calon penyetuju per
`JenisPenyetuju` (`calonPenyetuju()`) dan cara cek eligibility
(`bolehMemutuskan()`). Baik `SetujuiPermintaanPersetujuan`,
`TolakPermintaanPersetujuan`, `LayananNotifikasiPersetujuan` (siapa yang
diberitahu), maupun `PermintaanPersetujuanController::inbox()` (siapa yang
melihat permintaan di kotak masuknya) memanggil kelas yang sama — tidak
ada logika "siapa penyetuju" yang terduplikasi di controller atau Action
manapun. Larangan self-approval (`TahapPersetujuan.BolehMenyetujuiSendiri`,
default false) diperiksa di titik yang sama
(`bolehMemutuskan()`), sehingga baik jalur approve/reject HTTP maupun
jalur tampilan inbox otomatis konsisten.

## 4. Rejection Fail-Fast: Keterbatasan Skema, Bukan Kelalaian

TASK.md 06.02 minta "rejection behavior" configurable, tapi
`TahapPersetujuan` tidak punya kolom untuk itu (mis. "kembali ke tahap
sebelumnya" vs "batalkan semua"). `TolakPermintaanPersetujuan` sengaja
mengimplementasikan HANYA fail-fast: satu penolakan di tahap manapun
langsung mengakhiri seluruh `PermintaanPersetujuan`
(`Status=Ditolak`, `SelesaiPada` diisi) — perilaku paling umum dan paling
aman sebagai default. Keputusan ini didokumentasikan lewat PHPDoc
level-kelas di `TolakPermintaanPersetujuan` sendiri, bukan didiamkan:
kalau modul bisnis nanti butuh rejection routing yang lebih canggih,
skema `TahapPersetujuan` perlu kolom baru dulu — bukan sesuatu yang bisa
diam-diam "dianggap sudah ada".

Alasan sama berlaku untuk `KondisiAktivasi` (`AlurPersetujuan`) dan
`Kondisi` (`TahapPersetujuan`): keduanya kolom JSON yang divalidasi
sebagai array opaque tapi TIDAK punya mesin evaluasi apapun di fase ini
— tidak ada domain bisnis nyata (mis. ambang nilai pengadaan) yang
mengonsumsinya, dan menulis evaluator generik tanpa kebutuhan konkret
adalah premature abstraction. `DefinisiKonfigurasi` sudah punya kunci
`Persetujuan.AmbangNilai` yang mengantisipasi ini; evaluasi sungguhan
ditunda sampai modul bisnis pertama (kemungkinan Pengadaan) benar-benar
membutuhkannya.

## 5. Inbox: Batching Dua Kali untuk Menghindari N+1

`PermintaanPersetujuanController::inbox()` perlu memeriksa eligibility
setiap `PermintaanPersetujuan` berstatus Menunggu terhadap pengguna yang
login — naif-nya itu berarti satu query `TahapPersetujuan` dan satu query
resolve-entitas PER permintaan. Dua batching diterapkan:

1. Satu `whereIn` untuk semua `TahapPersetujuan` yang relevan sekaligus
   (dikelompokkan per `AlurPersetujuanId#Urutan`).
2. Satu `RegistriEntitas::cariBanyakEntitas()` per `JenisEntitas` berbeda
   yang muncul di batch permintaan (dikelompokkan dulu via
   `groupBy('JenisEntitas')` supaya lookup `Lokasi` dan lookup jenis
   entitas lain tidak saling mencampur query).

Baru setelah kedua peta itu di tangan, `LayananPenyetuju::bolehMemutuskan()`
dipanggil per permintaan tanpa query tambahan.

## 6. PermintaanPersetujuan: Snapshot Lewat DataTambahan, Bukan Duplikasi Kolom

"Snapshot konteks penting" (checklist 06.03) diwujudkan lewat kolom
`DataTambahan` (JSON, opsional) yang diisi pemohon saat mengajukan —
bukan dengan menyalin kolom-kolom entitas target ke tabel
`PermintaanPersetujuan` (yang akan beda-beda per `JenisEntitas` dan tidak
mungkin diskemakan generik). `EntitasId`+`JenisEntitas` tetap jadi
sumber kebenaran untuk data entitas terkini; `DataTambahan` untuk konteks
tambahan yang relevan SAAT pengajuan (mis. alasan pengajuan) yang mungkin
sudah tidak sama dengan keadaan entitas saat keputusan dibuat kemudian.

`TahapSaatIni` (integer, merujuk `TahapPersetujuan.Urutan`) adalah
satu-satunya state machine current-stage — tidak ada FK langsung ke
`TahapPersetujuan` supaya alur bisa diedit tahapnya di masa depan tanpa
memutus riwayat permintaan lama (meski FASE ini mengunci tahap tidak bisa
diubah selama `AlurPersetujuan.Aktif=true`, lihat bagian 7).

## 7. Aktif/Nonaktif: Mengunci Struktur Tahap Saat Dipakai

`AlurPersetujuan` baru SELALU dibuat `Aktif=false`
(`BuatAlurPersetujuan`) — mencegah alur setengah-jadi (nol tahap) tiba-tiba
bisa dipakai mengajukan permintaan. `AktifkanAlurPersetujuan` menolak
kalau `tahapPersetujuan()->count() === 0` (memenuhi checklist "validasi
tidak ada tahap kosong"). Begitu aktif, `BuatTahapPersetujuan`/
`UbahTahapPersetujuan`/`HapusTahapPersetujuan` menolak modifikasi apapun
selama alurnya masih `Aktif` — mencegah tahap berubah di tengah jalan
saat ada `PermintaanPersetujuan` yang sedang berjalan mengacu ke
`Urutan` tahap tertentu. Admin harus menonaktifkan dulu untuk mengubah
struktur, konsisten dengan filosofi "state transisi eksplisit" yang sama
seperti `Aktif`/`Nonaktif` pada `KonfigurasiOrganisasi` di FASE 04.

## 8. KeputusanPersetujuan: Append-Oriented, Idempoten Lewat Constraint

`KeputusanPersetujuan` tidak pernah di-update atau dihapus — setiap
approve/reject adalah INSERT baru (checklist "append-oriented"). Constraint
unik `(PermintaanPersetujuanId, TahapPersetujuanId, PenyetujuId)` mencegah
satu penyetuju memutuskan dua kali pada tahap yang sama; dicek eksplisit
di Action (`KonflikData` 409) sebelum insert supaya pesan error jelas,
bukan mengandalkan exception constraint database yang generik. Timestamp
`DiputuskanPada` diisi `now()->toImmutable()` mengikuti cast
`immutable_datetime` yang dipakai konsisten sejak FASE 01 (ADR 0001).

`LayananAudit::catat()` dipanggil dari `SetujuiPermintaanPersetujuan`/
`TolakPermintaanPersetujuan` (bukan controller) dengan kode aksi
`PermintaanPersetujuan.Disetujui`/`PermintaanPersetujuan.Ditolak` —
memenuhi checklist "Audit" di 06.04, konsisten dengan keharusan yang sama
di Komentar FASE 05 (ADR 0005 bagian 2).

## 9. Notifikasi: Opt-Out, Bukan Opt-In

`LayananNotifikasi::kirim()` mengirim ke kanal `InApp` secara default
KECUALI pengguna secara eksplisit menonaktifkannya lewat
`PreferensiNotifikasi` — tidak ada baris preferensi tersimpan berarti
"aktif" (`$preferensi === null || $preferensi->Aktif`). Alasan: notifikasi
approval (`Persetujuan.PerluTindakan`, dll.) adalah informasi operasional
penting yang pengguna baru harus terima tanpa perlu mengaktifkan apapun
dulu — kebalikan dari pola marketing opt-in. `KatalogPeristiwaNotifikasi`
(katalog statis kode peristiwa + label) mengikuti pola persis
`DefinisiKonfigurasi` yang sudah ada sejak FASE 04, supaya menambah jenis
peristiwa notifikasi baru nanti (dari modul manapun) tidak perlu migration
baru — cukup tambah entri ke katalog.

**Kanal Email bersifat opsional secara desain, bukan hanya "belum
diimplementasi".** `Notifikasi::KANAL_EMAIL` sudah didukung penuh di
`KirimNotifikasi::handle()` (lewat `NotifikasiUmum extends Notification`,
pola sama seperti `ResetKataSandiNotification`), tapi kanal bawaan yang
dipakai `LayananNotifikasi::kirim()` kalau tidak diminta eksplisit hanya
`InApp` — modul pemanggil (mis. `LayananNotifikasiPersetujuan`) yang
memutuskan kanal mana yang dipakai per peristiwa, konsisten dengan target
produksi Niagahoster shared hosting yang perlu SMTP dikonfigurasi
terpisah dan tidak boleh jadi blocker kalau belum di-setup.

## 10. Job Notifikasi: Bypass ScopeOrganisasi Kedua di Codebase

`KirimNotifikasi::handle()` berjalan lewat `queue:work` tanpa konteks HTTP
— `KonteksOrganisasi` tidak pernah ditetapkan, sehingga
`Notifikasi::find($id)` biasa akan selalu null (`ScopeOrganisasi` fail
closed, `1=0`, lihat ADR 0002). Job ini memanggil
`Notifikasi::withoutGlobalScope(ScopeOrganisasi::class)->find($id)` —
bypass legitimate kedua di seluruh codebase setelah
`catatan-akses:bersihkan` di FASE 05 (ADR 0005 bagian 4), didokumentasikan
lewat PHPDoc yang merujuk balik ke precedent itu supaya developer
berikutnya tahu ini pola yang sudah disetujui, bukan kebocoran isolasi
tenant yang tidak sengaja.

Job dikirim dengan `dispatch($notifikasi->Id)` (ID saja, bukan model utuh)
mengikuti best-practice Laravel standar untuk `ShouldQueue` — payload job
tetap kecil dan model selalu dibaca fresh saat job benar-benar dieksekusi.
Kegagalan (`failed()`) menyimpan `KesalahanTerakhir` dan
`Status=STATUS_GAGAL` — checklist "failure handling" 06.05 dipenuhi tanpa
mekanisme retry-tak-terbatas yang bisa membanjiri antrian di shared
hosting (`tries=3` bawaan Laravel).

## 11. Frontend: Halaman Admin dan Panel Self-Service Terpisah

`AlurPersetujuan/Index.tsx` (admin, `Persetujuan.Kelola`) mengelola
struktur alur+tahap. `PermintaanPersetujuan/Index.tsx` (semua pengguna
login, tanpa gating izin — setiap orang berpotensi punya permintaan atau
tugas approval) memakai dua tab (`Perlu Tindakan Saya` / `Permintaan
Saya`) memanggil endpoint JSON generik lewat `apiPersetujuan`, mengikuti
pola axios-per-domain yang sudah dipakai sejak `features/Berkas/api.ts`.
`NotificationBell` (poll 60 detik) ditempel ke `AppLayout` global — satu
komponen dipakai lintas semua halaman, bukan duplikasi per halaman.

**Halaman preferensi memerlukan pemisahan route JSON vs Inertia** —
ditemukan langsung lewat smoke test Playwright: `PreferensiNotifikasiController
@index` awalnya SELALU mengembalikan `JsonResponse`, sehingga navigasi
browser ke `/notifikasi/preferensi` menampilkan JSON mentah, bukan
halaman React. Diperbaiki dengan memisahkan `halaman()` (render Inertia,
route `GET /notifikasi/preferensi`) dari `index()` (JSON, dipindah ke
`GET /notifikasi/preferensi/data`) — pola yang sama persis dengan
`PermintaanPersetujuanController::halaman()` vs `milikSaya()`/`inbox()`.
Bug ini murni ditemukan lewat smoke test browser sungguhan, bukan lewat
`php artisan test` (yang memanggil endpoint langsung dengan header
`Accept: application/json` sehingga tidak pernah menyentuh jalur render
Inertia) — konsisten dengan catatan ADR 0005 bagian 8 bahwa kelas bug
"controller mengembalikan bentuk salah tergantung siapa yang memanggil"
hanya kelihatan lewat pengujian end-to-end sungguhan.

## 12. Bukti Gate 06: Use Case Dummy End-to-End

Smoke test Playwright menjalankan siklus penuh terhadap `Chromium`
sungguhan: login → buat+aktifkan `AlurPersetujuan` untuk `Lokasi` →
ajukan `PermintaanPersetujuan` → tahap muncul di kotak masuk penyetuju →
`Setujui` lewat dialog → status berubah `Disetujui` di tab "Permintaan
Saya" → notifikasi penyelesaian muncul di `NotificationBell` — nol error
JavaScript, mengikuti pola pembuktian yang sama seperti "Integrasi Nyata
ke Lokasi" di ADR 0005 bagian 9. Dibuktikan juga lewat test backend:
`tests/Feature/Persetujuan/PersetujuanEngineTest.php` (17 test, mencakup
approver Pengguna/Peran/Unit, self-approval, duplicate-decision,
multi-tahap, rejection fail-fast) dan `tests/Feature/Notifikasi/
NotifikasiTest.php` (7 test, mencakup preferensi opt-out, kegagalan
kanal, dan mark-as-read) — 207 test lulus total, PHPStan level 7 bersih,
`tsc --noEmit` bersih.

### Gate 06

Modul bisnis manapun nanti (Aset, Pengadaan, dll.) dapat memasang alur
persetujuan pada aksi tertentu cukup dengan memanggil
`AjukanPermintaanPersetujuan::jalankan($alur, $entitasId, $data, $pemintaId)`
setelah mendaftarkan jenis entitasnya di `RegistriEntitas` — tanpa
menyentuh domain `Persetujuan` maupun `Notifikasi`, dan otomatis
mendapat notifikasi in-app/email untuk setiap penyetuju serta pemohon
tanpa kode tambahan.
