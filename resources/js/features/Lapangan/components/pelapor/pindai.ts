import { ruteLapangan } from '@/features/Lapangan/api';

/** Label QR bisa berisi kode mentah atau tautan `/aset/pindai/<kode>`; ambil kodenya. */
export function kodeDariPindaian(teks: string): string {
  const cocok = teks.match(/\/aset\/pindai\/([^/?#]+)/);
  return cocok ? decodeURIComponent(cocok[1]) : teks.trim();
}

/**
 * QR konfirmasi penerima (PRD 8.22) yang dipindai dari dalam aplikasi: jalur beserta tanda
 * tangan tautannya, atau `null` bila bukan QR konfirmasi. Dibuka apa adanya supaya tanda
 * tangan server tetap utuh.
 */
export function tautanKonfirmasiPenerima(teks: string): string | null {
  const cocok = teks.trim().match(ruteLapangan.polaKonfirmasiPenerima);
  if (!cocok) return null;
  try {
    const url = new URL(teks.trim(), window.location.origin);
    return `${url.pathname}${url.search}`;
  } catch {
    return null;
  }
}
