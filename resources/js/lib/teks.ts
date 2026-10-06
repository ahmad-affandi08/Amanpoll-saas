const NAMA_MEREK = ['WhatsApp', 'TikTok', 'YouTube', 'LinkedIn'];

/** Nilai enum dari basis data (`MenungguVerifikasi`) dipecah menjadi kata agar terbaca: `Menunggu Verifikasi`. Nama merek tetap utuh. */
export function labelEnum(nilai: string | null | undefined): string {
  const teks = nilai ?? '';

  if (NAMA_MEREK.includes(teks)) {
    return teks;
  }

  return teks.replace(/([a-z])([A-Z])/g, '$1 $2');
}
