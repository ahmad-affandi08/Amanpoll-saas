/**
 * Tangkapan layar berpenanda untuk panduan pengguna (/dokumentasi).
 *
 * Skrip membuka aplikasi demo (organisasi PT Sinar Nusantara Industri dari
 * DemoAwalSeeder), masuk sebagai peran yang tepat, memotret layar, lalu mencari
 * tombol atau menu yang ditunjuk panduan lewat namanya dan mencatat kotaknya:
 *
 *   public/assets/dokumentasi/<nama>.webp                     gambar
 *   resources/js/features/Dokumentasi/tangkapan/<nama>.json   posisi penanda (persen)
 *
 * Komponen `Tangkapan` menggambar nomor di atas gambar dari berkas JSON itu, jadi
 * setelah tampilan berubah cukup jalankan ulang skrip ini; halaman panduan tidak
 * perlu disunting.
 *
 * Pemakaian (server demo sudah berjalan di atas basis data hasil
 * `php artisan migrate:fresh --seed`):
 *
 *   TANGKAP_URL=http://localhost:8027 node tools/dokumentasi/tangkap.mjs [saringan-nama]
 *
 * Playwright tidak menjadi dependensi proyek; arahkan TANGKAP_PLAYWRIGHT ke
 * paket yang terpasang bila bukan `playwright` global.
 */
import { createRequire } from 'node:module';
import { existsSync, mkdirSync, readdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.TANGKAP_PLAYWRIGHT ?? 'playwright');

const PANGKAL = process.env.TANGKAP_URL ?? 'http://localhost:8027';
const SANDI = process.env.TANGKAP_SANDI ?? 'password';
const DIR_GAMBAR = 'public/assets/dokumentasi';
const DIR_DATA = 'resources/js/features/Dokumentasi/tangkapan';
const LEBAR = 1280;
/** Layar ponsel untuk Mode Lapangan; dipotret dua kali lebih rapat supaya tetap tajam. */
const LAYAR_HP = { width: 390, height: 844 };
const TINGGI = 800;
const KUALITAS_WEBP = 0.82;
/** Kotak penanda sedikit lebih lega dari elemennya supaya garis sorot tidak menutupi teks. */
const RUANG_PENANDA = 4;

function cariPeramban() {
    const akar = process.env.PLAYWRIGHT_BROWSERS_PATH ?? '/opt/pw-browsers';
    if (!existsSync(akar)) return undefined;
    for (const d of readdirSync(akar)) {
        const p = join(akar, d, 'chrome-linux', 'chrome');
        if (/^chromium-\d+$/.test(d) && existsSync(p)) return p;
    }
    return undefined;
}

// ---------------------------------------------------------------------------
// Pembantu pencari elemen. Semuanya mengambil elemen pertama yang terlihat.
// ---------------------------------------------------------------------------

const terlihat = (loc) => loc.locator('visible=true').first();
/** Submenu sidebar, mis. "Perintah Kerja" di bawah Pemeliharaan. */
const menu = (label) => (p) =>
    terlihat(
        p
            .locator('[data-sidebar="menu-sub-button"], [data-sidebar="menu-button"]')
            .filter({ hasText: new RegExp(`^\\s*${label}\\s*$`) }),
    );
const tombol = (nama) => (p) => terlihat(p.getByRole('button', { name: nama, exact: true }));
const tautan = (nama) => (p) => terlihat(p.getByRole('link', { name: nama, exact: true }));
/** Bidang isian di dialog, ditemukan dari teks labelnya; kotaknya mencakup label dan isiannya. */
const bidang = (label) => (p) =>
    terlihat(p.locator('[role=dialog] label').filter({ hasText: label }).locator('xpath=..'));
/** Bidang isian di isi halaman (bukan dialog), ditemukan dari teks labelnya. */
const bidangHalaman = (label) => (p) =>
    terlihat(p.locator('main label').filter({ hasText: label }).locator('xpath=..'));
/** Label di dialog itu sendiri, untuk kotak centang yang berbagi induk dengan bidang lain. */
const labelDialog = (teks) => (p) => terlihat(p.locator('[role=dialog] label').filter({ hasText: teks }));
const dialog = (p) => terlihat(p.locator('[role=dialog]'));
/** Tombol, tautan, atau pemilih (combobox) di isi halaman yang teksnya persis. */
const kendali = (teks) => (p) =>
    terlihat(
        p
            .locator('main button, main a, main [role=combobox]')
            .filter({ hasText: new RegExp(`^\\s*${teks}\\s*$`) }),
    );
const kepalaKolom = (teks) => (p) => terlihat(p.locator('main th').filter({ hasText: teks }));

async function klik(p, pencari) {
    await pencari(p).click();
    await p.waitForTimeout(700);
}

async function bukaPertama(p, pola) {
    await terlihat(p.locator('main a').filter({ hasText: pola })).click();
    await p.waitForLoadState('networkidle');
    await p.waitForTimeout(800);
}

// ---------------------------------------------------------------------------
// Daftar tangkapan. `penanda` memetakan kunci yang dipakai halaman panduan ke
// pencari elemennya; `potong` membatasi gambar ke satu bagian layar; `tinggi`
// meninggikan layar untuk dialog panjang agar tombol simpannya ikut terfoto.
// ---------------------------------------------------------------------------

const DAFTAR = [
    // Keluhan
    {
        nama: 'keluhan/daftar',
        masuk: 'admin',
        buka: '/pemeliharaan/keluhan',
        penanda: { menu: menu('Keluhan'), buat: tombol('Buat Keluhan'), saring: kendali('Semua status') },
    },
    {
        nama: 'keluhan/formulir',
        masuk: 'admin',
        buka: '/pemeliharaan/keluhan',
        siapkan: (p) => klik(p, tombol('Buat Keluhan')),
        potong: dialog,
        penanda: {
            kategori: bidang('Kategori'),
            lokasi: bidang('Lokasi'),
            judul: bidang('Judul'),
            kirim: tombol('Kirim Keluhan'),
        },
    },
    {
        nama: 'keluhan/detail',
        masuk: 'admin',
        buka: '/pemeliharaan/keluhan',
        siapkan: (p) => bukaPertama(p, /KLH\//),
        penanda: {
            status: tombol('Ubah Status'),
            alihkan: tombol('Alihkan'),
            prioritas: tombol('Ubah Prioritas'),
        },
    },

    // Perintah kerja
    {
        nama: 'perintah-kerja/daftar',
        masuk: 'admin',
        buka: '/pemeliharaan/perintah-kerja',
        // Kolom Buka terdorong keluar layar oleh tabel yang lebar; nomornya membuka halaman yang sama.
        penanda: {
            menu: menu('Perintah Kerja'),
            buat: tombol('Buat Perintah Kerja'),
            buka: (p) => terlihat(p.locator('main a').filter({ hasText: /PK\// })),
        },
    },
    {
        nama: 'perintah-kerja/formulir',
        masuk: 'admin',
        buka: '/pemeliharaan/perintah-kerja',
        siapkan: (p) => klik(p, tombol('Buat Perintah Kerja')),
        potong: dialog,
        tinggi: 1150,
        penanda: {
            jenis: bidang('Jenis Pekerjaan'),
            prioritas: bidang('Prioritas'),
            simpan: tombol('Simpan Perintah Kerja'),
        },
    },
    {
        nama: 'perintah-kerja/detail',
        masuk: 'admin',
        buka: '/pemeliharaan/perintah-kerja',
        siapkan: (p) => bukaPertama(p, /PK\//),
        penanda: {
            tugaskan: tombol('Tugaskan Teknisi'),
            mulai: tombol('Mulai Kerja'),
            suku: tombol('Reservasi Suku Cadang'),
        },
    },

    // Preventif
    {
        nama: 'preventif/templat',
        masuk: 'admin',
        buka: '/preventif-inspeksi/templat-daftar-periksa',
        siapkan: (p) => klik(p, tautan('Buka Builder')),
        penanda: { tambah: tombol('Tambah Pertanyaan'), versi: tombol('Buat Versi Baru') },
    },
    {
        nama: 'preventif/rencana-baru',
        masuk: 'admin',
        buka: '/preventif-inspeksi/rencana-pemeliharaan',
        siapkan: async (p) => {
            await klik(p, tombol('Buat Rencana Baru'));
            await p.locator('#StrategiJadwal').click();
            await p.getByRole('option', { name: /mana lebih dulu/ }).click();
            await p.fill('#Nama', 'Servis Kompresor 2.000 Jam');
            await p.fill('#AmbangMeter', '2000');
            await p.waitForTimeout(300);
        },
        potong: dialog,
        penanda: {
            pemicu: bidang('Pemicu'),
            interval: bidang('Interval'),
            ambang: bidang('Setiap pemakaian meter'),
        },
    },
    {
        nama: 'preventif/rencana-detail',
        masuk: 'admin',
        buka: '/preventif-inspeksi/rencana-pemeliharaan',
        siapkan: async (p) => {
            // Kartu terdalam yang memuat kode rencana dan tautannya sekaligus.
            const kartu = p
                .locator('main div')
                .filter({ has: p.getByText('PM-KMP-01', { exact: true }) })
                .filter({ has: p.getByRole('link', { name: 'Kelola Aset & Jadwal' }) });
            await kartu.last().getByRole('link', { name: 'Kelola Aset & Jadwal' }).click();
            await p.waitForLoadState('networkidle');
            await p.waitForTimeout(800);
        },
        penanda: {
            daftarkan: tombol('Daftarkan Aset ke Rencana'),
            jatuhTempo: (p) => terlihat(p.locator('main td').filter({ hasText: /≥/ })),
        },
    },

    // Persediaan
    {
        nama: 'persediaan/suku-cadang',
        masuk: 'admin',
        buka: '/suku-cadang',
        penanda: { menu: menu('Suku Cadang'), tambah: tombol('Tambah Suku Cadang') },
    },
    {
        nama: 'persediaan/stok',
        masuk: 'admin',
        buka: '/stok-suku-cadang',
        penanda: { gudang: kendali('Semua Gudang'), tersedia: kepalaKolom('Tersedia Bersih') },
    },

    // Mutasi stok
    {
        nama: 'mutasi-stok/formulir',
        masuk: 'gudang',
        buka: '/mutasi-stok',
        siapkan: (p) => klik(p, tombol('Buat Mutasi Stok')),
        potong: dialog,
        penanda: { jenis: bidang('Jenis'), gudang: bidang('Gudang Tujuan'), draft: tombol('Buat Draft') },
    },
    {
        nama: 'mutasi-stok/detail',
        masuk: 'admin',
        buka: '/mutasi-stok',
        siapkan: (p) => bukaPertama(p, /MS\//),
        penanda: { baris: tombol('Tambah Baris'), posting: tombol('Posting') },
    },

    // Pengadaan
    {
        nama: 'pengadaan/permintaan',
        masuk: 'admin',
        buka: '/perencanaan-pengadaan/permintaan-pembelian',
        siapkan: (p) => bukaPertama(p, /PP\/\d{4}\/0034/),
        penanda: { item: tombol('Tambah Item'), ajukan: tombol('Ajukan Persetujuan') },
    },
    {
        nama: 'pengadaan/menunggu-penerimaan',
        masuk: 'gudang',
        buka: '/perencanaan-pengadaan/penerimaan-pembelian',
        penanda: {
            menu: menu('Penerimaan Pembelian'),
            menunggu: (p) => terlihat(p.locator('section[aria-labelledby="judul-menunggu"]')),
        },
    },
    {
        nama: 'pengadaan/catat-penerimaan',
        masuk: 'gudang',
        buka: '/perencanaan-pengadaan/penerimaan-pembelian',
        siapkan: async (p) => {
            await terlihat(p.locator('section[aria-labelledby="judul-menunggu"] a')).click();
            await p.waitForLoadState('networkidle');
            await p.waitForTimeout(800);
        },
        penanda: {
            catat: tombol('Catat Penerimaan'),
            item: (p) => terlihat(p.locator('main').getByText('Item Pesanan', { exact: true })),
        },
    },

    // Persetujuan
    {
        nama: 'persetujuan/tahap',
        masuk: 'admin',
        buka: '/persetujuan/alur',
        siapkan: async (p) => {
            await terlihat(
                p
                    .locator('tr')
                    .filter({ hasText: 'Persetujuan Permintaan Pembelian' })
                    .getByRole('button', { name: 'Kelola Tahap' }),
            ).click();
            await p.waitForTimeout(800);
        },
        potong: dialog,
        penanda: {
            tahap: (p) => terlihat(p.locator('[role=dialog] div').filter({ hasText: /^2\. / })),
            ambang: (p) => terlihat(p.locator('[role=dialog]').getByText(/nilai ≥/)),
        },
    },
    {
        nama: 'persetujuan/inbox',
        masuk: 'penyetuju',
        buka: '/persetujuan/permintaan',
        penanda: { menu: menu('Persetujuan Saya'), setujui: tombol('Setujui'), tolak: tombol('Tolak') },
    },
    // Organisasi & lokasi
    {
        nama: 'organisasi/profil',
        masuk: 'admin',
        buka: '/platform/organisasi',
        penanda: {
            zona: bidangHalaman('Zona Waktu'),
            simpan: tombol('Simpan Perubahan'),
        },
    },
    {
        nama: 'organisasi/unit-formulir',
        masuk: 'admin',
        buka: '/platform/unit-organisasi',
        siapkan: (p) => klik(p, tombol('Tambah Unit')),
        potong: dialog,
        penanda: {
            kode: tombol('Atur sendiri'),
            jenis: bidang('Jenis'),
            induk: bidang('Induk'),
            pengelola: bidang('Mengelola aset'),
            simpan: tombol('Simpan'),
        },
    },
    {
        nama: 'organisasi/lokasi',
        masuk: 'admin',
        buka: '/platform/lokasi',
        penanda: {
            kategori: tombol('Kelola Kategori'),
            tambah: tombol('Tambah Lokasi'),
        },
    },

    // Pengguna & peran
    {
        nama: 'pengguna/peran',
        masuk: 'admin',
        buka: '/platform/peran',
        penanda: { tambah: tombol('Tambah Peran'), izin: tombol('Kelola Izin') },
    },
    {
        nama: 'pengguna/izin',
        masuk: 'admin',
        buka: '/platform/peran',
        siapkan: (p) => klik(p, tombol('Kelola Izin')),
        potong: dialog,
        penanda: {
            centang: labelDialog('Lihat Aset'),
        },
    },
    {
        nama: 'pengguna/formulir',
        masuk: 'admin',
        buka: '/platform/pengguna',
        siapkan: (p) => klik(p, tombol('Tambah Pengguna')),
        potong: dialog,
        penanda: { email: bidang('Email'), jenis: bidang('Jenis Pengguna'), simpan: tombol('Simpan') },
    },
    {
        nama: 'pengguna/kelola-peran',
        masuk: 'admin',
        buka: '/platform/pengguna',
        siapkan: (p) => klik(p, tombol('Kelola Peran')),
        potong: dialog,
        penanda: {
            peran: (p) =>
                terlihat(p.locator('[role=dialog] [role=combobox]').filter({ hasText: 'Pilih peran' })),
            lingkup: (p) =>
                terlihat(p.locator('[role=dialog] [role=combobox]').filter({ hasText: 'Semua unit' })),
            tetapkan: tombol('Tetapkan'),
        },
    },

    // Penomoran & hari libur
    {
        nama: 'penomoran/daftar',
        masuk: 'admin',
        buka: '/platform/nomor-dokumen',
        penanda: { pratinjau: kepalaKolom('Pratinjau Berikutnya'), ubah: tombol('Ubah') },
    },
    {
        nama: 'penomoran/formulir',
        masuk: 'admin',
        buka: '/platform/nomor-dokumen',
        siapkan: async (p) => {
            await klik(p, tombol('Ubah'));
            // Isian awalan terfokus dan terseleksi saat dialog terbuka; di gambar tampak seperti salah.
            await p.evaluate(() => document.activeElement?.blur());
        },
        potong: dialog,
        penanda: { awalan: bidang('Awalan'), reset: bidang('Reset Periode'), format: bidang('Format Nomor') },
    },
    {
        nama: 'penomoran/hari-libur',
        masuk: 'admin',
        buka: '/platform/hari-libur',
        siapkan: (p) => klik(p, tombol('Tambah Hari Libur')),
        potong: dialog,
        penanda: { tanggal: bidang('Tanggal'), berulang: labelDialog('Berulang setiap tahun') },
    },

    // Data induk aset
    {
        nama: 'master-aset/kategori-formulir',
        masuk: 'admin',
        buka: '/aset-master/kategori',
        siapkan: (p) => klik(p, tombol('Tambah Kategori')),
        potong: dialog,
        penanda: {
            induk: bidang('Kategori Induk'),
            umur: bidang('Umur Manfaat'),
            kalibrasi: labelDialog('Memerlukan Kalibrasi'),
            pemeliharaan: labelDialog('Memerlukan Pemeliharaan'),
        },
    },
    {
        nama: 'master-aset/model-formulir',
        masuk: 'admin',
        buka: '/aset-master/model',
        siapkan: (p) => klik(p, tombol('Tambah Model')),
        potong: dialog,
        tinggi: 1000,
        penanda: { merek: bidang('Merek'), interval: bidang('Interval Pemeliharaan') },
    },

    // Aset
    {
        nama: 'aset/daftar',
        masuk: 'admin',
        buka: '/aset',
        penanda: { menu: menu('Daftar Aset'), daftarkan: tombol('Daftarkan Aset'), impor: tombol('Impor') },
    },
    {
        nama: 'aset/formulir',
        masuk: 'admin',
        buka: '/aset',
        siapkan: (p) => klik(p, tombol('Daftarkan Aset')),
        potong: dialog,
        penanda: {
            kategori: bidang('Kategori'),
            lokasi: bidang('Lokasi Awal'),
            pengelola: bidang('Unit Pengelola'),
            seri: bidang('Nomor Seri'),
        },
    },
    {
        nama: 'aset/impor',
        masuk: 'admin',
        buka: '/aset',
        siapkan: (p) => klik(p, tombol('Impor')),
        potong: dialog,
        penanda: {
            templat: (p) =>
                terlihat(
                    p.locator('[role=dialog] a, [role=dialog] button').filter({ hasText: 'Templat Excel' }),
                ),
            berkas: bidang('Pilih berkas'),
            periksa: tombol('Periksa Berkas'),
        },
    },
    // Siklus aset
    {
        nama: 'siklus-aset/mutasi',
        masuk: 'admin',
        buka: '/mutasi-aset',
        penanda: { buat: tombol('Buat Permintaan Mutasi'), status: kepalaKolom('Status') },
    },
    {
        nama: 'siklus-aset/mutasi-formulir',
        masuk: 'admin',
        buka: '/mutasi-aset',
        siapkan: (p) => klik(p, tombol('Buat Permintaan Mutasi')),
        potong: dialog,
        penanda: {
            jenis: bidang('Jenis Mutasi'),
            lokasi: bidang('Lokasi Tujuan'),
            unit: bidang('Unit Tujuan'),
            draft: tombol('Buat Draft'),
        },
    },
    {
        nama: 'siklus-aset/penghapusan-formulir',
        masuk: 'admin',
        buka: '/penghapusan-aset',
        siapkan: (p) => klik(p, tombol('Ajukan Penghapusan')),
        potong: dialog,
        penanda: {
            alasan: bidang('Alasan'),
            metode: bidang('Metode Penghapusan'),
            draft: tombol('Buat Draft'),
        },
    },

    // Kalibrasi
    {
        nama: 'kalibrasi/dasbor',
        masuk: 'admin',
        buka: '/kalibrasi',
        penanda: {
            ringkasan: (p) => terlihat(p.locator('main div').filter({ hasText: /^Terlambat Kalibrasi/ })),
            saring: kendali('Terlambat'),
            pengingat: tombol('Kirim Pengingat'),
        },
    },
    {
        nama: 'kalibrasi/rencana-formulir',
        masuk: 'admin',
        buka: '/kalibrasi/rencana',
        siapkan: (p) => klik(p, tombol('Buat Rencana Kalibrasi')),
        potong: dialog,
        tinggi: 1000,
        penanda: {
            aset: bidang('Pilih Aset'),
            jenis: bidang('Jenis Kalibrasi'),
            penyedia: bidang('Penyedia / Laboratorium'),
            interval: bidang('Interval (Hari)'),
            pengingat: bidang('Jendela Pengingat'),
        },
    },

    // Laporan
    {
        nama: 'lanjutan/laporan',
        masuk: 'admin',
        buka: '/pelaporan/laporan',
        penanda: { baru: tombol('Laporan baru'), rentang: kendali('30 hari'), unit: kendali('Semua unit') },
    },

    // Mode Lapangan (layar ponsel)
    {
        nama: 'lapangan/teknisi-tugas',
        masuk: 'teknisi.teknik',
        hp: true,
        buka: '/lapangan/teknisi/tugas',
        penanda: {
            tab: (p) => terlihat(p.getByRole('tab', { name: /^Hari ini/ })),
            kartu: (p) => terlihat(p.locator('[role=tabpanel] article')),
            pindai: (p) => terlihat(p.getByRole('link', { name: 'Pindai' })),
        },
    },
    {
        nama: 'lapangan/pelapor-beranda',
        masuk: 'pelapor',
        hp: true,
        buka: '/lapangan/pelapor',
        penanda: {
            lapor: (p) => terlihat(p.locator('a').filter({ hasText: 'Laporkan Kerusakan' })),
            jenis: (p) =>
                terlihat(p.getByText('Atau pilih jenis masalah').locator('xpath=following-sibling::*[1]')),
            konfirmasi: (p) => terlihat(p.locator('main a span').filter({ hasText: /^Konfirmasi$/ })),
        },
    },
    {
        nama: 'lapangan/pelapor-lapor',
        masuk: 'pelapor',
        hp: true,
        buka: '/lapangan/pelapor/lapor',
        penanda: {
            pindai: (p) =>
                terlihat(
                    p.getByText('Pindai QR alat').locator('xpath=ancestor::*[self::button or self::a][1]'),
                ),
            lokasi: (p) =>
                terlihat(p.getByText('Belum dipilih').locator('xpath=ancestor::div[.//button][1]')),
            tanpa: (p) =>
                terlihat(
                    p
                        .getByText('Tidak tahu alatnya?')
                        .locator('xpath=ancestor::*[self::button or self::a][1]'),
                ),
        },
    },
];

// ---------------------------------------------------------------------------

async function keWebp(halamanKonversi, png) {
    const dataUrl = await halamanKonversi.evaluate(
        async ({ b64, kualitas }) => {
            const img = new Image();
            img.src = `data:image/png;base64,${b64}`;
            await img.decode();
            const kanvas = document.createElement('canvas');
            kanvas.width = img.naturalWidth;
            kanvas.height = img.naturalHeight;
            kanvas.getContext('2d').drawImage(img, 0, 0);
            return kanvas.toDataURL('image/webp', kualitas);
        },
        { b64: png.toString('base64'), kualitas: KUALITAS_WEBP },
    );
    return Buffer.from(dataUrl.split(',')[1], 'base64');
}

async function masuk(peramban, peran, hp = false) {
    const konteks = await peramban.newContext({
        viewport: hp ? LAYAR_HP : { width: LEBAR, height: TINGGI },
        ...(hp ? { deviceScaleFactor: 2, isMobile: true, hasTouch: true } : {}),
        locale: 'id-ID',
        timezoneId: 'Asia/Jakarta',
    });
    const p = await konteks.newPage();
    await p.goto(`${PANGKAL}/login`);
    await p.fill('#email', `${peran}@amanpoll.test`);
    await p.fill('#kata-sandi', SANDI);
    await p.press('#kata-sandi', 'Enter');
    await p.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 15000 });
    return p;
}

async function tangkap(p, konversi, satu) {
    const lebar = satu.hp ? LAYAR_HP.width : LEBAR;
    const tinggi = satu.tinggi ?? (satu.hp ? LAYAR_HP.height : TINGGI);
    await p.setViewportSize({ width: lebar, height: tinggi });
    await p.goto(`${PANGKAL}${satu.buka}`);
    // Layar lapangan terus menyinkronkan data, jadi jaringannya mungkin tidak pernah benar-benar diam.
    await p.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    await p.addStyleTag({
        content:
            '*,*::before,*::after{transition:none!important;animation:none!important;caret-color:transparent!important}',
    });
    await p.waitForTimeout(900);
    if (satu.siapkan) await satu.siapkan(p);

    let clip = { x: 0, y: 0, width: lebar, height: tinggi };
    if (satu.potong) {
        const b = await satu.potong(p).boundingBox();
        if (!b) throw new Error('bagian yang dipotong tidak ditemukan');
        // Dialog dipotong pas di tepinya; halaman yang diredupkan di belakangnya hanya mengganggu.
        const tepi = 0;
        const x = Math.max(0, b.x - tepi);
        const y = Math.max(0, b.y - tepi);
        clip = {
            x,
            y,
            width: Math.min(lebar - x, b.width + tepi * 2),
            height: Math.min(tinggi - y, b.height + tepi * 2),
        };
    }

    const penanda = {};
    const hilang = [];
    for (const [kunci, pencari] of Object.entries(satu.penanda)) {
        const b = await pencari(p)
            .boundingBox({ timeout: 4000 })
            .catch(() => null);
        if (!b) {
            hilang.push(kunci);
            continue;
        }
        const x = b.x - RUANG_PENANDA - clip.x;
        const y = b.y - RUANG_PENANDA - clip.y;
        const diLuar =
            b.x < clip.x - 1 ||
            b.y < clip.y - 1 ||
            b.x + b.width > clip.x + clip.width + 1 ||
            b.y + b.height > clip.y + clip.height + 1;
        if (diLuar) {
            // Elemen ada tapi di luar gambar (tergulir atau terpotong): nomornya akan menunjuk ke udara.
            hilang.push(`${kunci} (di luar gambar)`);
            continue;
        }
        const bulat = (n) => Math.round(n * 100) / 100;
        penanda[kunci] = {
            x: bulat((x / clip.width) * 100),
            y: bulat((y / clip.height) * 100),
            w: bulat(((b.width + RUANG_PENANDA * 2) / clip.width) * 100),
            h: bulat(((b.height + RUANG_PENANDA * 2) / clip.height) * 100),
        };
    }

    const png = await p.screenshot({ clip });
    const webp = await keWebp(konversi, png);
    const berkasGambar = join(DIR_GAMBAR, `${satu.nama}.webp`);
    const berkasData = join(DIR_DATA, `${satu.nama}.json`);
    mkdirSync(dirname(berkasGambar), { recursive: true });
    mkdirSync(dirname(berkasData), { recursive: true });
    writeFileSync(berkasGambar, webp);
    writeFileSync(
        berkasData,
        `${JSON.stringify({ lebar: Math.round(clip.width), tinggi: Math.round(clip.height), penanda }, null, 2)}\n`,
    );

    return { ukuranKb: Math.round(webp.length / 1024), hilang };
}

const saringan = process.argv[2];
// Bahasa peramban menentukan teks bawaan seperti tombol pilih berkas dan format isian tanggal.
// Di Linux Chromium membacanya dari LANGUAGE/LANG; `--lang` hanya berlaku di sistem lain.
const peramban = await chromium.launch({
    executablePath: cariPeramban(),
    args: ['--lang=id-ID'],
    env: { ...process.env, LANGUAGE: 'id', LANG: 'id_ID.UTF-8' },
});
const konversi = await (await peramban.newContext()).newPage();
const sesi = {};
let gagal = 0;

for (const satu of DAFTAR.filter((t) => !saringan || t.nama.includes(saringan))) {
    try {
        const kunciSesi = `${satu.masuk}${satu.hp ? ':hp' : ''}`;
        sesi[kunciSesi] ??= await masuk(peramban, satu.masuk, satu.hp);
        const hasil = await tangkap(sesi[kunciSesi], konversi, satu);
        const catatan = hasil.hilang.length ? `  PENANDA TIDAK DITEMUKAN: ${hasil.hilang.join(', ')}` : '';
        if (hasil.hilang.length) gagal++;
        console.log(`${satu.nama.padEnd(34)} ${String(hasil.ukuranKb).padStart(4)} KB${catatan}`);
    } catch (galat) {
        gagal++;
        console.log(`${satu.nama.padEnd(34)} GAGAL: ${galat.message.split('\n')[0]}`);
    }
}

await peramban.close();
process.exit(gagal ? 1 : 0);
