import { kunciKonteks, type KonteksOffline } from '@/lib/penyimpanan-offline';

/**
 * Pemasangan dan pembaruan service worker Amanpoll (FASE 20.01).
 *
 * Pembaruan tidak pernah dipaksakan di tengah pekerjaan: worker baru menunggu
 * sampai `terapkanPembaruan()` dipanggil, yang hanya terjadi setelah pengguna
 * menyetujui tawaran muat ulang.
 */

let pendaftaran: ServiceWorkerRegistration | null = null;
let menungguPembaruan: ServiceWorker | null = null;

export function serviceWorkerDidukung(): boolean {
  return typeof navigator !== 'undefined' && 'serviceWorker' in navigator;
}

/**
 * @param saatAdaPembaruan dipanggil ketika versi baru siap diaktifkan
 */
export async function pasangServiceWorker(saatAdaPembaruan: () => void): Promise<void> {
  if (!serviceWorkerDidukung()) {
    return;
  }

  try {
    pendaftaran = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
  } catch {
    // Browser tanpa izin service worker (mis. mode privat) tetap harus bisa
    // memakai aplikasi secara online; kegagalan pemasangan sengaja diabaikan.
    return;
  }

  const tandaiMenunggu = (pekerja: ServiceWorker | null) => {
    if (pekerja && navigator.serviceWorker.controller) {
      menungguPembaruan = pekerja;
      saatAdaPembaruan();
    }
  };

  tandaiMenunggu(pendaftaran.waiting);

  pendaftaran.addEventListener('updatefound', () => {
    const pekerja = pendaftaran?.installing ?? null;
    pekerja?.addEventListener('statechange', () => {
      if (pekerja.state === 'installed') {
        tandaiMenunggu(pekerja);
      }
    });
  });
}

export function adaPembaruanMenunggu(): boolean {
  return menungguPembaruan !== null;
}

/** Mengaktifkan worker baru lalu memuat ulang halaman satu kali. */
export function terapkanPembaruan(): void {
  if (!menungguPembaruan) {
    return;
  }

  navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload(), {
    once: true,
  });
  menungguPembaruan.postMessage({ type: 'LEWATI_MENUNGGU' });
  menungguPembaruan = null;
}

function kirimKeServiceWorker(pesan: Record<string, unknown>): void {
  if (!serviceWorkerDidukung()) {
    return;
  }

  navigator.serviceWorker.ready.then((siap) => siap.active?.postMessage(pesan)).catch(() => undefined);
}

/** Menyekat cache runtime per organisasi + pengguna (20.02). */
export function tetapkanKonteksCache(konteks: KonteksOffline): void {
  kirimKeServiceWorker({ type: 'TETAPKAN_KONTEKS', kunci: kunciKonteks(konteks) });
}

/** Membuang cache runtime milik sesi yang baru saja ditutup (20.02). */
export function bersihkanCache(): void {
  kirimKeServiceWorker({ type: 'BERSIHKAN' });
}
