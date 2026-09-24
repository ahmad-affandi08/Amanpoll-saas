/*
 * Service worker Amanpoll (FASE 20.01/20.02, Mode Lapangan FASE 39).
 *
 * Ditulis tangan dan berada di luar bundel Vite supaya cakupannya tetap `/`
 * dan berkasnya dapat dimuat browser tanpa manifest build.
 *
 * Aturan cache yang dipegang berkas ini:
 *
 * 1. Yang di-cache hanya aset statis dan layar Mode Lapangan (`/lapangan/...`):
 *    navigasi peramban dan kunjungan Inertia (header `X-Inertia`) ke jalur itu.
 *    Teknisi dan pelapor harus tetap bisa membuka layarnya tanpa sinyal.
 *    Antrean dan paket kerjanya sendiri tinggal di IndexedDB, bukan di sini.
 * 2. Respons endpoint data (/offline/paket, /offline/antrian, API, JSON
 *    `@/lib/http`) tidak pernah masuk cache. Jawaban basi di sana akan
 *    menyesatkan dan isinya milik satu organisasi.
 * 3. Nama cache runtime memuat kunci konteks (organisasi + pengguna), sehingga
 *    perangkat yang dipakai bergantian tidak pernah menyajikan respons dari
 *    sesi pengguna sebelumnya. Kunci itu juga disimpan di cache meta karena
 *    worker dapat dimatikan browser kapan saja dan kehilangan variabelnya.
 *    Tanpa kunci konteks (tamu), layar lapangan tidak disimpan sama sekali.
 * 4. Hanya respons 200 asli dari server ini yang disimpan: bukan pengalihan
 *    ke halaman login, bukan kunjungan Inertia parsial, bukan galat.
 * 5. Logout mengirim pesan BERSIHKAN: cache runtime dan kunci konteks dibuang.
 */

const VERSI = 'v3';
const CACHE_KERANGKA = `amanpoll-kerangka-${VERSI}`;
const CACHE_META = `amanpoll-meta-${VERSI}`;
const AWALAN_RUNTIME = `amanpoll-runtime-${VERSI}-`;
const HALAMAN_OFFLINE = '/offline.html';
const KUNCI_META_KONTEKS = '/__amanpoll/konteks';
const KONTEKS_TAMU = 'tamu';

/** Kunci konteks aktif; diisi aplikasi lewat pesan TETAPKAN_KONTEKS. `null` = belum dibaca dari cache meta. */
let kunciKonteks = null;

/** Aset yang wajib ada supaya halaman offline tetap tampil rapi. */
const KERANGKA = [
  HALAMAN_OFFLINE,
  '/site.webmanifest',
  '/favicon.svg',
  '/android-chrome-192x192.png',
  '/android-chrome-512x512.png',
];

/** Path yang boleh disimpan di cache runtime: murni aset build dan gambar. */
const POLA_ASET_STATIS = [/^\/build\//, /^\/images\//, /^\/assets\//, /^\/icons\//];

/**
 * Layar Mode Lapangan (teknisi dan pelapor) yang boleh disimpan per konteks.
 * Mode Lapangan tidak berguna kalau hanya dapat dibuka saat ada sinyal.
 */
const POLA_HALAMAN_LAPANGAN = /^\/lapangan(\/|$)/;

/** Beranda tiap mode; yang terakhir dibuka menjadi tujuan pintu masuk saat offline. */
const BERANDA_LAPANGAN = ['/lapangan/teknisi', '/lapangan/pelapor'];
const PINTU_MASUK_LAPANGAN = ['/', '/lapangan', '/offline/teknisi'];
const KUNCI_BERANDA_LAPANGAN = '/__amanpoll/beranda-lapangan';

/** Batas tunggu jaringan sebelum memakai salinan tersimpan saat sinyal sangat lemah. */
const BATAS_TUNGGU_JARINGAN_MS = 8000;

function asetStatis(url) {
  return POLA_ASET_STATIS.some((pola) => pola.test(url.pathname));
}

function halamanLapangan(url) {
  return POLA_HALAMAN_LAPANGAN.test(url.pathname);
}

async function kunciAktif() {
  if (kunciKonteks !== null) {
    return kunciKonteks;
  }

  const meta = await caches.open(CACHE_META);
  const tersimpan = await meta.match(KUNCI_META_KONTEKS);
  kunciKonteks = tersimpan ? await tersimpan.text() : KONTEKS_TAMU;

  return kunciKonteks;
}

async function cacheRuntime() {
  return caches.open(`${AWALAN_RUNTIME}${await kunciAktif()}`);
}

/** Kunjungan Inertia disimpan di bawah kunci tersendiri supaya tidak menimpa HTML navigasi di URL yang sama. */
function kunciInertia(url) {
  const salinan = new URL(url.href);
  salinan.searchParams.set('__inertia', '1');
  return salinan.href;
}

/** Respons asli 200 dari server ini, tanpa pengalihan (mis. sesi habis → halaman login). */
function layakDisimpan(respons) {
  return respons.ok && respons.status === 200 && respons.type === 'basic' && !respons.redirected;
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(CACHE_KERANGKA)
      .then((cache) => cache.addAll(KERANGKA))
      .then(() => {
        // Pemasangan pertama boleh langsung mengambil alih. Pembaruan tidak:
        // worker baru menunggu di status "waiting" sampai pengguna menyetujui
        // muat ulang, supaya halaman yang sedang dipakai teknisi tidak berganti
        // versi di tengah pekerjaan.
        if (!self.registration.active) {
          return self.skipWaiting();
        }

        return undefined;
      }),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((nama) =>
        Promise.all(
          nama
            .filter(
              (n) =>
                n.startsWith('amanpoll-') &&
                n !== CACHE_KERANGKA &&
                n !== CACHE_META &&
                !n.startsWith(AWALAN_RUNTIME),
            )
            .map((n) => caches.delete(n)),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('message', (event) => {
  const pesan = event.data || {};

  // Strategi pembaruan: aplikasi menawarkan "muat ulang" ke pengguna, lalu
  // worker baru mengambil alih hanya setelah pengguna setuju.
  if (pesan.type === 'LEWATI_MENUNGGU') {
    self.skipWaiting();
    return;
  }

  if (pesan.type === 'TETAPKAN_KONTEKS' && typeof pesan.kunci === 'string') {
    event.waitUntil(
      kunciAktif().then(async (sebelumnya) => {
        kunciKonteks = pesan.kunci;
        const meta = await caches.open(CACHE_META);
        await meta.put(KUNCI_META_KONTEKS, new Response(pesan.kunci));
        if (sebelumnya !== pesan.kunci) {
          await caches.delete(`${AWALAN_RUNTIME}${sebelumnya}`);
        }
      }),
    );
    return;
  }

  if (pesan.type === 'BERSIHKAN') {
    kunciKonteks = KONTEKS_TAMU;
    event.waitUntil(
      caches
        .keys()
        .then((nama) =>
          Promise.all(nama.filter((n) => n.startsWith(AWALAN_RUNTIME)).map((n) => caches.delete(n))),
        )
        .then(() => caches.open(CACHE_META))
        .then((meta) => meta.delete(KUNCI_META_KONTEKS)),
    );
  }
});

self.addEventListener('fetch', (event) => {
  const permintaan = event.request;

  if (permintaan.method !== 'GET') {
    return;
  }

  const url = new URL(permintaan.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // Navigasi: selalu ke jaringan dulu supaya data yang tampil tidak basi,
  // dengan halaman offline sebagai jaring pengaman.
  if (permintaan.mode === 'navigate') {
    event.respondWith(navigasi(permintaan, url));
    return;
  }

  // Kunjungan Inertia antar-layar Mode Lapangan (klik tautan, muat ulang props).
  if (halamanLapangan(url) && permintaan.headers.get('X-Inertia') === 'true') {
    event.respondWith(kunjunganInertia(permintaan, url));
    return;
  }

  // Layar "Menyiapkan Mode Lapangan" mengambil HTML utuh tiap layar lebih dulu, supaya
  // membuka ulang aplikasi tanpa sinyal (navigasi peramban) tetap menemukan halamannya.
  if (halamanLapangan(url) && permintaan.headers.get('X-Amanpoll-Simpan') === 'halaman') {
    event.respondWith(simpanHalaman(permintaan, url));
    return;
  }

  if (asetStatis(url)) {
    event.respondWith(asetDariCache(permintaan));
    return;
  }

  // Sisanya (endpoint offline, JSON, unduhan berkas) sengaja dibiarkan lewat
  // tanpa cache: isinya spesifik tenant dan tidak boleh mengendap di perangkat.
});

/** Jaringan dengan batas waktu hanya bila ada salinan cadangan; tanpa cadangan tunggu sampai selesai. */
function ambilDariJaringan(permintaan, adaCadangan) {
  const ambil = fetch(permintaan);
  if (!adaCadangan) {
    return ambil;
  }

  return Promise.race([
    ambil,
    new Promise((_, gagal) =>
      setTimeout(() => gagal(new Error('jaringan lambat')), BATAS_TUNGGU_JARINGAN_MS),
    ),
  ]);
}

/**
 * Network-first untuk seluruh navigasi. Layar Mode Lapangan disimpan ke cache
 * runtime bertenant supaya tetap dapat dibuka tanpa sinyal; navigasi lain yang
 * gagal jatuh ke halaman cadangan.
 */
async function navigasi(permintaan, url) {
  const kunci = await kunciAktif();
  const bolehDisimpan = halamanLapangan(url) && kunci !== KONTEKS_TAMU;
  const cache = kunci !== KONTEKS_TAMU ? await cacheRuntime() : null;
  const cadangan = bolehDisimpan && cache ? await cariTersimpan(cache, url.href) : undefined;

  try {
    const respons = await ambilDariJaringan(permintaan, cadangan !== undefined);

    if (bolehDisimpan && cache && layakDisimpan(respons)) {
      await cache.put(url.href, respons.clone());
      await ingatBerandaLapangan(cache, url);
    }

    return respons;
  } catch (galat) {
    if (cadangan) {
      return cadangan;
    }

    // Pintu masuk yang di server hanya mengalihkan (ikon PWA `/`, `/lapangan`,
    // pintasan lama `/offline/teknisi`) diarahkan ke beranda lapangan terakhir.
    if (cache && PINTU_MASUK_LAPANGAN.includes(url.pathname)) {
      const beranda = await cache.match(KUNCI_BERANDA_LAPANGAN);
      if (beranda) {
        return Response.redirect(new URL(await beranda.text(), self.location.origin).href, 302);
      }
    }

    const kerangka = await caches.open(CACHE_KERANGKA);
    const halamanCadangan = await kerangka.match(HALAMAN_OFFLINE);

    return halamanCadangan || Response.error();
  }
}

/** Menyimpan HTML utuh satu layar lapangan di kunci yang sama dengan navigasinya. */
async function simpanHalaman(permintaan, url) {
  const respons = await fetch(permintaan);
  if ((await kunciAktif()) !== KONTEKS_TAMU && layakDisimpan(respons)) {
    const cache = await cacheRuntime();
    await cache.put(url.href, respons.clone());
    await ingatBerandaLapangan(cache, url);
  }

  return respons;
}

/** Mencatat beranda Mode Lapangan yang terakhir berhasil dibuka konteks ini. */
async function ingatBerandaLapangan(cache, url) {
  if (BERANDA_LAPANGAN.includes(url.pathname)) {
    await cache.put(KUNCI_BERANDA_LAPANGAN, new Response(url.pathname));
  }
}

/** Salinan tersimpan untuk URL persis, lalu URL yang sama tanpa kueri. */
async function cariTersimpan(cache, href) {
  return (
    (await cache.match(href, { ignoreVary: true })) ||
    (await cache.match(href, { ignoreVary: true, ignoreSearch: true }))
  );
}

/**
 * Kunjungan Inertia ke layar Mode Lapangan: network-first. Respons halaman
 * utuh disimpan; kunjungan parsial (`X-Inertia-Partial-Data`) tidak, supaya
 * props yang tersimpan selalu lengkap. Tanpa sinyal, salinan halaman utuh
 * terakhir yang dipakai.
 */
async function kunjunganInertia(permintaan, url) {
  const kunci = await kunciAktif();
  if (kunci === KONTEKS_TAMU) {
    return fetch(permintaan);
  }

  const cache = await cacheRuntime();
  const kunciSimpan = kunciInertia(url);
  const cadangan = await cariTersimpan(cache, kunciSimpan);
  const parsial =
    permintaan.headers.has('X-Inertia-Partial-Data') || permintaan.headers.has('X-Inertia-Partial-Except');

  try {
    const respons = await ambilDariJaringan(permintaan, cadangan !== undefined);

    if (!parsial && layakDisimpan(respons) && respons.headers.get('X-Inertia') === 'true') {
      await cache.put(kunciSimpan, respons.clone());
      await ingatBerandaLapangan(cache, url);
    }

    return respons;
  } catch (galat) {
    if (cadangan) {
      return cadangan;
    }

    throw galat;
  }
}

/**
 * Cache-first untuk aset statis ber-hash. Aset build Vite memiliki hash pada
 * namanya, jadi versi baru selalu memakai URL baru dan tidak pernah basi.
 */
async function asetDariCache(permintaan) {
  const cache = await cacheRuntime();
  const tersimpan = await cache.match(permintaan);
  if (tersimpan) {
    return tersimpan;
  }

  try {
    const respons = await fetch(permintaan);
    if (respons.ok && respons.type === 'basic') {
      cache.put(permintaan, respons.clone());
    }

    return respons;
  } catch (galat) {
    const kerangka = await caches.open(CACHE_KERANGKA);
    const cadangan = await kerangka.match(permintaan);
    if (cadangan) {
      return cadangan;
    }

    throw galat;
  }
}
