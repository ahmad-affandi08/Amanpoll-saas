/*
 * Service worker Amanpoll (FASE 20.01/20.02).
 *
 * Ditulis tangan dan berada di luar bundel Vite supaya cakupannya tetap `/`
 * dan berkasnya dapat dimuat browser tanpa manifest build.
 *
 * Aturan cache yang dipegang berkas ini:
 *
 * 1. Yang di-cache hanya aset statis dan satu halaman: ruang kerja teknisi.
 *    Halaman itu harus tetap terbuka tanpa sinyal, jadi kerangkanya disimpan;
 *    seluruh isi kerjanya sendiri datang dari IndexedDB, bukan dari cache ini.
 * 2. Respons endpoint data (/offline/paket, /offline/antrian, API) tidak
 *    pernah masuk cache. Jawaban basi di sana akan menyesatkan teknisi dan
 *    isinya milik satu organisasi.
 * 3. Nama cache runtime memuat kunci konteks (organisasi + pengguna), sehingga
 *    perangkat yang dipakai bergantian tidak pernah menyajikan respons dari
 *    sesi pengguna sebelumnya.
 * 4. Logout mengirim pesan BERSIHKAN dan cache runtime dibuang seluruhnya.
 */

const VERSI = 'v1';
const CACHE_KERANGKA = `amanpoll-kerangka-${VERSI}`;
const AWALAN_RUNTIME = `amanpoll-runtime-${VERSI}-`;
const HALAMAN_OFFLINE = '/offline.html';

/** Kunci konteks aktif; diisi aplikasi lewat pesan TETAPKAN_KONTEKS. */
let kunciKonteks = 'tamu';

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
 * Satu-satunya halaman yang kerangkanya boleh disimpan. Ruang kerja teknisi
 * tidak akan berguna kalau hanya dapat dibuka saat ada sinyal.
 */
const HALAMAN_OFFLINE_DIIZINKAN = '/offline/teknisi';

function cacheRuntime() {
  return `${AWALAN_RUNTIME}${kunciKonteks}`;
}

function asetStatis(url) {
  return POLA_ASET_STATIS.some((pola) => pola.test(url.pathname));
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
            .filter((n) => n.startsWith('amanpoll-') && n !== CACHE_KERANGKA && !n.startsWith(AWALAN_RUNTIME))
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
    const sebelumnya = cacheRuntime();
    kunciKonteks = pesan.kunci;
    if (sebelumnya !== cacheRuntime()) {
      event.waitUntil(caches.delete(sebelumnya));
    }
    return;
  }

  if (pesan.type === 'BERSIHKAN') {
    event.waitUntil(
      caches
        .keys()
        .then((nama) => Promise.all(nama.filter((n) => n.startsWith(AWALAN_RUNTIME)).map((n) => caches.delete(n))))
        .then(() => {
          kunciKonteks = 'tamu';
        }),
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

  if (asetStatis(url)) {
    event.respondWith(asetDariCache(permintaan));
    return;
  }

  // Sisanya (Inertia, endpoint offline, unduhan berkas) sengaja dibiarkan
  // lewat tanpa cache: isinya spesifik tenant dan tidak boleh mengendap di
  // perangkat.
});

/**
 * Network-first untuk seluruh navigasi. Ruang kerja teknisi disimpan ke cache
 * runtime bertenant supaya tetap dapat dibuka tanpa sinyal; navigasi lain yang
 * gagal jatuh ke halaman cadangan.
 */
async function navigasi(permintaan, url) {
  const bolehDisimpan = url.pathname === HALAMAN_OFFLINE_DIIZINKAN;

  try {
    const respons = await fetch(permintaan);
    if (bolehDisimpan && respons.ok && respons.type === 'basic') {
      const cache = await caches.open(cacheRuntime());
      cache.put(permintaan, respons.clone());
    }

    return respons;
  } catch (galat) {
    if (bolehDisimpan) {
      const cache = await caches.open(cacheRuntime());
      const tersimpan = await cache.match(permintaan, { ignoreSearch: true });
      if (tersimpan) {
        return tersimpan;
      }
    }

    const kerangka = await caches.open(CACHE_KERANGKA);
    const cadangan = await kerangka.match(HALAMAN_OFFLINE);

    return cadangan || Response.error();
  }
}

/**
 * Cache-first untuk aset statis ber-hash. Aset build Vite memiliki hash pada
 * namanya, jadi versi baru selalu memakai URL baru dan tidak pernah basi.
 */
async function asetDariCache(permintaan) {
  const cache = await caches.open(cacheRuntime());
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
