/**
 * Pengecil gambar di peramban sebelum dikirim (PRD 11.1), memakai kanvas bawaan.
 *
 * Gambar diperkecil sampai sisi terpanjangnya ≤ `sisiMaks`, lalu dikodekan ke WebP
 * (JPEG bila peramban tidak dapat menulis WebP). Orientasi EXIF diterapkan peramban
 * saat membaca gambar, dan kanvas tidak membawa metadata apa pun (termasuk GPS).
 *
 * Berkas dikembalikan apa adanya bila: bukan gambar, GIF (animasi hilang di kanvas),
 * SVG, gambar gagal dibaca, atau hasilnya tidak lebih kecil. Format yang tidak diterima
 * server apa adanya (mis. HEIC dari kamera iPhone) selalu memakai hasil kanvas.
 *
 * Server tetap memampatkan ulang; ini hanya menghemat kuota dan waktu unggah.
 */

/** Sama dengan batas bawaan server (`amanpoll.kompresi.gambar.sisi_maks`). */
export const SISI_MAKS_GAMBAR = 2560;

const KUALITAS_BAWAAN = 0.8;

/** Format yang dapat disimpan server apa adanya; untuk ini hasil kanvas dipakai hanya bila lebih kecil. */
const FORMAT_WEB = ['image/jpeg', 'image/png', 'image/webp'];

const DILEWATI = ['image/gif', 'image/svg+xml'];

export interface OpsiPemampatGambar {
  /** Sisi terpanjang hasil, dalam piksel. Bawaan {@link SISI_MAKS_GAMBAR}. */
  sisiMaks?: number;
  /** Kualitas 0..1. Bawaan 0.8. */
  kualitas?: number;
}

interface GambarTerbaca {
  sumber: CanvasImageSource;
  lebar: number;
  tinggi: number;
  lepas: () => void;
}

async function baca(berkas: Blob): Promise<GambarTerbaca> {
  if (typeof createImageBitmap === 'function') {
    const bitmap = await createImageBitmap(berkas);
    return { sumber: bitmap, lebar: bitmap.width, tinggi: bitmap.height, lepas: () => bitmap.close() };
  }

  const url = URL.createObjectURL(berkas);
  const gambar = new Image();
  gambar.src = url;
  try {
    await gambar.decode();
  } catch (galat) {
    URL.revokeObjectURL(url);
    throw galat;
  }
  return {
    sumber: gambar,
    lebar: gambar.naturalWidth,
    tinggi: gambar.naturalHeight,
    lepas: () => URL.revokeObjectURL(url),
  };
}

function keBlob(kanvas: HTMLCanvasElement, jenis: string, kualitas: number): Promise<Blob | null> {
  return new Promise((selesai) => kanvas.toBlob(selesai, jenis, kualitas));
}

function gantiEkstensi(nama: string, jenis: string): string {
  const ekstensi = jenis === 'image/webp' ? 'webp' : 'jpg';
  const dasar = nama.replace(/\.[^./\\]*$/, '') || 'gambar';
  return `${dasar}.${ekstensi}`;
}

export function pampatkanGambar(berkas: File, opsi?: OpsiPemampatGambar): Promise<File>;
export function pampatkanGambar(berkas: Blob, opsi?: OpsiPemampatGambar): Promise<Blob>;
export async function pampatkanGambar(berkas: Blob, opsi: OpsiPemampatGambar = {}): Promise<Blob> {
  if (!berkas.type.startsWith('image/') || DILEWATI.includes(berkas.type)) return berkas;
  if (typeof document === 'undefined') return berkas;

  const sisiMaks = opsi.sisiMaks ?? SISI_MAKS_GAMBAR;
  const kualitas = opsi.kualitas ?? KUALITAS_BAWAAN;
  const formatWeb = FORMAT_WEB.includes(berkas.type);

  let gambar: GambarTerbaca;
  try {
    gambar = await baca(berkas);
  } catch {
    return berkas;
  }

  try {
    const skala = Math.min(1, sisiMaks / Math.max(gambar.lebar, gambar.tinggi));
    const kanvas = document.createElement('canvas');
    kanvas.width = Math.max(1, Math.round(gambar.lebar * skala));
    kanvas.height = Math.max(1, Math.round(gambar.tinggi * skala));
    const konteks = kanvas.getContext('2d');
    if (!konteks) return berkas;
    konteks.drawImage(gambar.sumber, 0, 0, kanvas.width, kanvas.height);

    let hasil = await keBlob(kanvas, 'image/webp', kualitas);
    // Peramban tanpa penulis WebP mengembalikan PNG; JPEG sebagai cadangan, kecuali
    // PNG asli (transparansinya akan hilang di JPEG).
    if (!hasil || hasil.type !== 'image/webp') {
      if (berkas.type === 'image/png') return berkas;
      hasil = await keBlob(kanvas, 'image/jpeg', kualitas);
    }
    if (!hasil || (hasil.type !== 'image/webp' && hasil.type !== 'image/jpeg')) return berkas;
    if (formatWeb && hasil.size >= berkas.size) return berkas;

    if (berkas instanceof File) {
      return new File([hasil], gantiEkstensi(berkas.name, hasil.type), {
        type: hasil.type,
        lastModified: berkas.lastModified,
      });
    }
    return hasil;
  } catch {
    return berkas;
  } finally {
    gambar.lepas();
  }
}
