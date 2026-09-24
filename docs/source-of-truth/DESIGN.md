# DESIGN — Amanpoll

| Atribut | Nilai |
|---|---|
| Produk | Amanpoll |
| Dokumen | UI/UX Design System |
| Versi | 1.0.0 |
| Status | Baseline Implementasi |
| Tanggal | 18 September 2026 |
| UI Stack | React 19 · Inertia.js · shadcn/ui · Tailwind CSS 4 |
| Tema | Light only |
| Karakter Visual | Teknisi · industri · presisi · operasional |
| Font Utama | IBM Plex Sans |
| Font Data Teknis | IBM Plex Mono |
| Mode Lapangan | Bahasa visual tersendiri untuk peran lapangan (Teknisi, Pelapor): lihat §36 |

---

## 1. Arah Desain

Amanpoll harus terasa seperti alat kerja profesional untuk teknisi dan operasional, bukan landing page startup, dashboard kripto, atau template admin generik.

**Arah N "Presisi" (disetujui pemilik produk 24 September 2026) berlaku untuk dasbor web tenant dan konsol platform:** rapi, presisi, betah dilihat, tidak ramai. Halaman putih; kartu dan tabel dibatasi garis tipis Garis-200 tanpa bayangan; sidebar terang; satu warna aksen (Teknisi-700) untuk aksi utama dan penanda aktif; warna status hanya untuk status. Tanpa gradien, tanpa mode gelap. Mode Lapangan (§36) dan halaman autentikasi (§37) punya arahnya sendiri dan tidak mengikuti bagian ini.

Karakter yang dituju:

- Presisi.
- Kokoh.
- Efisien.
- Terstruktur.
- Teknis.
- Mudah dipindai.
- Tidak dekoratif berlebihan.
- Tidak menggunakan estetika "AI dashboard" dengan gradient besar, glassmorphism, glow, atau kartu terlalu mengambang.

Referensi rasa visual secara konseptual:

```text
industrial control panel
+ maintenance worksheet
+ modern enterprise software
+ field technician tool
```

Bukan:

```text
neon SaaS
glassmorphism
rounded-everything
dark dashboard
gradient-heavy
oversized marketing UI
```

---

## 2. Prinsip UX

### 2.1 Operasional Lebih Penting dari Dekorasi

Prioritas layar:

1. Apa yang sedang terjadi.
2. Apa yang harus dilakukan.
3. Status.
4. Risiko/jatuh tempo.
5. Detail.
6. Aksi.

### 2.2 Scanability

Pengguna harus dapat membaca status dalam beberapa detik.

Gunakan:

- Alignment konsisten.
- Label singkat.
- Badge status.
- Hierarki typography.
- Divider.
- Spacing yang konsisten.
- Ikon hanya ketika membantu pengenalan.

### 2.3 Progressive Disclosure

Jangan tampilkan 40 field sekaligus.

Contoh detail aset:

```text
Ringkasan
Pemeliharaan
Riwayat
Kalibrasi
Dokumen
Biaya
Kepatuhan
Aktivitas
```

### 2.4 Mobile adalah Alat Kerja

Mobile bukan versi desktop yang diperkecil. Flow teknisi harus dirancang agar dapat dilakukan dengan satu tangan saat berada di lapangan.

---

# 3. Typography

## 3.1 Font Utama — IBM Plex Sans

Gunakan:

```text
IBM Plex Sans
400 Regular
500 Medium
600 SemiBold
700 Bold
```

Alasan:

- Terlihat teknis.
- Sangat terbaca.
- Tidak terasa seperti font SaaS generik.
- Cocok untuk angka, form, table, dan dashboard operasional.
- Memiliki karakter engineering tanpa mengorbankan readability.

Fallback:

```css
font-family:
  "IBM Plex Sans",
  ui-sans-serif,
  system-ui,
  -apple-system,
  BlinkMacSystemFont,
  "Segoe UI",
  sans-serif;
```

Dasbor web tidak menggunakan:

- Inter.
- Plus Jakarta Sans (hanya Mode Lapangan §36.4 dan halaman autentikasi §37, keduanya disetujui pemilik produk pada 24 September 2026).
- Roboto.
- Poppins.

## 3.2 Font Data Teknis — IBM Plex Mono

Gunakan hanya untuk:

- Asset code.
- Serial number.
- QR reference.
- API key prefix.
- Nomor dokumen tertentu.
- Nilai meter bila membantu alignment.
- Payload/debug admin terbatas.

Jangan gunakan mono untuk paragraf atau form umum.

## 3.3 Type Scale

| Token | Size | Line Height | Weight | Penggunaan |
|---|---:|---:|---:|---|
| Display | 30px | 38px | 700 | Dashboard heading terbatas |
| H1 | 24px | 32px | 700 | Judul halaman |
| H2 | 20px | 28px | 600 | Section utama |
| H3 | 16px | 24px | 600 | Card/section |
| Body | 14px | 21px | 400 | Default aplikasi |
| BodyStrong | 14px | 21px | 600 | Label penting |
| Small | 12px | 18px | 400 | Metadata |
| Caption | 11px | 16px | 500 | Auxiliary label |

Body default 14px dipilih karena aplikasi berorientasi data. Form pada mobile tetap harus terbaca dan tidak lebih kecil dari 14px; field yang rawan auto-zoom Safari dapat memakai 16px pada breakpoint mobile.

---

# 4. Warna

## 4.1 Palet Utama

Amanpoll menggunakan warna yang diasosiasikan dengan equipment, engineering, safety, dan maintenance.

| Token | Hex | Fungsi |
|---|---|---|
| `Teknisi-900` | `#17324D` | Heading kuat, identitas utama |
| `Teknisi-800` | `#1D4663` | Hover gelap |
| `Teknisi-700` | `#205B78` | Secondary strong |
| `Teknisi-600` | `#27718F` | Brand action |
| `Teknisi-500` | `#3487A6` | Accent terbatas |
| `Teknisi-300` | `#8CBFD2` | Garis sorotan |
| `Teknisi-200` | `#B9D7E3` | Rentang terpilih, garis tepi aktif |
| `Teknisi-100` | `#DCEBF1` | Latar terpilih |
| `Teknisi-50` | `#EEF5F8` | Latar hover dan fokus (token `accent`) |
| `Safety-700` | `#9A4508` | Teks peringatan di atas tint |
| `Safety-600` | `#D97706` | Warning utama |
| `Safety-500` | `#F59E0B` | Due soon / attention |
| `Sukses-700` | `#116A4A` | Teks sukses di atas tint |
| `Sukses-600` | `#16835B` | Selesai / aktif / aman |
| `Sukses-200` | `#B7E2CF` | Garis tepi sukses |
| `Sukses-50` | `#ECF7F2` | Latar sukses lembut |
| `Bahaya-700` | `#A3342F` | Hover destructive, teks bahaya di atas tint |
| `Bahaya-600` | `#C2413B` | Gagal / overdue / destructive |
| `Info-700` | `#2C5A87` | Teks informasi di atas tint |
| `Info-600` | `#376FA6` | Informasi |
| `Grafit-950` | `#172027` | Teks utama |
| `Grafit-700` | `#44515A` | Teks sekunder |
| `Grafit-500` | `#5F6B73` | Metadata (≥ 4,5:1 di atas putih dan latar halaman) |
| `Garis-300` | `#D7DEE3` | Tepi isian (token `input`) |
| `Garis-200` | `#E7ECEF` | Border kartu, tabel, divider (token `border`) |
| `Permukaan-100` | `#F4F7F8` | Segmen aktif, latar nonaktif |
| `Permukaan-50` | `#FAFBFC` | Sidebar, kepala tabel, hover baris |
| `Putih` | `#FFFFFF` | Latar halaman, kartu, form |

## 4.2 Penggunaan Warna

Primary action:

```text
Background: Teknisi-700 / #205B78
Hover: Teknisi-800 / #1D4663
Text: #FFFFFF
```

Safety amber hanya untuk:

- Due soon.
- Warning.
- Pekerjaan tertunda.
- Maintenance attention.
- Stok menipis.

Jangan menggunakan amber sebagai warna tombol utama.

Red hanya untuk:

- Error.
- Destructive.
- Overdue kritis.
- Failure.

Green hanya untuk:

- Success.
- Healthy.
- Active.
- Completed.

Status tidak boleh dibedakan hanya dengan warna. Selalu sertakan teks/ikon bila dibutuhkan.

Hover, fokus, dan opsi tersorot memakai tint terang (`accent` = Teknisi-50) dengan
teks Teknisi-900, bukan Teknisi-500: teks gelap dan metadata abu-abu tidak
terbaca di atas biru pekat. Setiap teks memenuhi WCAG AA (4,5:1; 3:1 untuk teks
≥ 24px atau ≥ 18,66px tebal) dalam keadaan diam, hover, dan fokus. Kelas warna
hanya boleh menunjuk token yang ada di `app.css`; shade tanpa token tidak
menghasilkan CSS sama sekali (`WarnaPaletTerdefinisiTest`).

Teks status di atas latar tint (badge, alert, banner) memakai shade -700
(`Sukses-700`, `Safety-700`, `Bahaya-700`, `Info-700`); shade -600 di atas tint
jatuh di bawah 4,5:1. Latar halaman putih (arah N); kartu di atasnya dibedakan
oleh garis Garis-200, bukan oleh bayangan atau latar abu. Tombol sekunder, ghost,
dan segmen memakai tint transparan Grafit-950 (5–8%) supaya terbaca di atas putih
maupun Permukaan-50. Angka KPI berwarna netral (Grafit-950); warna status hanya
di keterangan kecil, titik, atau badge. Dialog dan sheet berlatar putih.

---

# 5. CSS Token Tailwind 4

Contoh baseline:

```css
@import "tailwindcss";

@theme {
  --font-sans: "IBM Plex Sans", ui-sans-serif, system-ui, sans-serif;
  --font-mono: "IBM Plex Mono", ui-monospace, monospace;

  --color-teknisi-900: #17324D;
  --color-teknisi-800: #1D4663;
  --color-teknisi-700: #205B78;
  --color-teknisi-600: #27718F;
  --color-teknisi-500: #3487A6;
  --color-teknisi-300: #8CBFD2;
  --color-teknisi-200: #B9D7E3;
  --color-teknisi-100: #DCEBF1;
  --color-teknisi-50: #EEF5F8;

  --color-safety-700: #9A4508;
  --color-safety-600: #D97706;
  --color-safety-500: #F59E0B;

  --color-sukses-700: #116A4A;
  --color-sukses-600: #16835B;
  --color-sukses-200: #B7E2CF;
  --color-sukses-50: #ECF7F2;
  --color-bahaya-700: #A3342F;
  --color-bahaya-600: #C2413B;
  --color-info-700: #2C5A87;
  --color-info-600: #376FA6;

  --color-grafit-950: #172027;
  --color-grafit-700: #44515A;
  --color-grafit-500: #5F6B73;

  --color-garis-300: #D7DEE3;
  --color-garis-200: #E7ECEF;
  --color-permukaan-100: #F4F7F8;
  --color-permukaan-50: #FAFBFC;

  --radius-xs: 4px;
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 10px;
  --radius-xl: 12px;
}
```

Tidak membuat token dark mode.

---

# 6. Radius

Amanpoll tidak menggunakan bentuk terlalu bulat (arah N).

| Komponen | Radius | Kelas |
|---|---:|---|
| Input, Select, Combobox, pemilih tanggal | 6px | `rounded-sm` |
| Button | 6px | `rounded-sm` |
| Badge | 4px | `rounded-xs` |
| Card, table container, panel, dropdown, popover | 8px | `rounded-md` |
| Dialog | 10px | `rounded-lg` |
| Mobile bottom sheet | 12px pada sudut atas | |

Hindari:

```text
rounded-xl
rounded-2xl
rounded-3xl
rounded-full
```

`rounded-full` hanya boleh untuk indikator bulat kecil, progress bar, dan avatar, bukan tombol utama atau badge status default.

---

# 7. Spacing

Gunakan skala 4 px.

```text
4
8
12
16
20
24
32
40
48
```

Default:

- Isi halaman: padding 28px × 32px desktop, 20px × 16px mobile.
- Page gap: 20–24px desktop, 16px mobile.
- Card padding: 20px (`px-5 py-4`) desktop, 16px mobile.
- Form field gap: 16px.
- Label to input: 6px.
- Table row: 48px.
- Toolbar/penyaring: 8px gap dalam satu baris.

Jangan membuat dashboard terlalu lega sampai informasi penting tersebar jauh.

---

# 8. Shadow

Arah N: kartu, panel, tabel, tombol, dan isian **tanpa bayangan**; batasnya garis Garis-200.

Bayangan hanya untuk lapisan yang benar-benar mengapung di atas halaman:

```css
/* dropdown, popover, select, tooltip grafik */
box-shadow: 0 6px 20px rgb(23 32 39 / 0.08);
/* dialog */
box-shadow: 0 16px 48px rgb(23 32 39 / 0.14);
```

Cincin fokus dan halo `0 0 0 Npx` bukan bayangan dan tetap dipakai. Hindari shadow tebal, glow, dan floating-card.

---

# 9. Layout Utama

## 9.1 Desktop

```text
┌──────── Sidebar 264 ────────┬──────────────────────────────┐
│ Amanpoll                    │ Topbar 52                    │
│                             ├──────────────────────────────┤
│ Dashboard                   │ Breadcrumb                   │
│ Aset                        │ Page title        Actions    │
│ Operasional                 │                              │
│ Persediaan                  │ Main content                 │
│ Pengadaan                   │                              │
│ Kepatuhan                   │                              │
│ Laporan                     │                              │
│ Pengaturan                  │                              │
└─────────────────────────────┴──────────────────────────────┘
```

Sidebar:

- 264px expanded.
- 72px collapsed.
- Background `Permukaan-50` dengan garis kanan Garis-200 (arah N).
- Item setinggi 32px, teks 13,5px Grafit-700, ikon Lucide 16px Grafit-500.
- Hover: tint Grafit-950/5. Item aktif: latar putih dengan cincin Garis-200, teks tebal Grafit-950, ikon Teknisi-700. Bukan blok berwarna, bukan glow.
- Group label kalimat biasa 12px Grafit-500 (bukan huruf kapital).
- Maksimal dua level navigasi langsung; submenu bergaris kiri Garis-200.

Topbar 52px putih bergaris bawah, menempel di atas saat halaman digulir.

Topbar:

- Kiri: tombol sidebar, lalu kotak cari global (ikon saja di ponsel).
- Kanan: indikator sinkronisasi dan pembaruan (hanya saat relevan), lalu lonceng notifikasi.
- Pencarian global dibuka juga dengan Ctrl+K / ⌘K atau "/". Hasilnya dikelompokkan
  per modul dengan ikon dan keterangan (kode, nomor seri, lokasi, status), dan
  dapat dipilih dengan panah atas/bawah lalu Enter.

## 9.2 Tablet

- Sidebar dapat collapse.
- Content maksimal memanfaatkan viewport.
- Toolbar boleh wrap.
- Form dua kolom berubah satu kolom bila ruang tidak cukup.

## 9.3 Mobile

- Sidebar menjadi drawer.
- Topbar 56px.
- Primary action dapat sticky di bawah bila benar-benar membantu flow.
- Detail menggunakan stacked sections.
- Tabs dapat horizontal scroll.
- Table operasional dapat berubah menjadi list card bila data penting tidak cocok horizontal scroll.
- Hindari modal besar; gunakan full-screen dialog/sheet untuk workflow panjang.

---

# 10. Breakpoint

Gunakan Tailwind breakpoint sebagai baseline:

| Mode | Lebar |
|---|---|
| Mobile kecil | 360–639 |
| Tablet | 640–1023 |
| Desktop | 1024–1439 |
| Wide | ≥1440 |

Amanpoll harus tetap usable di 320 px bila dibuka, tetapi target QA utama dimulai dari 360 px.

---

# 11. Navigation Information Architecture

Sidebar utama:

```text
Dashboard

Aset
├── Daftar Aset
├── Mutasi Aset
├── Serah Terima
└── Penghapusan

Operasional
├── Keluhan
├── Perintah Kerja
├── Preventive
├── Inspeksi
└── Kalibrasi

Persediaan
├── Gudang
├── Suku Cadang
├── Stok
├── Mutasi Stok
└── Reservasi

Perencanaan & Pengadaan
├── Anggaran
├── Usulan Aset
├── Rencana Pengadaan
├── Permintaan Pembelian
├── Permintaan Penawaran
├── Pesanan Pembelian
├── Penerimaan
└── Tagihan Penyedia

Penyedia & Kontrak
├── Penyedia
└── Kontrak

Kepatuhan
├── Standar
└── Sertifikasi

Persetujuan

Laporan

Administrasi
├── Organisasi
├── Unit
├── Lokasi
├── Pengguna
├── Peran & Izin
├── Integrasi
├── Audit
└── Pengaturan
```

Menu disembunyikan berdasarkan izin, tetapi authorization tetap dilakukan backend.

## 11.1 Konsol platform

Konsol platform (`/admin-platform`) memakai kerangka yang sama dengan dashboard tenant: sidebar terang yang bisa diciutkan, menu bergrup dari komponen bersama `MenuSidebar`, profil di kaki sidebar, dan header putih. Pembedanya hanya label "Konsol Platform" di kepala sidebar dan header.

```text
Bisnis
├── Paket
└── Langganan

Growth & Marketing
├── Ringkasan
├── Analitik (Dashboard Growth, Eksperimen A/B)
├── Prospek & Penjualan (Prospek, Aturan Skor, Trial, Demo Produk, Referral)
├── Situs & Konten (Halaman Publik, Formulir, Konten & SEO, Konten Sosial, Redirect)
└── Kampanye & Pesan (Kampanye, Otomasi, Template/Sequence Email, WhatsApp, Consent)

Pengaturan
└── Layanan Luar
```

Menu konsol disaring oleh izin admin platform dan oleh modul pemasaran yang aktif; halaman modul yang mati tidak ditawarkan karena rutenya menjawab 404. Daftar halaman pemasaran hanya satu (`HALAMAN_PEMASARAN`), dipakai sidebar dan kartu pintasan di Ringkasan.

---

# 12. Page Anatomy

Setiap halaman daftar:

```text
Breadcrumb
Judul + deskripsi singkat                [Aksi Utama]

Search | Filter | Filter | Reset        (satu baris, tanpa kartu)

Summary ringan bila berguna

DataTable / List

Pagination
```

Jangan memberi setiap halaman 4–6 kartu statistik bila tidak menambah nilai.

Baris penyaring (arah N): satu baris `flex-wrap gap-2` tanpa bingkai kartu. Kotak cari
256px berikon Search di kiri; Select/Combobox penyaring berlebar tetap (160–192px),
bukan merentang. Label penyaring boleh hanya untuk pembaca layar (`sr-only`) bila nilai
kontrolnya sudah menyebut apa yang disaring ("Semua status", "Semua lokasi"). Tombol
Terapkan varian sekunder; Reset varian ghost dan hanya tampil bila ada penyaring aktif.

Setiap halaman detail:

```text
Breadcrumb

[Icon/Code] Nama Entitas             Status
Metadata utama                       Actions

Summary strip

Tabs / sections

Activity / history
```

---

# 13. Komponen

## 13.1 Button

Variant:

- `utama`
- `sekunder`
- `outline`
- `ghost`
- `bahaya`

Ukuran (arah N):

- sm dan md 32px desktop, teks 13px, radius 6px, tanpa bayangan.
- lg 36px.
- Ponsel: semua tombol minimal 44px.

Hanya tombol utama yang berwarna (Teknisi-700). Outline: putih bertepi Garis-300.
Sekunder: tint Grafit-950/5 tanpa tepi. Ghost: teks Grafit-700, hover tint Grafit-950/5.
Keadaan nonaktif memakai warna (`Garis-200` dengan teks `Grafit-500`), bukan
`opacity-50`: separuh transparansi menurunkan teks tombol utama ke sekitar 1,6:1.

Button text menggunakan kata kerja:

```text
Simpan
Buat Aset
Tugaskan
Mulai Pekerjaan
Selesaikan
Setujui
Tolak
```

Hindari label ambigu:

```text
OK
Submit
Process
Yes
```

## 13.2 Input

- Height desktop 32px (sama dengan tombol), radius 6px, latar putih, tanpa bayangan.
- Mobile 40–44px untuk form utama.
- Border `Garis-300`.
- Focus ring tipis `Teknisi-600`.
- Error border `Bahaya-600`.
- Label di atas input.
- Placeholder bukan pengganti label.

## 13.3 Badge Status

Bentuk kotak datar radius 4px, teks 12px, tint tipis **tanpa garis tepi** (arah N).

Contoh:

```text
Aktif
Draf
Menunggu
Dikerjakan
Selesai
Overdue
Dibatalkan
```

Badge terdiri dari background tipis + text kuat. Setiap makna status punya
varian warnanya sendiri (netral, info, proses, perhatian, sukses, bahaya); dua
status yang berlawanan (Aktif/Nonaktif, Terbit/Draf) tidak boleh sama-sama abu.

## 13.4 DataTable

Header:

- 12,5px/500 Grafit-500, kalimat biasa.
- Surface `Permukaan-50`, garis bawah Garis-200.

Baris 48px, isi 14px Grafit-700 (kolom utama menebalkan dirinya sendiri), garis
antarbaris Garis-200, hover Permukaan-50, angka rata kanan dengan digit tabular.
Wadah tabel `rounded-md` bergaris Garis-200; toolbar di dalamnya dipisah garis bawah.
- Sticky bila tabel panjang dan konteks memungkinkan.

Fitur:

- Server pagination.
- Sort.
- Search.
- Filter.
- Row action.
- Column visibility hanya jika diperlukan.
- Bulk action hanya pada use case nyata.
- Checkbox tidak ditampilkan bila tidak ada bulk action.

Kolom ID/kode dapat memakai IBM Plex Mono.

## 13.5 StatCard

Gunakan hemat.

Format (arah N):

```text
Label kalimat biasa (13px Grafit-700)      Kelompok (12px)
1.248 (26px/600, netral)
metadata kecil (12,5px)
```

Beberapa angka yang berjajar digabung ke satu bingkai bersekat garis tipis
(`DeretStatistik` + `KartuStatistik menyatu`; di Dashboard, KPI angka selebar satu
kolom yang berurutan otomatis digabung), bukan kartu-kartu terpisah. Label tidak
berhuruf kapital, angka tidak diwarnai status.

Tidak menggunakan chart mini dekoratif bila tidak ada makna.

## 13.6 EmptyState

Harus menjelaskan:

- Apa yang kosong.
- Mengapa mungkin kosong.
- Aksi berikutnya bila user memiliki izin.

## 13.7 Dialog

Gunakan untuk:

- Konfirmasi.
- Form pendek.
- Keputusan sederhana.

Jangan gunakan dialog untuk form 20 field. Gunakan halaman atau sheet/full-screen mobile.

## 13.8 Sheet

Cocok untuk:

- Filter mobile.
- Quick detail.
- Assignment.
- Scan result action.

## 13.9 Timeline

Dipakai untuk:

- Status work order.
- Mutasi aset.
- Approval.
- Audit ringan.
- Calibration history.

Timeline bukan dekorasi; setiap item harus berisi timestamp dan actor bila relevan.

---

# 14. Form Design

## 14.1 Form Pendek

Satu kolom.

## 14.2 Form Sedang

Desktop dua kolom bila field saling independen.

Mobile selalu satu kolom.

## 14.3 Form Panjang

Pecah menjadi section:

```text
Identitas
Lokasi & Penanggung Jawab
Informasi Pembelian
Garansi
Informasi Teknis
Dokumen
```

Gunakan sticky save bar hanya bila form panjang dan risiko kehilangan perubahan tinggi.

## 14.4 Validation

Error muncul dekat field.

Contoh:

```text
Nomor seri sudah digunakan pada organisasi ini.
```

Bukan:

```text
Validation failed.
```

Server error umum ditampilkan pada alert/toast yang dapat dipahami.

Pesan bawaan aturan validasi berbahasa Indonesia (`lang/id/validation.php`).
Nama isian PascalCase ditulis sebagai kata (`JumlahAset` → "Jumlah aset wajib
diisi."), dan isian baris ditulis dengan nomor barisnya (`Detail.1.HargaSatuan` →
"Harga satuan baris 2 wajib diisi."). Pesan khusus di `messages()` hanya perlu
bila kalimat bawaannya belum menjelaskan aturan bisnisnya.

---

# 15. Dashboard

## 15.1 Dashboard Teknisi

> Pengguna lapangan (Teknisi dan Pelapor) tidak melihat dasbor ini: mereka langsung masuk Mode Lapangan (§36).
> Bagian ini hanya berlaku bagi pengguna yang memegang peran lapangan sekaligus peran meja dan memilih tampilan lengkap.

Urutan:

1. Pekerjaan hari ini.
2. Pekerjaan urgent.
3. Overdue.
4. Jadwal berikutnya.
5. Quick scan QR.
6. Pekerjaan baru.

Mobile:

```text
Selamat bekerja

[ Scan Aset ]

Hari Ini
5 Pekerjaan

URGENT
WO-10291
Pompa Gedung A
Keluhan kebocoran
[Mulai]

...
```

## 15.2 Dashboard Supervisor

- Open complaints.
- Unassigned work orders.
- SLA at risk.
- Overdue.
- Teknisi aktif.
- Due preventive.
- Stock alerts.

## 15.3 Dashboard Manajemen

- Asset condition.
- Maintenance cost.
- Downtime.
- SLA trend.
- MTTR.
- Planned vs unplanned.
- Budget.
- Vendor performance.
- Compliance.

Jangan menampilkan semua KPI sekaligus. Gunakan priority dan saved dashboard.

---

# 16. Halaman Aset

## 16.1 Daftar

Kolom desktop baseline:

```text
Kode
Nama Aset
Kategori
Lokasi
Kondisi
Status
Penanggung Jawab
Updated
Aksi
```

Mobile card:

```text
AST-000124                   Aktif
Air Compressor 02
Workshop • Area Utility

Kondisi: Baik
PIC: Budi Santoso

[Lihat]
```

## 16.2 Detail

Header:

```text
AST-000124
Air Compressor 02

Aktif · Baik

Lokasi
Workshop / Utility

PIC
Budi Santoso

[Scan/QR] [Buat Keluhan] [Aksi]
```

Tab:

```text
Ringkasan
Pekerjaan
Preventive
Inspeksi
Kalibrasi
Riwayat
Biaya
Dokumen
Kepatuhan
```

---

# 17. Halaman Perintah Kerja

PerintahKerja adalah layar kerja, bukan sekadar record detail.

Desktop layout:

```text
┌───────────────────────────────────────┬─────────────────────┐
│ WO-2026-00128                         │ Status              │
│ Perbaikan Air Compressor             │ Dikerjakan          │
│                                       │                     │
│ Masalah                               │ Teknisi             │
│ Diagnosis                             │ ...                 │
│ Tindakan                              │                     │
│ Checklist                             │ SLA                 │
│ Sparepart                             │ ...                 │
│ Foto/Dokumen                          │                     │
│                                       │ Asset               │
│ Timeline                              │ ...                 │
└───────────────────────────────────────┴─────────────────────┘
```

Mobile:

- Sticky status ringkas.
- Aksi utama dekat thumb zone.
- Timer/waktu kerja terlihat.
- Checklist mudah ditekan.
- Upload foto langsung.
- Sparepart search sederhana.
- Submit completion memvalidasi field wajib.

---

# 18. Status Visual

Mapping semantik, bukan hardcoded per halaman.

| Makna | Warna |
|---|---|
| Netral/Draf | Grafit |
| Informasi | Info |
| Sedang berjalan | Teknisi |
| Menunggu/Perhatian | Safety |
| Selesai/Aktif | Sukses |
| Gagal/Overdue | Bahaya |
| Nonaktif | Grafit muted |

Contoh:

```text
Dikerjakan → Teknisi
Menunggu Suku Cadang → Safety
Selesai → Sukses
Overdue → Bahaya
Dibatalkan → Grafit
```

---

# 19. Iconography

Gunakan Lucide React. Ikon 3D (clay) hanya dipakai di Mode Lapangan, dengan aturan §36.5; dasbor web tetap Lucide saja.

Aturan:

- Stroke 1.75–2.
- 16px untuk inline.
- 18–20px untuk navigation.
- 24px untuk empty state kecil.
- Jangan memakai ikon 48–64px dekoratif pada setiap card.

Contoh:

```text
Wrench → Perintah Kerja
Package → Persediaan
Box → Aset
ScanLine → QR
ClipboardCheck → Inspeksi
Gauge → Meter
ShieldCheck → Kepatuhan
FileCheck → Persetujuan
TriangleAlert → Warning
```

---

# 20. Responsive Data Strategy

Jangan selalu memaksa `<table>` desktop ke mobile.

Gunakan aturan:

### Tetap Table + Horizontal Scroll

Cocok untuk:

- Procurement detail.
- Stock ledger.
- Report tabular dengan banyak angka.

### Ubah ke Card List

Cocok untuk:

- Aset.
- Keluhan.
- Perintah kerja.
- Jadwal teknisi.
- Approval inbox.

Card mobile harus menampilkan 3–5 informasi paling penting, bukan seluruh kolom desktop.

---

# 21. Feedback

## 21.1 Toast

Gunakan Sonner/shadcn untuk:

- Saved.
- Created.
- Updated.
- Deleted.
- Background process queued.

Toast tidak dipakai untuk validation error field.

## 21.2 Loading

- Button spinner untuk aksi.
- Skeleton untuk first-load content.
- Hindari full-page spinner setelah shell sudah tampil.
- Optimistic UI hanya pada aksi yang aman.

## 21.3 Progress

Untuk proses panjang:

```text
Mengunggah 3 dari 5 berkas
Membuat laporan
Menyinkronkan data
```

---

# 22. Destructive Action

Delete/penghapusan harus membedakan:

- Hapus data konfigurasi.
- Arsip/nonaktif.
- Penghapusan aset secara bisnis.
- Hard delete.

Untuk tindakan kritis:

- Dialog menjelaskan dampak.
- Gunakan nama entitas.
- Minta alasan bila diperlukan.
- Jangan gunakan konfirmasi generik "Are you sure?".

Contoh:

```text
Hapus kategori "Pompa"?

Kategori tidak dapat dihapus jika masih digunakan oleh aset.
```

## 22.1 Komponen Konfirmasi

`window.confirm` tidak dipakai. Konfirmasi memakai satu dialog bersama lewat
`useKonfirmasi()`; dialognya berbasis AlertDialog sehingga fokus jatuh ke tombol
batal dan tidak tertutup oleh Esc atau klik di luar.

```ts
const konfirmasi = useKonfirmasi();

if (await konfirmasi({
  judul: `Hapus kategori "${kategori.Nama}"?`,
  deskripsi: 'Kategori yang masih dipakai aset tidak dapat dihapus.',
  ragam: 'bahaya',
})) {
  router.delete(ruteKategoriAset.detail(kategori.Id));
}
```

`deskripsi` wajib menjelaskan dampak. Untuk tindakan yang menuntut alasan,
gunakan opsi `alasan`; untuk penghapusan permanen gunakan
`ketikUntukKonfirmasi` agar pengguna mengetik ulang nama entitas.

Ragam menentukan warna tombol dan ilustrasi:

| Ragam | Dipakai untuk | Ilustrasi |
|---|---|---|
| `bahaya` | Menghapus data | `/assets/3d/hapus.webp` |
| `perhatian` | Membatalkan, mencabut, mengeksekusi, mengunci | `/assets/3d/peringatan.webp` |
| `info` | Keputusan tanpa efek merusak | `/assets/3d/info.webp` |

Ilustrasi 3D pada dialog berukuran 40px dan bersifat penanda, bukan hiasan.
Bila berkasnya gagal dimuat, komponen menggantinya dengan ikon berwarna.

---

# 23. Approval UX

Approval inbox:

```text
Menunggu Persetujuan

[Mutasi Aset]
MT-2026-0012
Workshop A → Workshop B
3 aset

Diajukan oleh Budi
18 Sep 2026 14:22

[Lihat Detail]
```

Detail approval harus menampilkan:

- Apa yang berubah.
- Nilai penting.
- Dokumen.
- Riwayat tahap.
- Approver sebelumnya.
- Tombol `Setujui` dan `Tolak`.

Reject wajib meminta alasan bila business rule mensyaratkan.

---

# 24. Offline UX

Indicator topbar/mobile:

```text
Offline
3 perubahan belum tersinkron
```

State:

- Online.
- Offline.
- Syncing.
- Sync failed.
- Conflict.

Jangan menampilkan sukses sebelum server mengkonfirmasi bila transaksi belum tersinkron.

---

# 25. Scan QR UX

Mobile flow:

```text
[ Scan Aset ]
↓
Camera
↓
Aset ditemukan
↓
Ringkasan
↓
Quick actions
```

Quick action:

```text
Lihat Detail
Buat Keluhan
Mulai Pekerjaan
Inspeksi
```

Jika tidak punya izin, action tidak ditampilkan.

Jika token invalid:

```text
Aset tidak ditemukan
Periksa QR atau masukkan kode aset.
```

---

# 26. Accessibility

- Kontras teks minimum mengikuti WCAG AA sejauh relevan.
- Focus ring terlihat.
- Form memiliki label.
- Icon-only button memiliki accessible name.
- Dialog trap focus.
- Escape menutup dialog non-destructive.
- Keyboard dapat mengoperasikan menu utama.
- Status bukan warna saja.
- Error message terhubung dengan field.
- Touch target utama 44px mobile.

---

# 27. No Dark Mode

Amanpoll versi 1.0 tidak memiliki dark mode.

Larangan:

- Tidak ada toggle tema.
- Tidak ada class `.dark`.
- Tidak ada duplicate dark token.
- Tidak mengikuti `prefers-color-scheme: dark`.
- Screenshot/design QA hanya light mode.

Tujuannya menjaga konsistensi visual dan mengurangi beban maintenance pada tahap produk awal.

---

# 28. shadcn/ui Rules

shadcn digunakan sebagai fondasi, bukan desain final.

Setelah menambah komponen:

- Sesuaikan radius.
- Sesuaikan height.
- Sesuaikan focus ring.
- Sesuaikan color token.
- Hilangkan spacing yang terlalu besar.
- Jangan mempertahankan default visual bila tidak cocok dengan Amanpoll.

Folder:

```text
resources/js/components/
├── ui/
├── layout/
├── forms/
├── data-table/
├── feedback/
└── shared/
```

Feature component tetap di:

```text
resources/js/features/NamaFitur/components/
```

---

# 29. Frontend Naming

Business function TypeScript juga Bahasa Indonesia.

Contoh:

```ts
function formatTanggalLokal()
function formatNilaiUang()
function hitungDurasiKerja()
function dapatMengubahAset()
function kelompokkanPerintahKerja()
```

Framework/library API tidak diterjemahkan.

Contoh tetap:

```ts
useEffect()
useMemo()
map()
filter()
```

Jangan membuat wrapper hanya demi menerjemahkan API JavaScript standar.

---

# 30. Microcopy

Gunakan bahasa pendek dan operasional.

Benar:

```text
Simpan Perubahan
Tugaskan Teknisi
Mulai Pekerjaan
Menunggu Suku Cadang
3 hari lagi
Terlambat 2 jam
```

Hindari:

```text
Silakan klik tombol di bawah ini untuk melanjutkan proses
Data berhasil dilakukan penyimpanan ke dalam sistem
```

Gunakan:

```text
Perubahan disimpan.
```

---

# 31. Tabel dan Angka

- Angka rata kanan.
- Teks rata kiri.
- Nomor dokumen dapat fixed-width/mono.
- Currency menggunakan locale organisasi.
- Tanggal mengikuti format UI yang konsisten.
- Timestamp detail dapat memakai `18 Sep 2026, 14.32`.
- Relative time hanya pelengkap, bukan pengganti timestamp pada audit.

---

# 32. Chart

Gunakan chart hanya bila menjawab pertanyaan.

Rekomendasi:

- Line: tren SLA/cost/downtime.
- Bar: perbandingan unit/vendor.
- Stacked bar: planned vs unplanned.
- Donut hanya untuk 2–5 kategori sederhana.
- Hindari 3D chart.
- Hindari 8 warna dekoratif.
- Tooltip harus menampilkan nilai tepat.

Chart memakai palet teknisi dan semantic colors secara konsisten.

Rincian KPI diformat dengan satuan rinciannya sendiri (`SatuanRincian`), bukan
satuan nilai utama: KPI persen seperti kondisi aset atau kepatuhan SLA merinci
jumlah, sehingga 4 aset tidak boleh terbaca "4%". Label sumbu uang diringkas
(`Rp 1,8 jt`) agar tidak terpotong, sumbu jumlah hanya menampilkan bilangan
bulat, dan nilai enum (`PerluPerhatian`) ditampilkan sebagai kata (`Perlu Perhatian`).

---

# 33. Page-Specific Responsive Rules

## Aset

- Mobile card.
- Quick scan visible.
- Filter di sheet.

## Keluhan

- Mobile list.
- Priority/status prominent.
- Quick create.

## PerintahKerja

- Mobile-first.
- Sticky action.
- Checklist large touch target.

## Persediaan

- Stock summary mobile.
- Ledger horizontal table bila diperlukan.

## Procurement

- Desktop-first table untuk detail.
- Mobile menggunakan stacked line item editor.
- Total sticky pada form panjang.

## Dashboard

- 1 kolom mobile.
- 2 kolom tablet.
- 3–4 kolom desktop sesuai informasi.
- Jangan memaksakan jumlah card sama di semua breakpoint.

---

# 34. Visual QA Checklist

Checklist ini berlaku untuk dasbor web. Halaman Mode Lapangan memakai checklist §36.9: font, gradien hero, dan radius besar di sana memang disengaja. Latar bergradien panel pemasaran halaman autentikasi juga pengecualian yang disengaja (§37).

Sebelum halaman dianggap selesai:

- [ ] Font IBM Plex Sans terpakai.
- [ ] Tidak ada Inter/Jakarta/Roboto/Poppins.
- [ ] Tidak ada dark mode.
- [ ] Tidak ada gradient dekoratif.
- [ ] Radius tidak terlalu bulat.
- [ ] Tidak ada card berlebihan.
- [ ] Primary action jelas.
- [ ] Status mudah dibaca.
- [ ] Loading state ada.
- [ ] Empty state ada.
- [ ] Error state ada.
- [ ] 360px usable.
- [ ] Tablet usable.
- [ ] Desktop usable.
- [ ] Focus state terlihat.
- [ ] Label form ada.
- [ ] Tidak ada horizontal page scroll.
- [ ] Table yang memang lebar memiliki containment scroll sendiri.
- [ ] Destructive action jelas.
- [ ] Mobile touch target cukup besar.

---

# 35. Design Definition of Done

Berlaku untuk dasbor web; Mode Lapangan memakai §36.9.

Sebuah halaman Amanpoll dinyatakan selesai secara desain hanya bila:

1. Menggunakan token Amanpoll.
2. Menggunakan IBM Plex Sans.
3. Tidak menggunakan dark mode.
4. Tidak menggunakan radius berlebihan.
5. Responsive dari mobile sampai wide desktop.
6. Aksi utama mudah ditemukan.
7. Data utama mudah dipindai.
8. Status memiliki teks, bukan hanya warna.
9. Form memiliki validation state.
10. Loading, empty, error, dan permission state sudah dipikirkan.
11. Tidak ada visual placeholder/template yang tidak relevan.
12. Tidak terasa seperti template admin generik.

---

# 36. Mode Lapangan (Teknisi dan Pelapor)

Mode Lapangan adalah tampilan bergaya aplikasi HP untuk dua peran lapangan: **Teknisi**, yang mengerjakan tiket kerja, dan **Pelapor**, yaitu staf lokasi atau unit yang melaporkan kerusakan. Pengguna lapangan murni tidak pernah melihat dasbor web.

Desainnya disetujui pemilik produk pada 24 September 2026, sesudah versi pertama yang bergaya panel admin ditolak karena generik. Bahasa visualnya mengacu pada aplikasi KAI Access, ditambah ikon 3D bergaya clay.

## 36.1 Acuan visual yang mengikat

- `docs/source-of-truth/mockup-mode-lapangan/teknisi.png`: 18 layar.
- `docs/source-of-truth/mockup-mode-lapangan/pelapor.png`: 15 layar.

Kedua papan itu adalah spesifikasi tampilan. Bangun sedekat mungkin dengannya: tata letak, hierarki, komponen, ikon, dan microcopy. Bila papan dan teks bagian ini berbeda, teks bagian ini yang menang.

**Layar 01 "Masuk" di kedua papan tidak dibangun.** Mode Lapangan memakai halaman login bersama (`resources/js/features/Auth/pages/Login.tsx`, tampilannya §37). Setelah login, pengarahan dilakukan di server (PRD 8.20). Jangan membuat halaman login, layar sambutan, atau onboarding khusus Mode Lapangan.

Data di papan (PT Graha Nusantara, Budi, Rina, nomor tiket, aset) hanyalah contoh. Konteks produk tetap multi-industri: jangan menulis istilah khusus satu industri (mis. rumah sakit) di teks antarmuka.

## 36.2 Kerangka layar

- Tanpa sidebar dan tanpa tabel. Satu kolom selebar layar HP; di layar lebar isi dipusatkan dengan lebar maksimum 480px.
- **Hero**: header gradien biru tua dengan dekorasi lingkaran samar. Isinya sapaan dan nama (atau judul halaman), tombol notifikasi bulat, dan chip status (sinkronisasi, lokasi). Hero naik menutupi area status bar.
- **Kartu apung**: kartu putih radius 20px yang menimpa bagian bawah hero, berisi ringkasan dan grid menu ikon 3D.
- **Appbar**: layar alur (detail, formulir, langkah kerja, kamera) memakai header gradien ringkas dengan tombol kembali bulat, judul, dan subjudul. Layar alur tidak memakai navigasi bawah.
- **Navigasi bawah**: putih, radius atas 26px, lima slot, tombol tengah bulat oranye yang menonjol.
  - Teknisi: Beranda · Tugas · **Pindai** · Aset · Akun.
  - Pelapor: Beranda · Laporan · **Lapor** · Aset · Akun.
  - Tab aktif ditandai ikon oranye dan label tebal.
- **Bilah aksi**: aksi utama layar alur menempel di bawah, dalam bilah putih radius atas 24px.
- **Lembar bawah** (bottom sheet) untuk pilihan dan input pendek: minta suku cadang, tambah keterangan, hasil pindai.

## 36.3 Komponen khas

- **Tiket**: kartu dengan sobekan berlekuk di kedua sisi dan garis putus-putus. Dipakai untuk tiket kerja, laporan keluhan, dan bukti selesai.
- **Rute jam**: dua jam besar dan tebal di kiri-kanan, dihubungkan garis titik dengan ikon 3D di tengah. Contoh: "09.00 Dilaporkan ··⏱ sisa 1j 49m·· 11.30 Target SLA", atau "09.05 Mulai ··42 mnt·· 09.47 Selesai". Dipakai di beranda, daftar, detail, ringkasan, dan layar selesai.
- **Perhentian (stasiun)**:
  - Langkah pengerjaan teknisi: Checklist • Diagnosis • Suku cadang • Foto • Selesai. Perhentian yang selesai oranye bertanda centang.
  - Lacak laporan pelapor: Dilaporkan → Ditinjau → Ditugaskan → Dikerjakan → Selesai. Perhentian saat ini bercincin oranye dan bertanda "Sekarang".
  - Jadwal hari ini di beranda teknisi.
- **Chip status**: pil berwarna lembut dengan teks warna -700, selalu bertuliskan statusnya. Pemetaannya:
  - Kritis / Terlambat: merah.
  - Tinggi: oranye.
  - Menunggu…: kuning.
  - Ditugaskan / Dikerjakan / Diproses: biru.
  - Selesai / Ditutup: hijau.
  - Lainnya: abu.
- **Tab pil**: segmen dengan jumlah (Hari ini 5 · Terlambat 1 · Selesai), bukan tab bergaris.
- **Isian bergaya formulir tiket**: label kecil di dalam kotak isian putih radius 16px.
- **Banner info**: kartu gradien (oranye, biru, atau hijau) dengan ikon 3D besar di kanan. Gradien oranye banner memakai `#A8470A → #C2530A` agar teks putih lolos AA di seluruh bidang.
- **Ilustrasi momen**: layar sukses dan kosong memakai ikon 3D besar (±104px) di dalam lingkaran lembut.

## 36.4 Warna dan tipografi

Token dipasang di `resources/css/app.css` dengan awalan `lapangan-` supaya tidak bercampur dengan palet dasbor. `WarnaPaletTerdefinisiTest` diperluas agar ikut memeriksa awalan ini.

| Token | Nilai | Pemakaian |
|---|---|---|
| `lapangan-navy-900` | `#0B2239` | Gradien hero (awal), teks judul papan |
| `lapangan-navy-800` | `#12324F` | Tab aktif, tombol navy, chip terpilih |
| `lapangan-biru-600` | `#1F5F8B` | Gradien hero (akhir), teks tautan biru |
| `lapangan-biru-500` | `#2A7BB0` | Sorotan gradien, fokus isian |
| `lapangan-biru-50` | `#EAF3F9` | Tint wadah ikon, chip biru |
| `lapangan-oranye-700` | `#C2530A` | **Tombol aksi utama**; teks putih 4,6:1 |
| `lapangan-oranye-600` | `#E8650C` | Aksen non-teks: ikon aktif, titik, garis perhentian |
| `lapangan-oranye-teks` | `#A8470A` | Teks oranye di atas putih/tint (≥ 4,5:1) |
| `lapangan-oranye-50` | `#FFF1E6` | Tint oranye |
| `lapangan-oranye-100` | `#FFE2CC` | Cincin/sorotan oranye lembut |
| `lapangan-ungu-50` | `#F1ECFB` | Tint wadah ikon ungu |
| `lapangan-hijau-700` / `-50` | `#0E7A4F` / `#E7F6EF` | Status selesai |
| `lapangan-merah-700` / `-50` | `#B3261E` / `#FDECEA` | Kritis, terlambat, keluar |
| `lapangan-kuning-700` / `-50` | `#8A5A00` / `#FFF6DC` | Menunggu |
| `lapangan-teks` | `#0F1B26` | Teks utama |
| `lapangan-teks-2` | `#4A5866` | Teks sekunder |
| `lapangan-teks-3` | `#5B6773` | Metadata; 5,3:1 di atas latar (jangan lebih muda) |
| `lapangan-garis` | `#E3E8EE` | Garis dan bingkai isian |
| `lapangan-garis-2` | `#EEF1F5` | Pemisah baris di dalam kartu |
| `lapangan-latar` | `#F2F5F8` | Latar layar |
| `lapangan-oranye-200` | `#FFC58F` | Baris aksen judul hero halaman autentikasi (§37), hanya di atas bagian gelap gradien |
| `lapangan-biru-100` | `#CFE2EF` | Paragraf hero halaman autentikasi (§37), hanya di atas bagian gelap gradien |

- Gradien hero: `radial-gradient(120% 90% at 100% 0%, #2A7BB0, transparent 55%), linear-gradient(160deg, #0B2239, #12324F 45%, #1F5F8B)`.
- Font Mode Lapangan: **Plus Jakarta Sans** (400–800), paket `@fontsource/plus-jakarta-sans`, disetujui pemilik produk pada 24 September 2026. Font dipasang lewat atribut `data-tampilan="lapangan"` pada `<html>` selama `KerangkaLapangan` terpasang, sehingga konten portal (lembar bawah, dialog, toast) ikut memakainya. Halaman autentikasi memakai font yang sama lewat `data-tampilan="autentikasi"` (§37, disetujui pada tanggal yang sama bersama arah desain 2). Dasbor web tetap IBM Plex Sans.
- Angka dan jam penting besar dan tebal (22–24px, 800) dengan `tabular-nums`. Teks isi 15px. Minimum 12px.

**Bayangan** (diputuskan pemilik produk, 24 September 2026): Mode Lapangan memakai bayangan setipis mungkin. Kartu dan tiket dibedakan dari latar lewat warna putih di atas latar abu dan garis tipis, bukan bayangan tebal.

| Token | Nilai | Dipakai untuk |
| --- | --- | --- |
| `shadow-lapangan-kartu` | `0 1px 2px` 5% | Kartu dan tiket |
| `shadow-lapangan-apung` | `0 2px 8px` 6% | Kartu yang mengapung di atas hero, lingkaran ilustrasi |
| `shadow-lapangan-bilah` | garis `0 -1px 0` 6% | Nav bawah dan bilah aksi |
| `shadow-lapangan-oranye` | `0 1px 2px` 16% | Tombol oranye |
| `shadow-lapangan-fab` | `0 3px 8px` 22% | Tombol tengah nav bawah (satu-satunya yang boleh sedikit terangkat) |

Jangan menambah bayangan berblur besar (mis. `0 20px 40px`) atau `shadow-lg` di layar Mode Lapangan. Cincin fokus dan halo status (`0 0 0 Npx`) bukan bayangan dan tetap boleh.

## 36.5 Ikon

- **Ikon 3D clay**: Microsoft Fluent Emoji 3D (lisensi MIT). Berkas PNG 256px disimpan sebagai aset statis di `public/images/3d/<nama>.png`, beserta berkas lisensinya. Hanya salin ikon yang benar-benar dipakai; ini bukan paket npm. Komponennya (`Ikon3D`, `WadahIkon3D`, daftar `NAMA_IKON_3D`) ada di `resources/js/components/shared/Ikon3D.tsx` karena dipakai Mode Lapangan dan halaman autentikasi; `Ikon3DTersediaTest` memastikan daftar nama dan berkas di folder itu cocok. Ukuran tampil paling besar ±256px (ukuran berkasnya) agar tidak buram.
  - Dipakai untuk: grid menu, kepala kartu penting, ilustrasi jenis aset, kategori keluhan, banner, layar sukses/kosong, dan baris menu Akun.
  - Selalu di atas wadah tint radius 16–18px, atau berdiri bebas sebagai ilustrasi.
- **Lucide** tetap dipakai untuk UI kecil: navigasi bawah, panah, tombol ikon, status bar, dan isi chip.
- Tidak ada emoji teks di antarmuka.

Pemetaan ikon 3D (nama berkas Fluent) yang dipakai papan acuan:

| Makna | Ikon 3D |
|---|---|
| Tiket Saya / checklist | `clipboard`, `memo` |
| Pindai aset | `magnifying_glass_tilted_left`, `camera`, `camera_with_flash` |
| Preventif / jadwal | `spiral_calendar`, `alarm_clock` |
| Suku cadang / gudang | `nut_and_bolt`, `package` |
| Riwayat | `card_index_dividers`, `bookmark_tabs` |
| Keluhan / lapor | `megaphone` |
| Bantuan | `headphone`, `telephone_receiver` |
| Alat kerja / merek Mode Lapangan | `hammer_and_wrench`, `wrench`, `toolbox`, `gear` |
| SLA / durasi | `stopwatch`, `hourglass_done` |
| Selesai / berhasil | `party_popper`, `trophy`, `check_mark_button`, `star`, `sparkles` |
| Konfirmasi ya / tidak | `thumbs_up`, `cross_mark` |
| Offline / sinkron | `satellite_antenna`, `cloud`, `mobile_phone` |
| Lokasi | `round_pushpin`, `office_building`, `door` |
| Peringatan | `warning`, `construction`, `police_car_light` |
| Kategori: Listrik, AC & Udara, Air & Pipa, Lift, IT & Printer, Bangunan, Keamanan, Lainnya | `high_voltage`, `snowflake`, `droplet`, `elevator`, `desktop_computer`/`printer`, `brick`, `video_camera`, `toolbox` |
| Jenis aset (contoh) | genset `battery`, lift `elevator`, AC `snowflake`, pompa `droplet`, forklift `articulated_lorry`, CCTV `video_camera`, panel `electric_plug`, APAR `fire_extinguisher`, lampu `light_bulb` |
| Akun / notifikasi | `bell`, `shield`, `man_mechanic`, `waving_hand`, `handshake` |
| Halaman autentikasi (§37) | sapaan `waving_hand`, lupa `key`, reset `locked_with_key`, pilih organisasi `office_building`, daftar trial `rocket`; jenis tempat `factory`, `office_building`, `package`, `school`, `hotel`, `hospital` |

Ikon jenis aset dipilih dari kategori aset. Kategori tanpa padanan memakai `toolbox`.

## 36.6 Layar Teknisi (papan `teknisi.png`)

1. ~~Masuk~~: tidak dibangun, pakai login yang ada.
2. Menyiapkan Mode Lapangan: paket offline diunduh (persentase, tiket, aset, checklist).
3. Beranda: hero + chip sinkron. Kartu apung "Jadwal hari ini" dengan perhentian jam, lalu grid menu 4 ikon (Tiket Saya, Pindai Aset, Suku Cadang, Riwayat). Di bawahnya tiket "Kerjakan sekarang" dengan tombol Mulai, dan banner info.
4. Notifikasi: tab Semua/Tiket/Info, ikon 3D per jenis, penanda belum dibaca.
5. Tiket Saya: tab pil Hari ini/Terlambat/Selesai. Tiket dengan rute jam dan ikon aset.
6. Detail tiket: tiket besar (prioritas, status, rute SLA), kartu aset, asal keluhan beserta kutipannya, checklist, lokasi, riwayat aset. Bilah aksi: Alihkan | **Terima & Mulai**.
7. Checklist: perhentian langkah + timer di appbar. Pilihan Sesuai/Tidak sesuai berukuran besar, isian ukur dengan rentang normal. Bilah aksi: Jeda | Simpan & lanjut.
8. Diagnosis & tindakan: chip kode kegagalan dengan ikon 3D, isian penyebab/tindakan, tombol dikte.
9. Minta suku cadang (lembar bawah): stok per gudang, jumlah, gudang ambil. **Teknisi hanya meminta; stok berkurang saat gudang menyerahkan barang.**
10. Foto sebelum/sesudah: foto tersimpan di HP saat offline (lencana "Di HP").
11. Ringkasan & tanda tangan: rute Mulai → Selesai, kondisi aset, tanda tangan penerima (opsional; wajib bila organisasi menyalakan setelannya, PRD 8.20).
12. Selesai: ilustrasi 3D, bukti tiket, tugas berikutnya, "Kembali ke Beranda".
13. Kamera pindai: layar penuh gelap, bingkai pindai, senter, "Ketik kode aset".
14. Aset ditemukan (lembar bawah di atas kamera): aset + kondisi, tiket terbuka, aksi cepat (Mulai kerja, Inspeksi, Lapor, Riwayat).
15. Riwayat aset: ringkasan angka + garis waktu pekerjaan.
16. Beranda saat offline: chip "Offline · N menunggu dikirim" dan kartu kuning menuju antrian. Semua tetap bisa dipakai.
17. Akun & sinkronisasi: profil, antrian perubahan, konflik, data offline, bantuan, Keluar.
18. Konflik: dua kartu versi (perangkat vs server) lengkap dengan siapa dan kapan, bidang yang berbeda, pilih satu. Versi yang tidak dipilih tetap tercatat di riwayat.

## 36.7 Layar Pelapor (papan `pelapor.png`)

1. ~~Masuk~~: tidak dibangun, pakai login yang ada.
2. Beranda: "Halo, <nama>" + lokasi. Kartu apung dengan aksi besar "Laporkan Kerusakan" dan grid 8 kategori ikon 3D, lalu tiket "Laporan aktif" (yang menunggu konfirmasi disorot dan diberi tombol Konfirmasi).
3. Notifikasi: kabar status dalam bahasa sehari-hari, dengan tombol aksi bila perlu.
4. Pilih alat (langkah 1/3, indikator langkah di appbar): kartu "Pindai QR alat", isian lokasi dan cari alat, daftar aset di lokasi, jalan keluar "Tidak tahu alatnya? Laporkan lokasi saja".
5. Alat ditemukan: aset terisi dari QR. **Cegah laporan ganda**: bila alat sudah punya laporan terbuka, tawarkan "Pantau laporan itu" atau "Tetap lapor".
6. Apa masalahnya (2/3): kategori, pilihan cepat masalah, cerita singkat + tombol mikrofon, tingkat urgensi dalam bahasa awam (Tidak buru-buru / Mengganggu kerja / Kerja terhenti / Berbahaya), foto.
7. Tinjau & kirim (3/3): ringkasan bergaya tiket dengan tombol "Ubah" per bagian, kontak, sakelar notifikasi.
8. Laporan terkirim: nomor laporan ditulis besar seperti kode booking, rute jam kirim → target ditinjau, "apa selanjutnya", catatan kirim otomatis saat offline.
9. Laporan Saya: tab pil Aktif/Perlu konfirmasi/Selesai. Tiket dengan jejak kemajuan.
10. Lacak laporan: tiket + rute jam, perhentian perjalanan laporan, kartu teknisi dengan tombol telepon.
11. Tambah keterangan (lembar bawah): pilihan cepat, catatan, foto.
12. Konfirmasi selesai: apa yang dikerjakan (foto sebelum/sesudah), dua pilihan besar (jempol/silang), bintang 1–5, komentar.
13. Terima kasih: ilustrasi 3D, ringkasan perjalanan laporan.
14. Aset di lokasi: kondisi, penanda laporan terbuka, tombol Lapor per aset, banner info gedung.
15. Akun: profil + angka ringkas, notifikasi, nomor ekstensi, panduan, pasang aplikasi, Keluar.

## 36.8 Larangan: agar tidak kembali ke gaya panel admin

- Tidak ada tabel, DataTable, sidebar, atau breadcrumb.
- Tidak ada label kecil HURUF KAPITAL berderet dan tidak ada kartu bergaris tepi kiri berwarna.
- Tidak ada deretan lencana. Satu hal besar per kartu.
- Tidak ada grafik KPI.
- Tidak ada komponen dasbor yang ditempel apa adanya. Komponen `components/ui` boleh dipakai sebagai dasar perilaku (dialog, sheet, input), tetapi tampilannya mengikuti §36.

## 36.9 Checklist Mode Lapangan

- [ ] Sesuai papan acuan: tata letak, komponen, dan ikon.
- [ ] Hero atau appbar gradien, kartu apung, tiket dengan rute jam dipakai sesuai §36.3.
- [ ] Aksi utama memakai tombol `lapangan-oranye-700` (teks putih) dan menempel di bawah pada layar alur.
- [ ] Kontras AA; metadata tidak lebih muda dari `lapangan-teks-3`; teks minimum 12px.
- [ ] Target sentuh ≥ 44px, tombol utama 52px.
- [ ] Status selalu bertuliskan teks.
- [ ] Berfungsi offline sesuai PRD 8.17 dan menampilkan status sinkron.
- [ ] Keadaan kosong, memuat, galat, dan tanpa izin ada (memakai ilustrasi 3D).
- [ ] Nyaman di lebar 360px; di layar lebar isi dipusatkan maksimum 480px.
- [ ] Tidak ada dark mode.

---

# 37. Halaman Autentikasi

Masuk, Lupa kata sandi, Pilih organisasi, Reset kata sandi, dan Daftar trial dilihat publik, jadi halaman ini juga membawa pesan produk. Kelimanya memakai satu kerangka, `features/Auth/components/KerangkaAutentikasi.tsx`. Fungsinya sama dengan halaman lain: tidak ada halaman login khusus Mode Lapangan (PRD 8.20).

Tampilannya mengikuti **arah desain 2** (biru merek bergradien, bahasa Mode Lapangan) yang dipilih pemilik produk pada 24 September 2026 dari tiga mockup, menggantikan versi dua kolom dengan panel pemasaran gelap. Login jadi satu keluarga visual dengan aplikasi teknisi: hero gradien, kartu mengapung, tiket bergaya karcis, ikon 3D, dan Plus Jakarta Sans.

## 37.1 Acuan visual dan tata letak

- Papan acuan: mockup arah 2 (desktop 1440 × 900 dan HP 390 × 844). Bangun sedekat mungkin dengannya; bila papan dan teks bagian ini berbeda, teks ini yang menang.
- **Hero**: gradien biru (`gradien-hero-autentikasi`: Navy-900 → Navy-800 → Biru-600, sorotan Biru-500 di kanan atas) menempel di atas halaman. Desktop setinggi 560px (580px di 1024–1279px) dengan sudut kanan bawah melengkung 140px, cincin samar, dan jalur putus-putus. HP setinggi 310px (392px mulai 640px) dengan kedua sudut bawah melengkung 36px, satu cincin, dan bulatan oranye lembut.
- **Desktop (≥ 1024px)**, isi selebar paling banyak 1248px: kiri berisi logo, judul "Tiap aset punya jadwal. / Tiap keluhan punya tenggat." (baris kedua Oranye-200), paragraf pendek, lalu ilustrasi yang menembus batas hero. Kanan berisi kartu formulir 440px (400px di bawah 1280px; Daftar trial 480px/440px) yang mengapung menutupi batas hero, dan footer kecil.
- **Ilustrasi desktop**: teknisi 3D (`man_mechanic`, 230px) di balik tiket contoh bergaya karcis (sobekan, rute jam 08.05 Dilaporkan → 10.30 Target SLA, teknisi di lokasi), kilau (`sparkles`), serta kotak perkakas dan palu-kunci di tepi hero. Tiket selalu menimpa batas hero karena kotaknya menempel di dasar area hero. Kotak perkakas diperkecil lalu disembunyikan saat kolom kiri lebih sempit dari 640px.
- **Deretan jenis tempat** "Untuk merawat aset di": Pabrik, Gedung, Gudang, Kampus, Hotel, Klinik, masing-masing ikon 3D di wadah putih 64px. Ini jenis tempat (multi-industri, PRD 1, 3.1), bukan logo atau nama pelanggan. Tampil mulai 640px; di HP disembunyikan agar formulir tetap di layar pertama.
- **HP dan tablet (< 1024px)**: hero ringkas dengan logo, judul (tanpa paragraf), dan teknisi 3D di kanan; kartu formulir menimpa batas hero. Tombol utama Masuk harus terlihat di layar pertama pada 390 × 844 dan 360 × 740.
- Tidak ada gulir horizontal di 360px, 390px, 768px, 1024px, 1280px, maupun 1440px.

## 37.2 Kartu formulir

- Kartu putih radius 26px (24px di HP), bayangan `shadow-lapangan-formulir`, padding 36/40/32px (22/20/18px di HP).
- Kepala kartu: ikon 3D di wadah Oranye-50 56px (48px di HP) lalu judul (h1, 26px/800, Navy-900) dan subjudul 15px. Per halaman:
  - Masuk: `waving_hand`, sapaan "Selamat pagi/siang/sore/malam" menurut jam lokal peramban (`salamWaktu` di `lib/waktu.ts`), subjudul "Masuk untuk melihat pekerjaan hari ini.".
  - Lupa kata sandi: `key`. Reset kata sandi: `locked_with_key`. Pilih organisasi: `office_building`. Daftar trial: `rocket`.
- Isian 52px, radius 14px, bingkai 1,5px `lapangan-garis`, latar `lapangan-latar/50`, ikon Lucide kecil di kiri; fokus berbingkai Biru-500 dengan cincin lembut. Teks 16px.
- Tombol utama 54px `lapangan-oranye-700` (teks putih 4,6:1), radius 14px. Tautan memakai `lapangan-oranye-teks`. Kotak centang 22px, tercentang Navy-800.
- Label di atas isian; galat (Merah-700) di bawah isian dan terhubung lewat `aria-describedby`; bantuan disembunyikan bila galat tampil.
- Kata sandi punya tombol tampilkan/sembunyikan 44px yang dapat difokus dan bernama ("Tampilkan kata sandi").
- Tombol utama menampilkan keadaan memuat (ikon berputar + kata kerja, mis. "Memeriksa...").
- `autocomplete`: `email`, `current-password` (Masuk), `new-password` (Reset, Daftar). Id isian tetap (`email`, `kata-sandi`, dst.).
- Pesan sukses flash (sesudah reset atau pendaftaran) tampil di atas formulir dengan `role="status"`.
- Target sentuh ≥ 44px untuk tautan berdiri sendiri ("Lupa kata sandi?", "Kembali ke halaman masuk") dan baris "Ingat saya".

## 37.3 Isi dan ajakan trial

Seluruh salinan hero, tiket contoh, dan jenis tempat ada di `features/Auth/isi.ts`, tidak ditulis di komponen.

- Ajakan "Belum punya akun? Coba gratis N hari" ke halaman Daftar trial. N diambil dari server (`amanpoll.langganan.hari_uji_coba`, lewat `LayananKebijakanTenggang::hariUjiCoba()`, prop `durasiTrialHari`), bukan ditulis di frontend. Ajakan hanya tampil di Masuk dan Lupa kata sandi, dan hanya bila N > 0 (Masuk menampilkan "Daftar" bila N = 0). Pilih organisasi, Reset kata sandi, dan Daftar trial tidak menampilkannya karena penggunanya sudah punya akun atau sedang mendaftar.
- Tiket di ilustrasi berisi data rekaan dan berlabel kecil "Contoh" di sebelah nomornya. Seluruh ilustrasi `aria-hidden`.
- Footer: "© <tahun> Amanpoll · Manajemen aset dan pemeliharaan". Jangan menaruh tautan ke halaman yang belum ada (mis. kebijakan privasi, bantuan publik).

## 37.4 Larangan bukti sosial palsu

Tidak ada testimoni, logo pelanggan, jumlah pengguna, rating, atau klaim angka (mis. "hemat 40%") kecuali sudah disetujui dan sumbernya tercatat di repo, misalnya blok CMS yang diterbitkan. Angka di tiket contoh adalah contoh tampilan, bukan klaim, dan harus tetap berlabel begitu. Deretan jenis tempat hanya boleh berisi jenis, bukan nama organisasi.

## 37.5 Warna dan tipografi

- Warna hanya token `lapangan-*` (§36.4), ditambah `lapangan-oranye-200` (`#FFC58F`, baris aksen judul hero) dan `lapangan-biru-100` (`#CFE2EF`, paragraf hero). Keduanya hanya di atas bagian gelap gradien: diukur dari tangkapan layar, latar di belakang teks hero paling terang `rgb(34 72 104)`, jadi Oranye-200 ≥ 6,2:1, Biru-100 ≥ 7,1:1, putih ≥ 9,5:1.
- Font **Plus Jakarta Sans**, disetujui pemilik produk pada 24 September 2026 bersama arah desain 2. `KerangkaAutentikasi` memasang `data-tampilan="autentikasi"` pada `<html>` selama terpasang dan mencabutnya saat pindah halaman; aturan base di `app.css` memetakannya ke `--font-lapangan`. Dasbor tetap IBM Plex Sans.
- Tanpa kelas `dark:`.
