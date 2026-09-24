import { pampatkanGambar } from '@/lib/pemampat-gambar';

/** Sisi terpanjang foto Mode Lapangan yang disimpan dan diunggah; cukup untuk bukti pekerjaan. */
export const SISI_FOTO_LAPANGAN = 1600;

/**
 * Memperkecil foto kamera (dan tanda tangan) sebelum disimpan di perangkat atau diunggah,
 * lewat pengecil gambar bersama (`lib/pemampat-gambar`): WebP, JPEG bila peramban tidak
 * mendukung WebP. Foto HP modern bisa belasan MB dan berformat yang tidak diterima server
 * (HEIC). Bila peramban tidak dapat membacanya, atau hasilnya tidak lebih kecil, berkas
 * asli dipakai.
 */
export function perkecilFoto(berkas: File): Promise<File>;
export function perkecilFoto(berkas: Blob): Promise<Blob>;
export function perkecilFoto(berkas: Blob): Promise<Blob> {
  return pampatkanGambar(berkas, { sisiMaks: SISI_FOTO_LAPANGAN });
}
