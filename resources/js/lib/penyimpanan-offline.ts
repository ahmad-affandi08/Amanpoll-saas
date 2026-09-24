import type { MutasiOffline, PaketOffline } from '@/features/Sinkronisasi/types';

/** Penyimpanan lokal perangkat untuk mode offline (FASE 20.02/20.03). */

const VERSI_SKEMA = 2;
const TOKO_PAKET = 'paket';
const TOKO_ANTRIAN = 'antrian';
/** Draf layar lapangan (catatan, foto, tanda tangan) yang belum terkirim; ikut terhapus saat logout. */
const TOKO_DRAF = 'draf';

export interface KonteksOffline {
  organisasiId: string;
  penggunaId: string;
}

export function kunciKonteks({ organisasiId, penggunaId }: KonteksOffline): string {
  return `${organisasiId}:${penggunaId}`;
}

function namaBasisData(konteks: KonteksOffline): string {
  return `amanpoll-offline-${kunciKonteks(konteks)}`;
}

export function penyimpananTersedia(): boolean {
  return typeof indexedDB !== 'undefined';
}

function buka(konteks: KonteksOffline): Promise<IDBDatabase> {
  return new Promise((selesai, gagal) => {
    const permintaan = indexedDB.open(namaBasisData(konteks), VERSI_SKEMA);

    permintaan.onupgradeneeded = () => {
      const db = permintaan.result;
      if (!db.objectStoreNames.contains(TOKO_PAKET)) {
        db.createObjectStore(TOKO_PAKET);
      }
      if (!db.objectStoreNames.contains(TOKO_ANTRIAN)) {
        db.createObjectStore(TOKO_ANTRIAN, { keyPath: 'KunciOperasi' });
      }
      if (!db.objectStoreNames.contains(TOKO_DRAF)) {
        db.createObjectStore(TOKO_DRAF);
      }
    };

    permintaan.onsuccess = () => selesai(permintaan.result);
    permintaan.onerror = () => gagal(permintaan.error);
  });
}

function jalankan<T>(
  konteks: KonteksOffline,
  toko: string,
  mode: IDBTransactionMode,
  aksi: (store: IDBObjectStore) => IDBRequest<T>,
): Promise<T> {
  return buka(konteks).then(
    (db) =>
      new Promise<T>((selesai, gagal) => {
        const transaksi = db.transaction(toko, mode);
        const permintaan = aksi(transaksi.objectStore(toko));
        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
        transaksi.oncomplete = () => db.close();
      }),
  );
}

export function simpanPaket(konteks: KonteksOffline, paket: PaketOffline): Promise<unknown> {
  return jalankan(konteks, TOKO_PAKET, 'readwrite', (store) => store.put(paket, 'terkini'));
}

export function ambilPaket(konteks: KonteksOffline): Promise<PaketOffline | undefined> {
  return jalankan<PaketOffline | undefined>(konteks, TOKO_PAKET, 'readonly', (store) => store.get('terkini'));
}

export function simpanMutasi(konteks: KonteksOffline, mutasi: MutasiOffline): Promise<unknown> {
  return jalankan(konteks, TOKO_ANTRIAN, 'readwrite', (store) => store.put(mutasi));
}

export function ambilAntrian(konteks: KonteksOffline): Promise<MutasiOffline[]> {
  return jalankan<MutasiOffline[]>(konteks, TOKO_ANTRIAN, 'readonly', (store) => store.getAll());
}

export function hapusMutasi(konteks: KonteksOffline, kunciOperasi: string): Promise<unknown> {
  return jalankan(konteks, TOKO_ANTRIAN, 'readwrite', (store) => store.delete(kunciOperasi));
}

/**
 * Draf per kunci bebas (mis. `teknisi:sesi:<Id>`). Nilainya boleh memuat `Blob` (foto),
 * jadi foto yang diambil tanpa sinyal tetap di perangkat sampai terkirim.
 */
export function simpanDraf<T>(konteks: KonteksOffline, kunci: string, nilai: T): Promise<unknown> {
  return jalankan(konteks, TOKO_DRAF, 'readwrite', (store) => store.put(nilai, kunci));
}

export function ambilDraf<T>(konteks: KonteksOffline, kunci: string): Promise<T | undefined> {
  return jalankan<T | undefined>(konteks, TOKO_DRAF, 'readonly', (store) => store.get(kunci));
}

export function hapusDraf(konteks: KonteksOffline, kunci: string): Promise<unknown> {
  return jalankan(konteks, TOKO_DRAF, 'readwrite', (store) => store.delete(kunci));
}

/** Seluruh kunci draf yang berawalan tertentu. */
export function daftarKunciDraf(konteks: KonteksOffline, awalan: string): Promise<string[]> {
  return jalankan<IDBValidKey[]>(konteks, TOKO_DRAF, 'readonly', (store) => store.getAllKeys()).then(
    (kunci) => kunci.map(String).filter((satu) => satu.startsWith(awalan)),
  );
}

/** Membuang seluruh data lokal milik konteks ini. */
export function hapusBasisData(konteks: KonteksOffline): Promise<void> {
  return new Promise((selesai) => {
    const permintaan = indexedDB.deleteDatabase(namaBasisData(konteks));
    permintaan.onsuccess = () => selesai();
    permintaan.onerror = () => selesai();
    permintaan.onblocked = () => selesai();
  });
}
