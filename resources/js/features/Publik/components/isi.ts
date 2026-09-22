/** Pembaca isi blok (MARKETING.md 8). */

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

export function angka(isi: Record<string, unknown>, kunci: string): number | null {
  const nilai = isi[kunci];

  return typeof nilai === 'number' && Number.isFinite(nilai) ? nilai : null;
}

export function benar(isi: Record<string, unknown>, kunci: string): boolean {
  return isi[kunci] === true;
}

/** Hanya `http`, `https`, dan alamat relatif yang diloloskan. */
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
