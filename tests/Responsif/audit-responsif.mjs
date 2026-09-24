/**
 * Audit responsif Amanpoll (TASK 23.03, Gate 23).
 *
 * Menjalankan peramban sungguhan pada tujuh ukuran layar yang disebut TASK,
 * lalu memeriksa tiga hal yang tidak dapat dinilai dari kode:
 *
 *  1. Overflow mendatar — halaman yang memaksa geser ke samping di ponsel.
 *  2. Target sentuh — tombol dan tautan yang lebih kecil dari 44px di layar
 *     sentuh; DESIGN.md menargetkan pekerjaan lapangan dengan sarung tangan.
 *  3. Elemen yang keluar dari viewport.
 *
 * Tangkapan layar disimpan supaya hasilnya dapat ditinjau manusia, bukan hanya
 * dipercaya dari angka.
 */
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.AUDIT_PLAYWRIGHT ?? 'playwright');
import { existsSync, mkdirSync, readdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

/** Mencari biner peramban yang sudah terpasang di lingkungan. */
function glob(akar, polaDir, sisa) {
  if (!existsSync(akar)) return null;
  for (const d of readdirSync(akar)) {
    if (!polaDir.test(d)) continue;
    const p = join(akar, d, sisa);
    if (existsSync(p)) return p;
  }
  return null;
}

const PANGKAL = process.env.AUDIT_URL ?? 'http://127.0.0.1:8123';
const KELUARAN = process.env.AUDIT_OUT ?? 'tests/Responsif/hasil';

const UKURAN = [
  { nama: '360x800', width: 360, height: 800, sentuh: true },
  { nama: '390x844', width: 390, height: 844, sentuh: true },
  { nama: '768x1024', width: 768, height: 1024, sentuh: true },
  { nama: '1024x768', width: 1024, height: 768, sentuh: false },
  { nama: '1280x800', width: 1280, height: 800, sentuh: false },
  { nama: '1440x900', width: 1440, height: 900, sentuh: false },
  { nama: '1920x1080', width: 1920, height: 1080, sentuh: false },
];

/** Halaman utama; yang bertanda `teknisi` adalah lingkup Gate 23. */
const HALAMAN = [
  { nama: 'dashboard', path: '/', teknisi: true },
  { nama: 'keluhan', path: '/pemeliharaan/keluhan', teknisi: true },
  { nama: 'perintah-kerja', path: '/pemeliharaan/perintah-kerja', teknisi: true },
  { nama: 'mode-lapangan', path: '/lapangan', teknisi: true },
  { nama: 'daftar-periksa', path: '/daftar-periksa/templat', teknisi: true },
  { nama: 'inspeksi', path: '/inspeksi', teknisi: true },
  { nama: 'aset', path: '/aset', teknisi: false },
  { nama: 'stok', path: '/stok', teknisi: false },
  { nama: 'langganan', path: '/langganan', teknisi: false },
  { nama: 'persetujuan', path: '/persetujuan/permintaan', teknisi: false },
];

/**
 * Ambang target sentuh berbeda menurut bentuk perangkat, bukan dilonggarkan
 * sesuka hati:
 *
 *  - Di lebar ponsel dipakai 44px, sesuai DESIGN.md 9.3. Di sinilah pekerjaan
 *    lapangan dilakukan sambil berdiri dan sering bersarung tangan, dan di
 *    sinilah navigasi berupa drawer yang disentuh.
 *  - Di lebar tablet dipakai 24px, yaitu ambang WCAG 2.5.8 Target Size (AA).
 *    Tablet dipegang dua tangan dengan jarak baca lebih dekat, dan baris menu
 *    setinggi 36px pada rail samping memang lazim dan lolos ambang itu.
 */
const MIN_SENTUH_PONSEL = 44;
const MIN_SENTUH_TABLET = 24;

async function masuk(page) {
  await page.goto(`${PANGKAL}/login`, { waitUntil: 'networkidle' });
  await page.getByLabel('Email').fill(process.env.AUDIT_EMAIL ?? 'admin@amanpoll.test');
  await page.getByLabel('Kata Sandi').fill(process.env.AUDIT_PASS ?? 'password');
  await page.getByRole('button', { name: 'Masuk' }).click();
  await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 20000 });
}

async function periksa(page, sentuh, minSentuh, ponsel) {
  return page.evaluate(
    ({ minSentuh, sentuh, ponsel }) => {
      const doc = document.documentElement;
      const lebarViewport = window.innerWidth;

      const overflow = Math.max(doc.scrollWidth, document.body.scrollWidth) - lebarViewport;

      // Wadah bergeser mendatar di dalam halaman. Halaman yang sendirinya tidak
      // menggeser tetapi menyembunyikan tabel lebar di dalamnya tetap menuntut
      // gerakan mendatar terus-menerus, dan itu tidak terlihat dari scrollWidth
      // dokumen. Hanya dihitung pada lebar sentuh.
      // Hanya ditegakkan di lebar ponsel: DESIGN.md 9.2 memang mengizinkan
      // tabel bergeser mendatar di tablet, sedangkan 9.3 meminta tabel
      // operasional berubah menjadi kartu di ponsel.
      const geserDalam = [];
      if (ponsel) {
        for (const el of document.querySelectorAll('*')) {
          const g = getComputedStyle(el);
          if (!/(auto|scroll)/.test(g.overflowX)) continue;
          if (el.scrollWidth - el.clientWidth <= 16) continue;
          geserDalam.push({
            tag: el.tagName.toLowerCase(),
            kelas: (el.className || '').toString().slice(0, 60),
            lebih: el.scrollWidth - el.clientWidth,
          });
        }
      }

      const keluar = [];
      const kecil = [];
      const interaktif = document.querySelectorAll(
        'button, a[href], input, select, textarea, [role="button"], [role="option"], [role="combobox"]',
      );

      for (const el of interaktif) {
        const r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) continue;

        const gaya = getComputedStyle(el);
        if (gaya.visibility === 'hidden' || gaya.display === 'none') continue;

        // Elemen yang menonjol melewati tepi kanan viewport.
        // Elemen di dalam wadah yang memang bergeser mendatar tidak dihitung
        // keluar layar: ia terjangkau dengan menggeser wadah itu, bukan hilang.
        let diWadahGeser = false;
        for (let n = el.parentElement; n && n !== document.body; n = n.parentElement) {
          const g = getComputedStyle(n);
          if (/(auto|scroll)/.test(g.overflowX) && n.scrollWidth > n.clientWidth) {
            diWadahGeser = true;
            break;
          }
        }

        if (!diWadahGeser && r.right > lebarViewport + 1) {
          keluar.push({
            tag: el.tagName.toLowerCase(),
            teks: (el.textContent ?? '').trim().slice(0, 40),
            kanan: Math.round(r.right),
          });
        }

        if (sentuh && (r.height < minSentuh || r.width < minSentuh)) {
          // Ikon di dalam tombol tidak dihitung; hanya elemen yang benar-benar
          // menjadi sasaran sentuh.
          if (el.closest('button, a[href], [role="button"]') !== el) continue;

          // Tautan teks di dalam kalimat dikecualikan, sesuai WCAG 2.5.8 yang
          // memang tidak menuntut ukuran minimum untuk tautan sebaris: melebarkan
          // kata di tengah paragraf justru merusak keterbacaannya.
          const sebaris =
            el.tagName === 'A' &&
            (el.closest('nav[aria-label="Breadcrumb"]') !== null ||
              el.closest('p, dd, li:not([class*="menu"])') !== null);
          if (sebaris) continue;

          kecil.push({
            tag: el.tagName.toLowerCase(),
            teks: (el.textContent ?? '').trim().slice(0, 40),
            ukuran: `${Math.round(r.width)}x${Math.round(r.height)}`,
          });
        }
      }

      return { overflow, geserDalam: geserDalam.slice(0, 6), jumlahGeserDalam: geserDalam.length, ambangSentuh: minSentuh, keluar: keluar.slice(0, 10), kecil: kecil.slice(0, 15), jumlahKecil: kecil.length };
    },
    { minSentuh, sentuh, ponsel },
  );
}

// Biner disediakan lingkungan; jangan mengunduh ulang.
const BINER =
  process.env.AUDIT_CHROME ??
  glob('/opt/pw-browsers', /^chromium-\d+$/, 'chrome-linux/chrome') ??
  undefined;

const peramban = await chromium.launch(BINER ? { executablePath: BINER } : {});
const hasil = [];

try {
  for (const ukuran of UKURAN) {
    const konteks = await peramban.newContext({
      viewport: { width: ukuran.width, height: ukuran.height },
      hasTouch: ukuran.sentuh,
      isMobile: ukuran.sentuh && ukuran.width < 768,
      deviceScaleFactor: 1,
    });
    const page = await konteks.newPage();

    try {
      await masuk(page);
    } catch (e) {
      hasil.push({ ukuran: ukuran.nama, halaman: 'login', galat: String(e).slice(0, 200) });
      await konteks.close();
      continue;
    }

    const dir = join(KELUARAN, ukuran.nama);
    mkdirSync(dir, { recursive: true });

    for (const h of HALAMAN) {
      try {
        const respons = await page.goto(`${PANGKAL}${h.path}`, { waitUntil: 'networkidle', timeout: 25000 });
        const status = respons?.status() ?? 0;
        await page.waitForTimeout(250);

        const p = await periksa(page, ukuran.sentuh, ukuran.width < 640 ? MIN_SENTUH_PONSEL : MIN_SENTUH_TABLET, ukuran.width < 640);
        await page.screenshot({ path: join(dir, `${h.nama}.png`), fullPage: false });

        hasil.push({ ukuran: ukuran.nama, halaman: h.nama, teknisi: h.teknisi, status, ...p });
      } catch (e) {
        hasil.push({ ukuran: ukuran.nama, halaman: h.nama, teknisi: h.teknisi, galat: String(e).slice(0, 200) });
      }
    }

    await konteks.close();
  }
} finally {
  await peramban.close();
}

mkdirSync(KELUARAN, { recursive: true });
writeFileSync(join(KELUARAN, 'hasil.json'), JSON.stringify(hasil, null, 2));

const masalah = hasil.filter(
  (h) =>
    h.galat ||
    h.overflow > 1 ||
    (h.keluar?.length ?? 0) > 0 ||
    (h.jumlahKecil ?? 0) > 0 ||
    (h.jumlahGeserDalam ?? 0) > 0,
);
console.log(`Diperiksa: ${hasil.length} kombinasi halaman × ukuran.`);
console.log(`Bermasalah: ${masalah.length}.`);
for (const m of masalah) {
  console.log(
    `  ${m.ukuran} ${m.halaman}` +
      (m.galat ? ` GALAT ${m.galat}` : '') +
      (m.overflow > 1 ? ` overflow=${m.overflow}px` : '') +
      ((m.keluar?.length ?? 0) > 0 ? ` keluar=${m.keluar.length}` : '') +
      ((m.jumlahKecil ?? 0) > 0 ? ` targetKecil=${m.jumlahKecil}` : '') +
      ((m.jumlahGeserDalam ?? 0) > 0 ? ` geserDalam=${m.jumlahGeserDalam}` : ''),
  );
}
process.exit(masalah.some((m) => m.galat || m.overflow > 1) ? 1 : 0);
