/**
 * Pembaca isi blok (MARKETING.md 8).
 *
 * Isi blok disimpan sebagai JSON bebas bentuk supaya jenis blok baru tidak
 * menuntut migrasi. Konsekuensinya, apa pun yang keluar dari sana harus
 * diperlakukan sebagai data tak dikenal — kunci bisa hilang, tipenya bisa
 * salah, dan komponen yang mengasumsikan sebaliknya akan memutih seluruh
 * halaman publik karena satu blok yang tertulis keliru.
 */

export function teks(isi: Record<string, unknown>, kunci: string, bawaan = ''): string {
  const nilai = isi[kunci];

  return typeof nilai === 'string' ? nilai : bawaan;
}

export function teksOpsional(isi: Record<string, unknown>, kunci: string): string | null {
  const nilai = isi[kunci];

  return typeof nilai === 'string' && nilai !== '' ? nilai : null;
}

export function daftarTeks(isi: Record<string, unknown>, kunci: string): string[] {
  const nilai = isi[kunci];

  return Array.isArray(nilai) ? nilai.filter((satu): satu is string => typeof satu === 'string') : [];
}

export function daftarObjek(isi: Record<string, unknown>, kunci: string): Record<string, unknown>[] {
  const nilai = isi[kunci];

  if (!Array.isArray(nilai)) {
    return [];
  }

  return nilai.filter(
    (satu): satu is Record<string, unknown> => typeof satu === 'object' && satu !== null && !Array.isArray(satu),
  );
}

/**
 * Hanya `http`, `https`, dan alamat relatif yang diloloskan. Isi halaman
 * ditulis manusia lewat konsol, dan satu tautan berskema `javascript:` yang
 * lolos ke situs publik berarti skrip pihak ketiga berjalan di domain utama.
 */
export function urlAman(nilai: string | null): string | null {
  if (nilai === null || nilai === '') {
    return null;
  }

  if (nilai.startsWith('/') && !nilai.startsWith('//')) {
    return nilai;
  }

  try {
    const url = new URL(nilai);

    return url.protocol === 'http:' || url.protocol === 'https:' ? url.toString() : null;
  } catch {
    return null;
  }
}
