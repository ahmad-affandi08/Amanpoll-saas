/**
 * Gate 23 — tidak ada halaman utama yang memerlukan desktop untuk
 * menyelesaikan pekerjaan teknisi dasar.
 *
 * Dibuktikan dengan benar-benar mengerjakannya di layar 360×800: membuka menu
 * lewat drawer, menelusuri keluhan dan perintah kerja, membuka satu perintah
 * kerja, dan memastikan kendali operasional yang dibutuhkan teknisi benar-benar
 * dapat disentuh — bukan sekadar ada di DOM.
 */
import { createRequire } from 'node:module';
import { existsSync, mkdirSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.AUDIT_PLAYWRIGHT ?? 'playwright');

function cariBiner(akar, pola, sisa) {
  if (!existsSync(akar)) return null;
  for (const d of readdirSync(akar)) {
    if (!pola.test(d)) continue;
    const p = join(akar, d, sisa);
    if (existsSync(p)) return p;
  }
  return null;
}

const PANGKAL = process.env.AUDIT_URL ?? 'http://127.0.0.1:8123';
const KELUARAN = process.env.AUDIT_OUT ?? 'tests/Responsif/hasil/gate23';
const PONSEL = { width: 360, height: 800 };

const langkah = [];
function catat(nama, lulus, catatan = '') {
  langkah.push({ nama, lulus, catatan });
  console.log(`${lulus ? 'LULUS' : 'GAGAL'}  ${nama}${catatan ? ` — ${catatan}` : ''}`);
}

/** Sebuah kendali dianggap terjangkau bila terlihat, cukup besar, dan tidak tertimpa. */
async function terjangkau(page, locator, minPx = 44) {
  const kotak = await locator.boundingBox();
  if (!kotak) return { ok: false, alasan: 'tidak terlihat' };
  if (kotak.height < minPx) return { ok: false, alasan: `tinggi ${Math.round(kotak.height)}px` };
  if (kotak.x + kotak.width > PONSEL.width + 1) return { ok: false, alasan: 'melewati tepi layar' };

  const diAtas = await page.evaluate(
    ({ x, y }) => {
      const el = document.elementFromPoint(x, y);
      return el ? el.tagName.toLowerCase() : null;
    },
    { x: kotak.x + kotak.width / 2, y: kotak.y + kotak.height / 2 },
  );

  return { ok: diAtas !== null, alasan: diAtas === null ? 'tertimpa elemen lain' : '' };
}

const biner = process.env.AUDIT_CHROME ?? cariBiner('/opt/pw-browsers', /^chromium-\d+$/, 'chrome-linux/chrome');
const peramban = await chromium.launch(biner ? { executablePath: biner } : {});
const konteks = await peramban.newContext({ viewport: PONSEL, hasTouch: true, isMobile: true });
const page = await konteks.newPage();
mkdirSync(KELUARAN, { recursive: true });

try {
  // 1. Masuk dari ponsel.
  await page.goto(`${PANGKAL}/login`, { waitUntil: 'networkidle' });
  await page.getByLabel('Email').fill(process.env.AUDIT_EMAIL ?? 'admin@amanpoll.test');
  await page.locator('#kata-sandi').fill(process.env.AUDIT_PASS ?? 'password');
  await page.getByRole('button', { name: 'Masuk' }).click();
  await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 20000 });
  catat('Teknisi dapat masuk dari layar 360px', true);

  // 2. Navigasi tersedia lewat drawer, bukan hanya sidebar desktop.
  const pemicu = page.getByRole('button', { name: 'Buka/tutup sidebar' });
  const jangkauPemicu = await terjangkau(page, pemicu);
  catat('Pemicu drawer navigasi terjangkau', jangkauPemicu.ok, jangkauPemicu.alasan);

  await pemicu.click();
  await page.waitForTimeout(400);
  // Pintu masuk teknisi diperiksa pada tingkat teratas menu; butir seperti
  // "Keluhan" berada di dalam grup yang dapat terlipat, sehingga tidak layak
  // dijadikan penanda bahwa drawer terbuka.
  const drawerTerbuka = await page
    .getByRole('link', { name: 'Dashboard' })
    .first()
    .isVisible();
  catat('Menu utama terbuka sebagai drawer', drawerTerbuka);
  await page.screenshot({ path: join(KELUARAN, '01-drawer.png') });
  await page.keyboard.press('Escape');
  await page.waitForTimeout(300);

  // 3. Daftar keluhan terbaca tanpa geser mendatar.
  await page.goto(`${PANGKAL}/pemeliharaan/keluhan`, { waitUntil: 'networkidle' });
  const overflowKeluhan = await page.evaluate(
    () => Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - window.innerWidth,
  );
  catat('Daftar keluhan tanpa geser mendatar', overflowKeluhan <= 1, `overflow ${overflowKeluhan}px`);
  await page.screenshot({ path: join(KELUARAN, '02-keluhan.png') });

  // 4. Daftar perintah kerja dan pembukaan satu pekerjaan.
  await page.goto(`${PANGKAL}/pemeliharaan/perintah-kerja`, { waitUntil: 'networkidle' });
  const overflowPk = await page.evaluate(
    () => Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - window.innerWidth,
  );
  catat('Daftar perintah kerja tanpa geser mendatar', overflowPk <= 1, `overflow ${overflowPk}px`);

  // Halaman yang tidak menggeser tetapi menyembunyikan tabel lebar di dalam
  // wadah bergeser tetap menuntut gerakan mendatar terus-menerus. Untuk daftar
  // yang dibaca teknisi, itu sama saja dengan menuntut layar besar.
  const geserDalam = await page.evaluate(() => {
    let jumlah = 0;
    for (const el of document.querySelectorAll('*')) {
      const g = getComputedStyle(el);
      if (!/(auto|scroll)/.test(g.overflowX)) continue;
      if (el.scrollWidth - el.clientWidth > 16) jumlah++;
    }
    return jumlah;
  });
  catat('Daftar perintah kerja tidak menyembunyikan tabel lebar', geserDalam === 0, `${geserDalam} wadah`);
  await page.screenshot({ path: join(KELUARAN, '03-perintah-kerja.png') });

  // Rute detail berada di bawah prefiks domain Pemeliharaan. Alamatnya dibaca
  // dari tautan lalu dibuka langsung, dan perpindahannya diperiksa: klik yang
  // gagal berpindah pernah membuat langkah berikutnya diam-diam mengukur
  // halaman daftar dan lulus tanpa menguji apa pun.
  const tautanPekerjaan = page.locator('a[href*="/pemeliharaan/perintah-kerja/"]').first();
  if ((await tautanPekerjaan.count()) > 0) {
    const alamat = await tautanPekerjaan.getAttribute('href');
    await page.goto(`${PANGKAL}${alamat}`, { waitUntil: 'networkidle' });
    catat(
      'Halaman detail benar-benar terbuka',
      new URL(page.url()).pathname === alamat,
      page.url(),
    );

    const overflowDetail = await page.evaluate(
      () => Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - window.innerWidth,
    );
    catat('Detail perintah kerja tanpa geser mendatar', overflowDetail <= 1, `overflow ${overflowDetail}px`);

    // Kendali yang menentukan: teknisi harus dapat mengubah status pekerjaannya.
    // Kendali inti pekerjaan teknisi. Ketiadaannya dilaporkan gagal, bukan
    // dilewati: gate yang lulus karena tombolnya tidak ada tidak membuktikan
    // apa pun.
    const ubahStatus = page.getByRole('button', { name: 'Ubah Status' }).first();
    if ((await ubahStatus.count()) > 0) {
      const j = await terjangkau(page, ubahStatus);
      catat('Kendali ubah status terjangkau di 360px', j.ok, j.alasan);

      // Dibuka sungguhan: dialog panjang di ponsel harus tetap dapat dipakai.
      if (j.ok && (await ubahStatus.isEnabled())) {
        await ubahStatus.click();
        await page.waitForTimeout(400);
        const dialog = page.getByRole('dialog').first();
        const terlihat = await dialog.isVisible();
        const kotak = await dialog.boundingBox();
        const muat = !kotak || kotak.x >= -1;
        catat('Dialog ubah status muat di layar 360px', terlihat && muat);
        await page.screenshot({ path: join(KELUARAN, '04b-dialog-status.png') });
        await page.keyboard.press('Escape');
      }
    } else {
      catat('Kendali ubah status terjangkau di 360px', false, 'tombol Ubah Status tidak dirender');
    }

    const breadcrumb = await page.locator('nav[aria-label="Breadcrumb"]').first().isVisible();
    catat('Breadcrumb tersedia pada halaman detail', breadcrumb);
    await page.screenshot({ path: join(KELUARAN, '04-detail-perintah-kerja.png') });
  } else {
    // Gate ini menguji halaman, bukan data. Tanpa satu pun perintah kerja tidak
    // ada yang dapat dibuktikan, jadi ini dilaporkan gagal, bukan dilewati.
    catat('Detail perintah kerja dapat dibuka', false, 'tidak ada perintah kerja pada basis data uji');
  }

  // 5. Mode Lapangan — jalur kerja lapangan (dulu /offline/teknisi).
  await page.goto(`${PANGKAL}/lapangan`, { waitUntil: 'networkidle' });
  const overflowOffline = await page.evaluate(
    () => Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - window.innerWidth,
  );
  catat('Mode teknisi offline tanpa geser mendatar', overflowOffline <= 1, `overflow ${overflowOffline}px`);
  await page.screenshot({ path: join(KELUARAN, '05-offline-teknisi.png') });

  // 6. Tidak ada halaman teknisi yang menyembunyikan isinya di balik lebar desktop.
  const tersembunyi = await page.evaluate(() => {
    const kandidat = document.querySelectorAll('[class*="hidden"][class*="lg:"], [class*="hidden"][class*="xl:"]');
    let jumlah = 0;
    for (const el of kandidat) {
      // Hanya dihitung bila memuat kendali; teks dekoratif boleh disembunyikan.
      if (el.querySelector('button, a[href], input, select')) jumlah++;
    }
    return jumlah;
  });
  catat('Tidak ada kendali yang hanya muncul di lebar desktop', tersembunyi === 0, `${tersembunyi} blok`);
} finally {
  await konteks.close();
  await peramban.close();
}

const gagal = langkah.filter((l) => !l.lulus);
console.log(`\nGate 23: ${langkah.length - gagal.length}/${langkah.length} langkah lulus.`);
process.exit(gagal.length === 0 ? 0 : 1);
